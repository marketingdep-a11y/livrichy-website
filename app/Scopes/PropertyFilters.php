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
            $query->where('data->price', '>=', request()->integer('min_price'));
        }

        if (request()->filled('max_price')) {
            $query->where('data->price', '<=', request()->integer('max_price'));
        }

        if (request()->filled('location')) {
            $query->where('data->community', request()->query('location'));
        }

        if (request()->filled('floor_area')) {
            $query->where('data->property_size', '>=', request()->integer('floor_area'));
        }

        if (request()->filled('status')) {
            $query->where('data->property_status', request()->query('status'));
        }

        if (request()->filled('categories')) {
            $values = (array) request()->input('categories', []);

            $query->where(function ($subQuery) use ($values) {
                foreach ($values as $value) {
                    $subQuery->orWhere('data->categories', 'like', '%"'.$value.'"%');
                }
            });
        }

        if (request()->filled('bedrooms')) {
            $query->where('data->bedrooms_count', '>=', request()->integer('bedrooms'));
        }

        if (request()->filled('bathrooms')) {
            $query->where('data->bathrooms_count', '>=', request()->integer('bathrooms'));
        }

        if (request()->filled('min_year')) {
            $query->where('data->year_build', '>=', request()->integer('min_year'));
        }

        if (request()->has('q')) {
            $query->where('data->title', 'like', '%' . request()->q . '%');
        }

        // Legacy routes for categories/cities are not used in the import workflow.
    }
}
