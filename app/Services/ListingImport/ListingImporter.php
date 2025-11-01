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

            // Resolve agent by name or external_agent_id if available
            if (!$dto->agentId) {
                $agentId = null;
                
                // Try to find by agent name first (more reliable)
                if ($dto->agentName) {
                    $agentId = $this->resolveAgentByName($dto->agentName);
                }
                
                // Fallback to external_agent_id if name didn't work
                if (!$agentId && $dto->externalAgentId) {
                    $agentId = $this->resolveAgentByExternalId($dto->externalAgentId);
                }
                
                if ($agentId) {
                    $entry->set('agent', $agentId);
                    Log::info('Agent linked to listing.', [
                        'external_id' => $externalId,
                        'agent_name' => $dto->agentName,
                        'external_agent_id' => $dto->externalAgentId,
                        'agent_id' => $agentId,
                    ]);
                }
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

    /**
     * Find agent by external_id and return its Statamic ID
     */
    private function resolveAgentByExternalId(string $externalAgentId): ?string
    {
        $agent = Entry::query()
            ->where('collection', 'agents')
            ->where('data->external_id', $externalAgentId)
            ->first();

        return $agent?->id();
    }

    /**
     * Find agent by name (fuzzy matching) and return its Statamic ID
     */
    private function resolveAgentByName(string $agentName): ?string
    {
        // Try exact match first
        $agent = Entry::query()
            ->where('collection', 'agents')
            ->where('data->title', $agentName)
            ->first();

        if ($agent) {
            return $agent->id();
        }

        // Try case-insensitive partial match
        $normalizedName = mb_strtolower(trim($agentName));
        
        $agents = Entry::query()
            ->where('collection', 'agents')
            ->get();

        foreach ($agents as $agent) {
            $agentTitle = mb_strtolower(trim($agent->get('title')));
            
            // Check if names match (case-insensitive)
            if ($agentTitle === $normalizedName) {
                return $agent->id();
            }
            
            // Check if one contains the other (for "John Smith" vs "John")
            if (str_contains($agentTitle, $normalizedName) || str_contains($normalizedName, $agentTitle)) {
                return $agent->id();
            }
        }

        return null;
    }
}
