<?php

namespace App\Tags;

use Illuminate\Support\Str;
use Statamic\Facades\Entry;
use Statamic\Tags\Tags;

class PropertyFilterOptions extends Tags
{
    /**
     * Return unique communities pulled from property entries.
     *
     * @return array<int, array<string, string>>
     */
    public function communities(): array
    {
        $communities = Entry::query()
            ->where('collection', 'properties')
            ->where('published', true)
            ->get()
            ->map(fn ($entry) => $entry->get('community'))
            ->filter(fn ($value) => filled($value))
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return $communities
            ->map(fn ($value) => [
                'value' => $value,
                'label' => $value,
            ])
            ->all();
    }

    /**
     * Return allowed property categories.
     *
     * @return array<int, array<string, string>>
     */
    public function categories(): array
    {
        $allowed = [
            'apartment' => 'Apartment',
            'townhouse' => 'Townhouse',
            'villa' => 'Villa',
            'duplex' => 'Duplex',
        ];

        $values = Entry::query()
            ->where('collection', 'properties')
            ->where('published', true)
            ->get()
            ->flatMap(function ($entry) {
                $categories = $entry->get('categories');

                if (is_string($categories)) {
                    return [$categories];
                }

                if (is_array($categories)) {
                    return $categories;
                }

                return [];
            })
            ->map(fn ($value) => strtolower((string) $value))
            ->filter(fn ($value) => isset($allowed[$value]))
            ->unique()
            ->sort()
            ->values();

        return $values
            ->map(fn ($value) => [
                'value' => $value,
                'label' => $allowed[$value] ?? Str::headline($value),
            ])
            ->all();
    }
}
