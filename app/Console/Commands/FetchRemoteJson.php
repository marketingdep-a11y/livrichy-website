<?php

namespace App\Console\Commands;

use App\Services\ListingImport\ListingImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class FetchRemoteJson extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:fetch-json {url? : The endpoint that returns JSON data}
        {--store= : Override the storage directory relative to storage/app}
        {--timeout= : Timeout in seconds for the HTTP request}
        {--retries= : Number of retries when the request fails}
        {--backoff= : Backoff in seconds between retries}
        {--collection= : Dot-notation path to the listings collection within the payload}
        {--id-key= : Dot-notation path to the unique identifier within each listing}
        {--required=* : Dot-notation keys that must be present for a listing to be considered ready}
        {--status-key= : Dot-notation path to the status attribute within each listing}
        {--active-status=* : Status values that should be treated as active listings}
        {--sync : Process the payload and synchronise listings into Statamic}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch JSON from a remote endpoint and store the raw response for debugging.';

    public function __construct(private readonly ListingImporter $importer)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $url = $this->argument('url') ?? config('services.import_json.url');

        if (blank($url)) {
            $this->error('An endpoint URL is required. Provide it as an argument or configure services.import_json.url.');

            return self::FAILURE;
        }

        $timeout = (float) ($this->option('timeout') ?? config('services.import_json.timeout', 10));
        $retries = max((int) ($this->option('retries') ?? config('services.import_json.retries', 3)), 1);
        $backoff = max((float) ($this->option('backoff') ?? config('services.import_json.backoff', 2)), 0);
        $storageDirectory = trim((string) ($this->option('store') ?? config('services.import_json.store', 'imports')), '/');
        $collectionKey = $this->option('collection') ?? config('services.import_json.collection');
        $identifierKey = $this->option('id-key') ?? config('services.import_json.id_key', 'id');
        $requiredFields = $this->option('required') ?: config('services.import_json.required_fields', []);
        $statusKey = $this->option('status-key') ?? config('services.import_json.status_key');
        $activeStatuses = $this->option('active-status') ?: config('services.import_json.active_statuses', []);

        $this->info(sprintf('Fetching JSON from %s', $url));

        try {
            $response = Http::timeout($timeout)
                ->retry($retries, fn ($attempt) => (int) ($backoff * 1000 * max($attempt, 1)))
                ->acceptJson()
                ->get($url);
        } catch (Throwable $exception) {
            Log::error('JSON import failed due to an HTTP client exception.', [
                'url' => $url,
                'message' => $exception->getMessage(),
            ]);

            $this->error('The request failed before completing. See logs for details.');

            return self::FAILURE;
        }

        if (! $response->successful()) {
            Log::warning('JSON import received a non-successful response.', [
                'url' => $url,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            $this->error(sprintf('The remote server responded with status %s.', $response->status()));

            return self::FAILURE;
        }

        $payload = $response->body();
        $isJson = $this->isJson($payload);

        if (! $isJson) {
            Log::warning('JSON import response did not contain valid JSON.', [
                'url' => $url,
                'body' => $payload,
            ]);

            $this->error('The response was not valid JSON. The raw body will still be stored for inspection.');
        }

        $disk = Storage::disk('local');

        if ($storageDirectory !== '') {
            $disk->makeDirectory($storageDirectory);
        }

        $filename = sprintf('%s.json', now()->format('Ymd_His_u'));
        $path = $storageDirectory ? $storageDirectory.'/'.$filename : $filename;

        $disk->put($path, $payload);

        $records = collect();

        if ($isJson) {
            $decoded = json_decode($payload, true);

            if (is_array($decoded)) {
                $records = $this->extractCollection($decoded, $collectionKey);
            }
        }

        $summary = $this->summariseRecords(
            records: $records,
            identifierKey: $identifierKey,
            requiredFields: $requiredFields,
            statusKey: $statusKey,
            activeStatuses: $activeStatuses,
        );

        Log::info('JSON import completed successfully.', array_merge([
            'url' => $url,
            'path' => $path,
        ], $summary));

        $this->info(sprintf('Saved response to storage/app/%s', $path));

        if (! empty($summary)) {
            $this->newLine();
            $this->table(
                ['Metric', 'Value'],
                collect($summary)
                    ->map(fn ($value, $key) => [ucwords(str_replace('_', ' ', $key)), is_array($value) ? implode(', ', $value) : $value])
                    ->all()
            );
        }

        if ($this->option('sync')) {
            if (! $isJson) {
                $this->error('Skipping sync because the response was not valid JSON.');
            } elseif ($records->isEmpty()) {
                $this->comment('No listings found in the payload. Nothing to sync.');
            } else {
                $report = $this->importer->sync($records->values()->all());

                $communityReport = $report['community_report'] ?? null;
                unset($report['community_report']);

                $this->newLine();
                $this->table(
                    ['Metric', 'Value'],
                    collect($report)
                        ->map(fn ($value, $key) => [ucwords(str_replace('_', ' ', $key)), $value])
                        ->all()
                );

                if (is_array($communityReport)) {
                    $this->newLine();
                    $this->table(
                        ['Community Metric', 'Value'],
                        collect($communityReport)
                            ->map(fn ($value, $key) => [ucwords(str_replace('_', ' ', $key)), $value])
                            ->all()
                    );
                }
            }
        }

        return self::SUCCESS;
    }

    private function isJson(string $payload): bool
    {
        if ($payload === '') {
            return false;
        }

        json_decode($payload, true);

        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * @return array<string, mixed>
     */
    private function summarisePayload(
        string $payload,
        ?string $collectionKey,
        string $identifierKey,
        array $requiredFields,
        ?string $statusKey,
        array $activeStatuses
    ): array {
        if (! $this->isJson($payload)) {
            return [];
        }

        $decoded = json_decode($payload, true);

        if (! is_array($decoded)) {
            return [];
        }

        $records = $this->extractCollection($decoded, $collectionKey);

        return $this->summariseRecords(
            records: $records,
            identifierKey: $identifierKey,
            requiredFields: $requiredFields,
            statusKey: $statusKey,
            activeStatuses: $activeStatuses,
        );
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $records
     * @return array<string, mixed>
     */
    private function summariseRecords(
        Collection $records,
        string $identifierKey,
        array $requiredFields,
        ?string $statusKey,
        array $activeStatuses
    ): array {
        if ($records->isEmpty()) {
            return [
                'records_total' => 0,
                'property_ids_total' => 0,
            ];
        }

        $requiredFields = array_values(array_filter($requiredFields, fn ($field) => filled($field)));

        $statusKey = filled($statusKey) ? $statusKey : null;
        $activeStatuses = collect($activeStatuses)
            ->filter(fn ($status) => filled($status))
            ->map(fn ($status) => (string) $status)
            ->values()
            ->all();

        $normalizedActiveStatuses = array_map('mb_strtolower', $activeStatuses);

        $recordsTotal = $records->count();
        $ids = collect();
        $readyIds = collect();
        $completeCount = 0;
        $activeCount = 0;

        foreach ($records as $record) {
            $identifier = data_get($record, $identifierKey);

            if (! blank($identifier)) {
                $ids->push($identifier);
            }

            $hasRequiredFields = $this->recordHasRequiredFields($record, $requiredFields);

            if ($hasRequiredFields) {
                $completeCount++;
            }

            $isActive = $this->recordIsActive($record, $statusKey, $activeStatuses, $normalizedActiveStatuses);

            if ($isActive) {
                $activeCount++;
            }

            if ($hasRequiredFields && $isActive) {
                $readyIds->push($identifier);
            }
        }

        $uniqueIds = $ids
            ->filter(fn ($identifier) => ! blank($identifier))
            ->unique()
            ->values();

        $readyIds = $readyIds
            ->filter(fn ($identifier) => ! blank($identifier))
            ->unique()
            ->values();

        return array_filter([
            'records_total' => $recordsTotal,
            'property_ids_total' => $uniqueIds->count(),
            'property_ids_missing' => max($recordsTotal - $uniqueIds->count(), 0),
            'property_ids_ready' => $readyIds->count(),
            'records_with_required_fields' => $completeCount,
            'records_active' => $statusKey ? $activeCount : null,
            'required_fields' => $requiredFields ?: null,
            'status_key' => $statusKey,
            'active_statuses' => $activeStatuses ?: null,
        ], fn ($value) => $value !== null && $value !== []);
    }

    /**
     * @param array<mixed>|Collection $record
     * @param array<int, string> $activeStatuses
     * @param array<int, string> $normalizedActiveStatuses
     */
    private function recordIsActive($record, ?string $statusKey, array $activeStatuses, array $normalizedActiveStatuses): bool
    {
        if (! $statusKey) {
            return true;
        }

        $status = data_get($record, $statusKey);

        if ($activeStatuses === []) {
            return filled($status);
        }

        if (is_string($status)) {
            return in_array(mb_strtolower($status), $normalizedActiveStatuses, true);
        }

        if (is_numeric($status)) {
            return in_array((string) $status, $activeStatuses, true);
        }

        return false;
    }

    /**
     * @param array<mixed> $payload
     */
    private function extractCollection(array $payload, ?string $collectionKey): Collection
    {
        $candidate = $collectionKey ? data_get($payload, $collectionKey) : $payload;

        if (! is_array($candidate)) {
            return collect();
        }

        if (Arr::isAssoc($candidate)) {
            $candidate = array_values($candidate);
        }

        return collect($candidate)
            ->map(function ($item) {
                if ($item instanceof Collection) {
                    return $item->all();
                }

                if (is_array($item)) {
                    return $item;
                }

                if (is_object($item)) {
                    return (array) $item;
                }

                return null;
            })
            ->filter(fn ($item) => is_array($item));
    }

    /**
     * @param array<string> $requiredFields
     * @param array<mixed> $record
     */
    private function recordHasRequiredFields(array $record, array $requiredFields): bool
    {
        if ($requiredFields === []) {
            return true;
        }

        foreach ($requiredFields as $field) {
            if (blank(data_get($record, $field))) {
                return false;
            }
        }

        return true;
    }
}
