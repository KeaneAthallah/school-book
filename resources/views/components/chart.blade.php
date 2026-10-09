@props([
    'config' => [],
    'height' => '16rem',
])

{{--
    The config is json_encoded and then HTML-escaped by Blade's {{ }}. The
    browser decodes the attribute before Alpine evaluates it, so the expression
    arrives as valid JavaScript while any quotes, angle brackets or apostrophes
    stay safely contained inside the attribute.

    `height` is a raw CSS length (not a Tailwind class) so per-chart heights can
    be computed at runtime without depending on the class scanner.
--}}
<div {{ $attributes->merge(['class' => 'w-full']) }} style="height: {{ $height }}">
    <canvas
        x-data="chart({{ json_encode($config) }})"
        x-ref="canvas"
        aria-hidden="true"
    ></canvas>
</div>
