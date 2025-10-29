@props([
    'variant' => 'primary',
    'type' => 'button',
    'url' => null,
    'base' => [
        'px-8 py-3 rounded-2xl font-bold font-heading',
        'inline-flex items-center justify-center',
        'transition-all duration-300 ease-in-out '
    ],
    'variants' => [
        'primary' => 'text-white bg-brand-950 hover:bg-dark-90 hover:text-brand-950 ring-brand-950 ring-2',
        'secondary' => 'bg-white text-brand-950 hover:text-white hover:bg-brand-950',
        'outline' => 'text-brand-950 bg-dark-50 ring-2 ring-brand-200 hover:ring-brand-950',
        'outline-fill' => 'text-brand-950 bg-dark-90 ring-2 ring-brand-200 hover:ring-brand-950',
    ]
])

@php
    $classes = implode(' ', $base) . ' ' . $variants[$variant];
@endphp

@if($url)
    <a href="{{ $url }}" {{ $attributes->twMerge(["class" => $classes]) }}>
        {{ $slot }}
    </a>
    @else
    <button type="{{ $type }}" {{ $attributes->twMerge(["class" => $classes]) }}>
        {{ $slot }}
    </button>
@endif
