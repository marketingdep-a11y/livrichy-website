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
        $values = $this->collectCountsFromArrayField('categories')
            ->filter(fn ($value) => isset($allowed[$value]))
            ->values();

        return $values
            ->map(fn ($value) => [
                'value' => $value,
                'label' => $allowed[$value] ?? Str::headline($value),
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function bedrooms(): array
    {
        return $this->collectNumericValues('bedrooms_count')
            ->map(function ($value) {
                if ($value === 0) {
                    return ['value' => 'studio', 'label' => 'Studio'];
                }

                return ['value' => (string) $value, 'label' => (string) $value];
            })
            ->all();
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function bathrooms(): array
    {
        return $this->collectNumericValues('bathrooms_count')
            ->map(fn ($value) => ['value' => (string) $value, 'label' => (string) $value])
            ->all();
    }

    private function collectNumericValues(string $handle)
    {
        return Entry::query()
            ->where('collection', 'properties')
            ->where('published', true)
            ->get()
            ->map(function ($entry) use ($handle) {
                $value = $entry->get($handle);

                if ($value === null) {
                    $features = $entry->get('property_features');

                    if (is_array($features)) {
                        foreach ($features as $feature) {
                            if (($feature['type'] ?? null) === str_replace('_count', '', $handle)) {
                                $value = $feature['description'] ?? null;
                                break;
                            }
                        }
                    }
                }

                if (is_string($value)) {
                    $lower = strtolower(trim($value));
                    if ($lower === 'studio' || $lower === '0') {
                        return 0;
                    }
                }

                if (is_numeric($value)) {
                    return (int) $value;
                }

                return null;
            })
            ->filter(fn ($value) => $value !== null && $value >= 0)
            ->unique()
            ->sort()
            ->values();
    }

    private function collectCountsFromArrayField(string $handle)
    {
        return Entry::query()
            ->where('collection', 'properties')
            ->where('published', true)
            ->get()
            ->flatMap(function ($entry) use ($handle) {
                $values = $entry->get($handle);

                if (is_string($values)) {
                    return [strtolower($values)];
                }

                if (is_array($values)) {
                    return collect($values)
                        ->map(fn ($value) => strtolower((string) $value));
                }

                return [];
            })
            ->filter()
            ->unique()
            ->sort()
            ->values();
    }
}
