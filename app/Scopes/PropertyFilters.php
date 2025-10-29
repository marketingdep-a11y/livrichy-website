<?php

namespace App\Scopes;

use Statamic\Query\Scopes\Scope;
use Statamic\Facades\Entry;

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

        if (request()->has('location')) {
            $city = Entry::query()->where('slug', request()->query('location'))->first();

            $query->where('city', $city->id);
        }

        if (request()->filled('community')) {
            $query->where('community', 'like', '%' . request()->query('community') . '%');
        }

        if (request()->has('floor_area')) {
            $query->where('property_features->0->property_size', '>=', request()->query('floor_area'));
        }

        if (request()->has('status')) {
            $query->where('status', 'published')
                ->where('property_status', request()->status);
        }

        if (request()->has('categories')) {
            $query->whereTaxonomyIn(request()->categories);
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

        if (request()->segment(1) == 'categories' && request()->segment(2)) {
            $query->whereTaxonomy('categories::' . request()->segment(2));
        }

        if (request()->segment(1) == 'cities' && request()->segment(2)) {
            $city = Entry::query()->where('slug', request()->segment(2))->first();

            $query->where('city', $city->id);
        }
    }
}
