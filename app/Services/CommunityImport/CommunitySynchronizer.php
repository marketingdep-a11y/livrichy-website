<?php

namespace App\Services\CommunityImport;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Statamic\Contracts\Entries\Entry as EntryContract;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;

class CommunitySynchronizer
{
    /**
     * Synchronise Statamic community entries with the distinct communities present on published properties.
     *
     * @return array<string, int>
     */
    public function sync(): array
    {
        $siteHandle = Site::default()->handle();

        $communities = $this->collectCommunityCounts($siteHandle);
        $existing = $this->collectExistingCommunities($siteHandle);

        $report = [
            'discovered' => $communities->count(),
            'created' => 0,
            'updated' => 0,
            'unpublished' => 0,
        ];

        foreach ($communities as $name => $count) {
            $normalizedKey = $this->normalizeKey($name);

            /** @var EntryContract|null $entry */
            $entry = $existing->pull($normalizedKey);

            $isNew = false;

            if ($entry === null) {
                $entry = Entry::make()
                    ->collection('communities')
                    ->locale($siteHandle)
                    ->slug($this->generateUniqueSlug($name, $siteHandle));

                $isNew = true;
                $report['created']++;
            } else {
                $report['updated']++;
            }

            // For new entries: set all fields
            // For existing entries: only update listings_total and import_key
            // This preserves manual changes like custom title, featured_image, content, etc.
            if ($isNew) {
                $entry->set('title', $name);
                $entry->set('import_key', $name);
            } else {
                // Only update import_key for matching, keep title as is
                $entry->set('import_key', $name);
            }

            $entry->set('listings_total', $count);
            $entry->published(true);
            $entry->save();
        }

        // Any remaining entries in the collection were not present in the latest import.
        $existing->each(function (EntryContract $entry) use (&$report) {
            if ($entry->published()) {
                $entry->published(false);
                $entry->set('listings_total', 0);
                $entry->save();

                $report['unpublished']++;
            }
        });

        return $report;
    }

    /**
     * @return Collection<string, EntryContract>
     */
    private function collectExistingCommunities(string $siteHandle): Collection
    {
        return Entry::query()
            ->where('collection', 'communities')
            ->where('site', $siteHandle)
            ->get()
            ->keyBy(function (EntryContract $entry) {
                $importKey = $entry->get('import_key') ?? $entry->get('title');

                return $this->normalizeKey($importKey);
            });
    }

    /**
     * @return Collection<string, int>
     */
    private function collectCommunityCounts(string $siteHandle): Collection
    {
        return Entry::query()
            ->where('collection', 'properties')
            ->where('site', $siteHandle)
            ->where('published', true)
            ->get()
            ->map(fn (EntryContract $entry) => $entry->get('community'))
            ->filter(fn ($value) => filled($value))
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->countBy();
    }

    private function normalizeKey(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        return mb_strtolower(trim($value));
    }

    private function generateUniqueSlug(string $name, string $siteHandle): string
    {
        $base = Str::slug($name);

        if ($base === '') {
            $base = 'community';
        }

        $slug = $base;
        $suffix = 2;

        while ($this->slugExists($slug, $siteHandle)) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    private function slugExists(string $slug, string $siteHandle): bool
    {
        return Entry::query()
            ->where('collection', 'communities')
            ->where('site', $siteHandle)
            ->where('slug', $slug)
            ->exists();
    }
}
