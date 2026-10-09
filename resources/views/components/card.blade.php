@props(['class' => ''])

<section {{ $attributes->merge(['class' => 'rounded-card border border-line bg-panel shadow-card ' . $class]) }}>
    {{ $slot }}
</section>
