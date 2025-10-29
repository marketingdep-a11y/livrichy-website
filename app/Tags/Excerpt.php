<?php

namespace App\Tags;

use Illuminate\Support\Str;
use Statamic\Tags\Tags;

class Excerpt extends Tags
{
    protected static $handle = 'excerpt';

    public function wildcard($key)
    {
        $post = collect($this->params->get('value'))
                ->first(function ($value) {
                    return $value['type'] === 'text';
                });

        // Strip tags
        $post = strip_tags($post['text']);

        $post = Str::words($post, 30, '...');

        return $post;
    }
}
