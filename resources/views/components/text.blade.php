@props([
    'size' => 'base',
    'color' => 'dark',
    'weight' => 300,
    'font' => 'body',
    'as' => 'p',
    'variants' => [
        'size' => [
            'xs' => 'text-xs leading-[20px]',
            'sm' => 'text-sm leading-[22px]',
            'base' => 'text-base leading-[24px]',
            'lg' => 'text-lg leading-[26px]', 
            'xl' => 'text-xl leading-[28px]',
            '2xl' => 'text-2xl leading-8' 
        ],
        'font' => [
            'body' => 'font-body',
            'heading' => 'font-heading',
            'sans' => 'font-sans',
        ],
        'weight' => [
            300 => 'font-light',
            400 => 'font-normal',
            500 => 'font-medium',
            700 => 'font-bold',
        ],
        'color' => [
            'dark' => 'text-dark-950',
            'dark-800' => 'text-dark-800',
            'light' => 'text-dark-600',
            'white' => 'text-white',
            'brand' => 'text-brand-950',
        ],
    ]
])

@php
    $classes = $variants['font'][$font]. ' ' . $variants['size'][$size] . ' ' . $variants['color'][$color] . ' ' . $variants['weight'][$weight];
@endphp

<{{ $as }} {{ $attributes->twMerge(["class" => $classes]) }}>
{{ $slot }}
</{{ $as }}>
