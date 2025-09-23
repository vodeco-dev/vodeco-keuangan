@props([
    'class' => '',
    'alt' => 'Logo Vodeco',
])

@php
    $imageClasses = trim($class);
    $wrapperAttributes = $attributes->except(['class', 'alt'])->merge(['class' => 'inline-block']);
@endphp

<span {{ $wrapperAttributes }}>
    <img src="{{ asset('vodeco.webp') }}" alt="{{ $alt }}" class="{{ trim('block dark:hidden ' . $imageClasses) }}">
    <img src="{{ asset('logo-vodeco-dark-mode.png') }}" alt="{{ $alt }}" class="{{ trim('hidden dark:block ' . $imageClasses) }}">
</span>
