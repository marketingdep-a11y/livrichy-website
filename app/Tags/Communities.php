<?php

namespace App\Tags;

use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Entry as EntryContract;
use Statamic\Facades\Entry;
use Statamic\Support\Str;
use Statamic\Tags\Tags;

class Communities extends Tags
{
    /**
     * Return the top communities. Prefers synced community entries and falls back to aggregating properties.
     */
    public function top(): Collection
    {
        $limit = max((int) $this->params->get('limit', 3), 1);

        $communities = Entry::query()
            ->where('collection', 'communities')
            ->where('published', true)
            ->get()
            ->map(fn (EntryContract $entry) => $this->mapCommunityEntry($entry))
            ->filter();

        if ($communities->isEmpty()) {
            return $this->fallbackFromProperties($limit);
        }

        return $communities
            ->sortByDesc('count')
            ->take($limit)
            ->values();
    }

    private function mapCommunityEntry(EntryContract $entry): ?array
    {
        $title = trim((string) ($entry->get('title') ?? ''));

        if ($title === '') {
            return null;
        }

        $importKey = trim((string) ($entry->get('import_key') ?? $title));
        $count = (int) ($entry->get('listings_total') ?? 0);
        $url = $entry->url() ?? url('/properties?community=' . urlencode($importKey));

        // Get featured_image from community, or fallback to first property photo
        $featuredImage = $entry->get('featured_image');
        
        if (!$featuredImage) {
            $featuredImage = $this->getFallbackImage($importKey);
        }

        return [
            'id' => $importKey,
            'import_key' => $importKey,
            'title' => $title,
            'slug' => $entry->slug(),
            'count' => $count,
            'total_text' => $this->formatTotal($count),
            'featured_image' => $featuredImage,
            'url' => $url,
        ];
    }

    private function fallbackFromProperties(int $limit): Collection
    {
        $entries = Entry::query()
            ->where('collection', 'properties')
            ->where('published', true)
            ->get();

        return $entries
            ->filter(fn ($entry) => filled($entry->get('community')))
            ->groupBy(fn ($entry) => trim((string) $entry->get('community')))
            ->map(function (Collection $group, string $community) {
                $count = $group->count();

                $featured = $group
                    ->sortByDesc(function ($entry) {
                        $dateTimestamp = optional($entry->date())->timestamp;
                        $updatedTimestamp = method_exists($entry, 'lastModified')
                            ? optional($entry->lastModified())->timestamp
                            : null;

                        return $dateTimestamp ?? $updatedTimestamp ?? 0;
                    })
                    ->first();

                $featuredImage = $featured?->get('featured_image');

                return [
                    'id' => $community,
                    'import_key' => $community,
                    'title' => $community,
                    'slug' => Str::slug($community),
                    'count' => $count,
                    'total_text' => $this->formatTotal($count),
                    'featured_image' => $featuredImage,
                    'url' => url('/properties?community=' . urlencode($community)),
                ];
            })
            ->sortByDesc('count')
            ->take($limit)
            ->values();
    }

    private function formatTotal(int $count): string
    {
        if ($count > 99) {
            return '100+ properties';
        }

        return $count . ' ' . Str::plural('property', $count);
    }

    /**
     * Get fallback image from first property in community
     */
    private function getFallbackImage(string $importKey): mixed
    {
        $property = Entry::query()
            ->where('collection', 'properties')
            ->where('published', true)
            ->where('data->community', $importKey)
            ->first();

        if (!$property) {
            return null;
        }

        // Try featured_image first
        $image = $property->get('featured_image');
        
        if ($image) {
            return $image;
        }

        // Fallback to first photo_link
        $photoLinks = $property->get('photo_links');
        
        if (is_array($photoLinks) && !empty($photoLinks)) {
            return $photoLinks[0];
        }

        return null;
    }
}
