<?php

namespace App\Tags;

use Statamic\Tags\Tags;
use Statamic\Facades\Entry;
use Illuminate\Support\Str;

class TotalProperties extends Tags
{
    /**
     * The {{ total_properties }} tag.
     *
     * @return string|array
     */
    public function index()
    {
        $city = $this->params->get('city');
        $categories = $this->params->get('categories');
        $agent = $this->params->get('agent');

        $total = Entry::query()
                        ->where('collection', 'properties')
                        ->when($city, fn($query) => $query->where('city', $city))
                        ->when($categories, fn($query) => $query->where('categories', 'LIKE', '%' . $categories . '%'))
                        ->when($agent, fn($query) => $query->where('agent', $agent) )
                        ->count();

        return ($total > 99) ? '100+ properties' : $total . ' ' . Str::plural('property', $total);
    }
}