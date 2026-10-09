@props([
    'name' => 'dashboard',
    'class' => 'h-5 w-5',
])

@php
    $paths = config('icons');
    $markup = $paths[$name] ?? ($paths['dashboard'] ?? '');
@endphp

<svg
    {{ $attributes->merge(['class' => $class])->except('name') }}
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="1.8"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
    focusable="false"
>{!! $markup !!}</svg>
