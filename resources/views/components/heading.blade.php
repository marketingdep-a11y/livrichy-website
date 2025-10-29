@props([
    'size' => 1,
    'color' => 'gray',
    'as' => 'p',
    'base' => 'font-bold font-heading [&>i]:font-sans [&>i]:font-medium',
    'variants' => [
        'size' => [
            1 => 'text-5xl md:text-6xl lg:text-7xl lg:leading-[80px]',
            2 => 'text-4xl md:text-5xl leading-10 md:leading-[56px]',
            3 => 'text-2xl lg:text-3xl leading-10',
            4 => 'text-[28px] leading-10',
            5 => 'text-2xl leading-8',
            6 => 'text-xl leading-6',
        ],
        'color' => [
            'gray' => 'text-dark-950',
            'brand' => 'text-brand-800',
            'red' => 'text-red-800',
            'white' => 'text-white',
        ],
    ]
])

@php
$classes = $base . ' ' . $variants['size'][$size] . ' ' . $variants['color'][$color];
@endphp

<{{ $as }} {{ $attributes->twMerge(["class" => $classes]) }}>
    {{ $slot }}
</{{ $as }}>
