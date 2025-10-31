<?php

namespace App\Tags;

use Statamic\Tags\Tags;

class Set extends Tags
{
    public function index()
    {
        // Исправление: используем all() для преобразования объектов в массивы
        return array_merge($this->context->all(), $this->params->all());
    }
}

