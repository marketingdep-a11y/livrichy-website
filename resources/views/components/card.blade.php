@props([
    'direction' => 'horizontal',
    'color' => 'white',
    'ring' => 'none',
    'as' => 'div',
    'base' => [
        'flex justify-between rounded-2xl p-6',
        'transition-all duration-300 ease-in-out',
    ],
    'variants' => [
        'direction' => [
            'vertical' => 'flex-col',
            'horizontal' => 'flex-row items-center',
        ],
        'color' => [
            'white' => 'bg-white',
            'dark' => 'bg-dark-90',
            'brand' => 'bg-brand-100',
        ],
        'ring' => [
            'gray' => 'ring-1 ring-dark-100 hover:ring-brand-950 hover:ring-2',
            'brand' => 'ring-1 ring-brand-100 hover:ring-2 hover:ring-brand-950',
            'transparent' => 'ring-0 hover:ring-2 hover:ring-brand-950',
            'none' => 'ring-0'
        ],
    ]
])

@php
$classes = implode(' ', $base) . ' ' . $variants['direction'][$direction] . ' ' . $variants['ring'][$ring] . ' ' . $variants['color'][$color];
@endphp

<{{ $as }} {{ $attributes->twMerge(["class" => $classes]) }}>
    {{ $slot }}
</{{ $as }}>
