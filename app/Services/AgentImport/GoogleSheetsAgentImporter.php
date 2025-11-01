<?php

namespace App\Services\AgentImport;

use Google\Client;
use Google\Service\Sheets;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Throwable;

class GoogleSheetsAgentImporter
{
    private const COLUMN_MAPPING = [
        'A' => 'agent_id',        // Agent' ID
        'B' => 'last_updated',    // LastUpdated
        'C' => 'department',      // Department
        'D' => 'name',            // Name Of Agent
        'E' => 'position',        // Position
        'F' => 'photo',           // Photo
        'G' => 'bio',             // Bio
        'H' => 'status',          // Status
    ];

    /**
     * Synchronize agents from Google Sheets
     *
     * @return array<string, int>
     */
    public function sync(): array
    {
        $spreadsheetId = config('services.google_sheets_agents.spreadsheet_id');
        $range = config('services.google_sheets_agents.range', 'Table1!A2:H');

        if (blank($spreadsheetId)) {
            throw new \InvalidArgumentException('Google Sheets spreadsheet ID is not configured.');
        }

        $client = $this->getGoogleClient();
        $sheetsService = new Sheets($client);

        try {
            $response = $sheetsService->spreadsheets_values->get($spreadsheetId, $range);
            $rows = $response->getValues();
        } catch (Throwable $exception) {
            Log::error('Failed to fetch Google Sheets data.', [
                'spreadsheet_id' => $spreadsheetId,
                'range' => $range,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        if (empty($rows)) {
            Log::info('No data found in Google Sheets.');

            return [
                'fetched' => 0,
                'published_count' => 0,
                'created' => 0,
                'updated' => 0,
                'deleted' => 0,
                'skipped_missing_name' => 0,
                'skipped_errors' => 0,
            ];
        }

        $records = $this->parseRows($rows);

        return $this->persist($records);
    }

    /**
     * Parse rows from Google Sheets into structured records
     *
     * @param  array<int, array<int, string>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function parseRows(array $rows): array
    {
        $records = [];

        foreach ($rows as $row) {
            $record = [];

            // Map columns to fields
            $record['agent_id'] = $row[0] ?? null;
            $record['last_updated'] = $row[1] ?? null;
            $record['department'] = $row[2] ?? null;
            $record['name'] = $row[3] ?? null;
            $record['position'] = $row[4] ?? null;
            $record['photo'] = $row[5] ?? null;
            $record['bio'] = $row[6] ?? null;
            $record['status'] = $row[7] ?? null;

            $records[] = $record;
        }

        return $records;
    }

    /**
     * Persist records to Statamic
     *
     * @param  array<int, array<string, mixed>>  $records
     * @return array<string, int>
     */
    private function persist(array $records): array
    {
        $report = [
            'fetched' => count($records),
            'published_count' => 0,
            'created' => 0,
            'updated' => 0,
            'deleted' => 0,
            'skipped_missing_name' => 0,
            'skipped_errors' => 0,
        ];

        // Get all published agents from Google Sheets
        $publishedAgentIds = [];

        foreach ($records as $record) {
            $status = trim((string) Arr::get($record, 'status'));
            $agentId = trim((string) Arr::get($record, 'agent_id'));

            // Only process agents with "Published" status
            if (strcasecmp($status, 'Published') !== 0) {
                continue;
            }

            $report['published_count']++;

            $name = trim((string) Arr::get($record, 'name'));

            if ($name === '') {
                $report['skipped_missing_name']++;
                continue;
            }

            if ($agentId === '') {
                $report['skipped_missing_name']++;
                continue;
            }

            $publishedAgentIds[] = $agentId;

            try {
                $created = $this->upsertAgent($record);

                if ($created) {
                    $report['created']++;
                } else {
                    $report['updated']++;
                }
            } catch (Throwable $exception) {
                $report['skipped_errors']++;

                Log::error('Unable to persist Google Sheets agent.', [
                    'agent_id' => $agentId,
                    'name' => $name,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        // Delete agents that are not in the published list
        $deleted = $this->deleteUnpublishedAgents($publishedAgentIds);
        $report['deleted'] = $deleted;

        return $report;
    }

    /**
     * Upsert an agent entry
     *
     * @param  array<string, mixed>  $record
     */
    private function upsertAgent(array $record): bool
    {
        $externalId = trim((string) Arr::get($record, 'agent_id'));
        $name = trim((string) Arr::get($record, 'name'));

        $entry = Entry::query()
            ->where('collection', 'agents')
            ->where('data->external_id', $externalId)
            ->first();

        $isNew = false;

        if (! $entry) {
            $isNew = true;
            $entry = Entry::make()
                ->collection('agents')
                ->locale(Site::default()->handle())
                ->slug($this->generateUniqueSlug($name, $externalId));
        }

        // Set basic fields
        $entry->set('title', $name);
        $entry->set('external_id', $externalId);

        // Set position if provided
        $position = trim((string) Arr::get($record, 'position'));
        if ($position !== '') {
            $entry->set('position', $position);
        }

        // Download and store photo
        $photoUrl = trim((string) Arr::get($record, 'photo'));
        if ($photoUrl !== '') {
            if ($photoPath = $this->storePhoto($photoUrl, $entry->slug() ?? $this->generateUniqueSlug($name, $externalId))) {
                $entry->set('image', $photoPath);
            }
        }

        // Set bio/content if provided
        $bio = trim((string) Arr::get($record, 'bio'));
        if ($bio !== '') {
            // Convert plain text to Bard format
            $entry->set('content', [
                [
                    'type' => 'paragraph',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => $bio,
                        ],
                    ],
                ],
            ]);
        }

        // Set social media (empty for now, can be extended)
        if (! $entry->get('social_media')) {
            $entry->set('social_media', []);
        }

        // Publish the entry
        $entry->published(true);

        $entry->save();

        return $isNew;
    }

    /**
     * Delete agents that are not in the published list
     *
     * @param  array<int, string>  $publishedAgentIds
     */
    private function deleteUnpublishedAgents(array $publishedAgentIds): int
    {
        $allAgents = Entry::query()
            ->where('collection', 'agents')
            ->get();

        $deletedCount = 0;

        foreach ($allAgents as $agent) {
            $externalId = $agent->get('external_id');

            // If agent is not in the published list, delete it
            if (! in_array($externalId, $publishedAgentIds, true)) {
                try {
                    $agent->delete();
                    $deletedCount++;

                    Log::info('Deleted unpublished agent.', [
                        'external_id' => $externalId,
                        'title' => $agent->get('title'),
                    ]);
                } catch (Throwable $exception) {
                    Log::error('Failed to delete agent.', [
                        'external_id' => $externalId,
                        'message' => $exception->getMessage(),
                    ]);
                }
            }
        }

        return $deletedCount;
    }

    /**
     * Generate a unique slug for the agent
     */
    private function generateUniqueSlug(string $name, string $externalId): string
    {
        $base = Str::slug($name);

        if ($base === '') {
            $base = 'agent-'.$externalId;
        }

        $slug = $base;
        $suffix = 1;

        while (Entry::query()->where('collection', 'agents')->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    /**
     * Store photo from URL
     */
    private function storePhoto(?string $url, string $slug): ?string
    {
        if (blank($url)) {
            return null;
        }

        try {
            $response = Http::timeout(15)->get($url);

            if (! $response->successful()) {
                return null;
            }

            $extension = pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION) ?: 'jpg';
            $directory = 'agents';
            $filename = sprintf('%s/%s.%s', $directory, $slug, $extension);

            Storage::disk('assets')->makeDirectory($directory);
            Storage::disk('assets')->put($filename, $response->body());

            return $filename;
        } catch (Throwable $exception) {
            Log::warning('Failed to store agent photo.', [
                'url' => $url,
                'slug' => $slug,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get configured Google Client
     */
    private function getGoogleClient(): Client
    {
        $client = new Client();

        // Try to use Base64 encoded credentials from environment variable (for production)
        $credentialsBase64 = config('services.google_sheets_agents.credentials_base64');

        if (! blank($credentialsBase64)) {
            $credentialsJson = base64_decode($credentialsBase64);
            $credentialsArray = json_decode($credentialsJson, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \InvalidArgumentException('Invalid Google credentials Base64 encoding.');
            }

            $client->setAuthConfig($credentialsArray);
        } else {
            // Fallback to file-based credentials (for local development)
            $credentialsPath = config('services.google_sheets_agents.credentials_path');

            if (blank($credentialsPath) || ! file_exists($credentialsPath)) {
                throw new \InvalidArgumentException('Google API credentials not found. Set either GOOGLE_CREDENTIALS_BASE64 or provide credentials file.');
            }

            $client->setAuthConfig($credentialsPath);
        }

        $client->addScope(Sheets::SPREADSHEETS_READONLY);
        $client->setApplicationName(config('app.name', 'Livrichy'));

        return $client;
    }
}

