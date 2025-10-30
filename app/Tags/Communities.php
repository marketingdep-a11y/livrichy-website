<?php

namespace App\Tags;

use Illuminate\Support\Collection;
use Statamic\Facades\Entry;
use Statamic\Support\Str;
use Statamic\Tags\Tags;

class Communities extends Tags
{
    /**
     * Return top communities aggregated from properties.
     */
    public function top(): Collection
    {
        $limit = (int) $this->params->get('limit', 3);

        $entries = Entry::query()
            ->where('collection', 'properties')
            ->where('status', 'published')
            ->get();

        return $entries
            ->filter(fn ($entry) => filled($entry->get('community')))
            ->groupBy(fn ($entry) => trim((string) $entry->get('community')))
            ->map(function (Collection $group, string $community) {
                $count = $group->count();

                $featured = $group
                    ->sortByDesc(fn ($entry) => optional($entry->date())->timestamp ?? $entry->updatedAt() ?? 0)
                    ->first();

                $featuredImage = $featured?->get('featured_image');

                return [
                    'id' => $community,
                    'title' => $community,
                    'slug' => Str::slug($community),
                    'count' => $count,
                    'total_text' => $this->formatTotal($count),
                    'featured_image' => $featuredImage,
                    'url' => url('/properties?community=' . urlencode($community)),
                ];
            })
            ->sortByDesc('count')
            ->take(max($limit, 1))
            ->values();
    }

    private function formatTotal(int $count): string
    {
        if ($count > 99) {
            return '100+ properties';
        }

        return $count . ' ' . Str::plural('property', $count);
    }
}
