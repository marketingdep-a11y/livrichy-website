<?php

namespace App\Tags;

use Illuminate\Support\Collection;
use Statamic\Facades\Entry;
use Statamic\Tags\Tags;

class FeaturedListings extends Tags
{
    /**
     * Fetch a blended set of rent and sale listings for the hero slider.
     *
     * @return \Illuminate\Support\Collection<int, \Statamic\Contracts\Entries\Entry>
     */
    public function hero(): Collection
    {
        $rentLimit = (int) $this->params->get('rent_limit', 2);
        $saleLimit = (int) $this->params->get('sale_limit', 2);

        $rentListings = $this->query()
            ->where('data->property_status', 'rent')
            ->inRandomOrder()
            ->limit($rentLimit)
            ->get();

        $saleListings = $this->query()
            ->where('data->property_status', 'sale')
            ->inRandomOrder()
            ->limit($saleLimit)
            ->get();

        return $rentListings
            ->merge($saleListings)
            ->shuffle()
            ->values();
    }

    private function query()
    {
        return Entry::query()
            ->where('collection', 'properties')
            ->where('status', 'published');
    }
}
