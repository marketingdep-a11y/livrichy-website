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
            $query->where('price', '>=', request()->integer('min_price'));
        }

        if (request()->filled('max_price')) {
            $query->where('price', '<=', request()->integer('max_price'));
        }

        if (request()->filled('location')) {
            $query->where('community', request()->query('location'));
        }

        if (request()->has('floor_area')) {
            $query->where('property_features->0->property_size', '>=', request()->query('floor_area'));
        }

        if (request()->filled('status')) {
            $query->where('property_status', request()->query('status'));
        }

        if (request()->filled('categories')) {
            $values = (array) request()->input('categories', []);

            foreach ($values as $value) {
                $query->where(function ($subQuery) use ($value) {
                    $subQuery->where('data->categories', 'like', '%"'.$value.'"%');
                });
            }
        }

        if (request()->has('bedrooms')) {
            $query->where('property_features->0->bedrooms', '>=', request()->bedrooms);
        }

        if (request()->has('bathrooms')) {
            $query->where('property_features->0->bathrooms', '>=', request()->bathrooms);
        }

        if (request()->has('min_year')) {
            $query->where('year_build', '>=', request()->min_year);
        }

        if (request()->has('q')) {
            $query->where('title', 'like', '%' . request()->q . '%');
        }

        // Legacy routes for categories/cities are not used in the import workflow.
    }
}
