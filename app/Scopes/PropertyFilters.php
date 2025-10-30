<?php

namespace App\Scopes;

use Statamic\Query\Scopes\Scope;

class PropertyFilters extends Scope
{
    /**
     * Apply the scope.
     *
     * @param \Statamic\Query\Builder $query
     * @param array $values
     * @return void
     */
    public function apply($query, $values)
    {
        if (request()->filled('min_price')) {
            $query->whereRaw("CAST(json_extract(data, '$.price') AS REAL) >= ?", [request()->float('min_price')]);
        }

        if (request()->filled('max_price')) {
            $query->whereRaw("CAST(json_extract(data, '$.price') AS REAL) <= ?", [request()->float('max_price')]);
        }

        if (request()->filled('location')) {
            $query->whereRaw("json_extract(data, '$.community') = ?", [request()->query('location')]);
        }

        if (request()->filled('floor_area')) {
            $range = request()->input('floor_area');

            if (str_contains($range, '+')) {
                $min = (float) rtrim($range, '+');

                $query->whereRaw("CAST(json_extract(data, '$.property_size') AS REAL) >= ?", [$min]);
            } elseif (preg_match('/^(\d+)-(\d+)$/', $range, $matches)) {
                $min = (float) $matches[1];
                $max = (float) $matches[2];

                $query->whereRaw("CAST(json_extract(data, '$.property_size') AS REAL) BETWEEN ? AND ?", [$min, $max]);
            }
        }

        if (request()->filled('status')) {
            $status = request()->query('status');

            $query->where(function ($subQuery) use ($status) {
                $subQuery
                    ->whereRaw("json_extract(data, '$.property_status') = ?", [$status])
                    ->orWhereRaw("json_extract(data, '$.property_status.value') = ?", [$status]);
            });
        }

        if (request()->filled('categories')) {
            $values = (array) request()->input('categories', []);

            $query->where(function ($subQuery) use ($values) {
                foreach ($values as $value) {
                    $subQuery->orWhereRaw("json_extract(data, '$.categories') LIKE ?", ['%"' . $value . '"%']);
                }
            });
        }

        if (request()->filled('bedrooms')) {
            $bedrooms = request()->input('bedrooms');

            if ($bedrooms === 'studio') {
                $query->whereRaw("CAST(json_extract(data, '$.bedrooms_count') AS INTEGER) = 0");
            } else {
                $count = (int) $bedrooms;

                $query->whereRaw("CAST(json_extract(data, '$.bedrooms_count') AS INTEGER) >= ?", [$count]);
            }
        }

        if (request()->filled('bathrooms')) {
            $count = (int) request()->input('bathrooms');

            $query->whereRaw("CAST(json_extract(data, '$.bathrooms_count') AS INTEGER) >= ?", [$count]);
        }

        if (request()->filled('min_year')) {
            $query->whereRaw("CAST(json_extract(data, '$.year_build') AS INTEGER) >= ?", [request()->integer('min_year')]);
        }

        if (request()->filled('q')) {
            $query->where('data->title', 'like', '%' . request()->q . '%');
        }

        // Legacy routes for categories/cities are not used in the import workflow.
    }
}
