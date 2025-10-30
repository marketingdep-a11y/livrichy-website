<?php

namespace App\Services\AgentImport;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Throwable;

class AgentImporter
{
    /**
     * @return array<string, int>
     */
    public function sync(?string $overrideUrl = null): array
    {
        $url = $overrideUrl ?? config('services.crm_agents.url');

        if (blank($url)) {
            throw new \InvalidArgumentException('CRM agents endpoint URL is not configured.');
        }

        $records = $this->fetchAll($url);

        return $this->persist($records);
    }

    /**
     * @param  array<int, array<string, mixed>>  $records
     * @return array<string, int>
     */
    private function persist(array $records): array
    {
        $allowedDepartments = collect(config('services.crm_agents.departments', []))
            ->map(fn ($department) => (int) $department)
            ->filter()
            ->values();

        $report = [
            'fetched' => count($records),
            'eligible' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped_inactive' => 0,
            'skipped_department' => 0,
            'skipped_missing_name' => 0,
            'skipped_errors' => 0,
        ];

        foreach ($records as $record) {
            if (! $this->isActive($record)) {
                $report['skipped_inactive']++;

                continue;
            }

            if (! $this->isInAllowedDepartment($record, $allowedDepartments)) {
                $report['skipped_department']++;

                continue;
            }

            $title = $this->resolveName($record);

            if ($title === null) {
                $report['skipped_missing_name']++;

                continue;
            }

            $report['eligible']++;

            try {
                $created = $this->upsertAgent($record, $title);
            } catch (Throwable $exception) {
                $report['skipped_errors']++;

                Log::error('Unable to persist CRM agent.', [
                    'id' => Arr::get($record, 'ID'),
                    'message' => $exception->getMessage(),
                ]);

                continue;
            }

            if ($created) {
                $report['created']++;
            } else {
                $report['updated']++;
            }
        }

        return $report;
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function upsertAgent(array $record, string $title): bool
    {
        $externalId = (string) Arr::get($record, 'ID');

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
                ->slug($this->generateUniqueSlug($title, $externalId));
        }

        $entry->set('title', $title);
        $entry->set('external_id', $externalId);

        if ($position = $this->resolvePosition($record)) {
            $entry->set('position', $position);
        }

        if ($photoPath = $this->storePhoto(Arr::get($record, 'PERSONAL_PHOTO'), $entry->slug() ?? $this->generateUniqueSlug($title, $externalId))) {
            $entry->set('image', $photoPath);
        }

        $entry->set('social_media', $this->buildSocialLinks($record));

        $entry->published(true);

        $entry->save();

        return $isNew;
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array<int, array<string, string>>
     */
    private function buildSocialLinks(array $record): array
    {
        $links = collect();

        if ($email = Arr::get($record, 'EMAIL')) {
            $links->push([
                'id' => Str::random(8),
                'name' => 'email',
                'link' => $email,
            ]);
        }

        $phone = Arr::get($record, 'PERSONAL_MOBILE') ?: Arr::get($record, 'WORK_PHONE');

        if ($phone) {
            $links->push([
                'id' => Str::random(8),
                'name' => 'telephone',
                'link' => $phone,
            ]);
        }

        return $links->values()->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchAll(string $url): array
    {
        $timeout = (int) config('services.crm_agents.timeout', 10);

        $records = [];
        $start = null;

        do {
            $query = $start !== null ? ['start' => $start] : [];

            $response = Http::timeout($timeout)
                ->retry(3, 200)
                ->acceptJson()
                ->get($url, $query);

            if (! $response->successful()) {
                Log::warning('Failed to fetch CRM agents page.', [
                    'url' => $url,
                    'query' => $query,
                    'status' => $response->status(),
                ]);

                break;
            }

            $payload = $response->json();

            $pageResults = Arr::get($payload, 'result', []);

            if (is_array($pageResults)) {
                $records = array_merge($records, $pageResults);
            }

            $start = Arr::get($payload, 'next');
        } while ($start !== null);

        return $records;
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function isActive(array $record): bool
    {
        return Arr::get($record, 'ACTIVE') === true;
    }

    /**
     * @param  array<string, mixed>  $record
     * @param  Collection<int, int>  $allowedDepartments
     */
    private function isInAllowedDepartment(array $record, Collection $allowedDepartments): bool
    {
        if ($allowedDepartments->isEmpty()) {
            return true;
        }

        $departments = collect(Arr::get($record, 'UF_DEPARTMENT', []))
            ->map(fn ($department) => (int) $department)
            ->filter();

        return $departments->intersect($allowedDepartments)->isNotEmpty();
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function resolveName(array $record): ?string
    {
        $parts = [
            trim((string) Arr::get($record, 'NAME')),
            trim((string) Arr::get($record, 'LAST_NAME')),
        ];

        $name = trim(implode(' ', array_filter($parts)));

        return $name === '' ? null : $name;
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function resolvePosition(array $record): ?string
    {
        $position = Arr::get($record, 'WORK_POSITION') ?: Arr::get($record, 'TITLE');

        if ($position === null) {
            return null;
        }

        $position = trim((string) $position);

        return $position === '' ? null : $position;
    }

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
        } catch (Throwable) {
            return null;
        }
    }
}
