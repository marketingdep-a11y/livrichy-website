<?php

namespace App\Tags;

use Illuminate\Support\Str;
use Statamic\Facades\Entry;
use Statamic\Tags\Tags;

class PropertyFilterOptions extends Tags
{
    /**
     * Return unique communities pulled from the synced communities collection.
     *
     * @return array<int, array<string, string>>
     */
    public function communities(): array
    {
        $communities = Entry::query()
            ->where('collection', 'communities')
            ->where('published', true)
            ->orderBy('title')
            ->get()
            ->map(function ($entry) {
                $value = trim((string) ($entry->get('import_key') ?? $entry->get('title')));
                $label = trim((string) ($entry->get('title') ?? $value));

                if ($value === '') {
                    return null;
                }

                return [
                    'value' => $value,
                    'label' => $label !== '' ? $label : $value,
                ];
            })
            ->filter();

        if ($communities->isEmpty()) {
            $communities = $this->communitiesFromProperties();
        }

        return $communities
            ->unique(fn ($item) => $item['value'])
            ->values()
            ->all();
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, string>>
     */
    private function communitiesFromProperties()
    {
        return Entry::query()
            ->where('collection', 'properties')
            ->where('published', true)
            ->get()
            ->map(fn ($entry) => $entry->get('community'))
            ->filter(fn ($value) => filled($value))
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->map(fn ($value) => [
                'value' => $value,
                'label' => $value,
            ]);
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

    /**
     * @return array<int, array<string, mixed>>
     */
    public function floorArea(): array
    {
        return collect([
            ['value' => '0-500', 'label' => 'Up to 500'],
            ['value' => '500-1000', 'label' => '500 - 1,000'],
            ['value' => '1000-1500', 'label' => '1,000 - 1,500'],
            ['value' => '1500-2000', 'label' => '1,500 - 2,000'],
            ['value' => '2000-2500', 'label' => '2,000 - 2,500'],
            ['value' => '2500-3000', 'label' => '2,500 - 3,000'],
            ['value' => '3000+', 'label' => '3,000+'],
        ])->all();
    }

    private function collectNumericValues(string $handle)
    {
        $typeHandle = str_replace('_count', '', $handle);

        return Entry::query()
            ->where('collection', 'properties')
            ->where('published', true)
            ->get()
            ->map(function ($entry) use ($handle, $typeHandle) {
                $value = $entry->get($handle);

                if ($value === null) {
                    $features = $entry->get('property_features');

                    if (is_array($features)) {
                        foreach ($features as $feature) {
                            if (($feature['type'] ?? null) === $typeHandle) {
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

                    if (is_numeric($value)) {
                        return $handle === 'property_size'
                            ? (float) $value
                            : (int) $value;
                    }
                }

                if (is_numeric($value)) {
                    return $handle === 'property_size'
                        ? (float) $value
                        : (int) $value;
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
