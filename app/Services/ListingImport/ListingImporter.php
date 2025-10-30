<?php

namespace App\Services\ListingImport;

use App\Services\CommunityImport\CommunitySynchronizer;
use App\Services\ListingImport\Dto\ListingImportData;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Throwable;

class ListingImporter
{
    /**
     * @var array<string, mixed>
     */
    private array $config;

    public function __construct(
        private readonly CommunitySynchronizer $communitySynchronizer,
        ?array $config = null
    ) {
        $this->config = $config ?? config('services.import_json', []);
    }

    /**
     * @param  array<int, array<string, mixed>>  $records
     * @return array<string, mixed>
     */
    public function sync(array $records): array
    {
        $collectionHandle = Arr::get($this->config, 'collection_handle', 'properties');
        $identifierKey = Arr::get($this->config, 'id_key', 'id');
        $siteHandle = Site::default()->handle();

        $report = [
            'total' => count($records),
            'created' => 0,
            'updated' => 0,
            'unpublished' => 0,
            'skipped_invalid' => 0,
            'skipped_missing_identifier' => 0,
            'skipped_new_unpublished' => 0,
        ];

        foreach ($records as $record) {
            try {
                $dto = ListingImportData::fromArray($record);
            } catch (InvalidArgumentException $exception) {
                $report['skipped_invalid']++;
                Log::warning('Listing skipped due to invalid payload.', [
                    'reason' => $exception->getMessage(),
                    'identifier' => $this->extractIdentifier($record, $identifierKey),
                ]);

                continue;
            }

            $externalId = $dto->externalId ?? $this->extractIdentifier($record, $identifierKey);

            if (! $externalId) {
                $report['skipped_missing_identifier']++;
                Log::warning('Listing skipped due to missing identifier.', [
                    'record' => $record,
                ]);

                continue;
            }

            $shouldBePublic = $this->shouldBePublic($dto, $record);

            $entry = Entry::query()
                ->where('collection', $collectionHandle)
                ->where('site', $siteHandle)
                ->where('data->external_id', $externalId)
                ->first();

            $isNewEntry = false;

            if (! $entry) {
                if (! $shouldBePublic) {
                    $report['skipped_new_unpublished']++;
                    continue;
                }

                $entry = Entry::make()
                    ->collection($collectionHandle)
                    ->locale($siteHandle)
                    ->slug($this->generateSlug($dto, $externalId));

                $isNewEntry = true;
            }

            foreach ($dto->toStatamicAttributes() as $key => $value) {
                $entry->set($key, $value);
            }

            $entry->set('external_id', $externalId);

            if ($isNewEntry && $entry->get('show_status') === null) {
                $entry->set('show_status', true);
            }

            $entry->published($shouldBePublic);

            try {
                $entry->save();
            } catch (Throwable $exception) {
                $report['skipped_invalid']++;
                Log::error('Failed to save listing entry.', [
                    'external_id' => $externalId,
                    'message' => $exception->getMessage(),
                ]);

                continue;
            }

            if ($isNewEntry) {
                $report['created']++;
            } else {
                $report['updated']++;
            }

            if (! $shouldBePublic) {
                $report['unpublished']++;
            }
        }

        $communityReport = $this->communitySynchronizer->sync();

        return array_merge($report, [
            'community_report' => $communityReport,
        ]);
    }

    private function shouldBePublic(ListingImportData $dto, array $record): bool
    {
        if ($dto->status !== 'published') {
            return false;
        }

        $flagKey = Arr::get($this->config, 'website_enabled_key');

        if (! $flagKey) {
            return true;
        }

        $raw = data_get($record, $flagKey);

        if ($raw === null) {
            return false;
        }

        if (is_bool($raw)) {
            return $raw;
        }

        if (is_numeric($raw)) {
            return (bool) $raw;
        }

        if (is_string($raw)) {
            $normalized = strtolower(trim($raw));
            $truthyValues = Arr::get($this->config, 'website_enabled_values', []);
            $truthyValues = array_map(static fn ($value) => strtolower(trim((string) $value)), $truthyValues);

            return in_array($normalized, $truthyValues, true);
        }

        return false;
    }

    private function extractIdentifier(array $record, string $identifierKey): ?string
    {
        $candidate = data_get($record, $identifierKey);

        if ($candidate === null) {
            return null;
        }

        if (is_scalar($candidate)) {
            $string = trim((string) $candidate);

            return $string === '' ? null : $string;
        }

        return null;
    }

    private function generateSlug(ListingImportData $dto, string $externalId): string
    {
        $base = Str::slug($dto->title);

        if ($base === '') {
            return 'listing-'.$externalId;
        }

        return $base.'-'.$externalId;
    }
}
