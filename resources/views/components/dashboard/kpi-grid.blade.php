@props(['kpis' => []])

@php
    use App\Support\Format;
@endphp

<div {{ $attributes->merge(['class' => 'grid grid-cols-1 gap-3 min-[400px]:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4']) }}>
    @foreach ($kpis as $kpi)
        <x-stat-card
            :label="$kpi['label']"
            :icon="$kpi['icon']"
            :icon-tone="$kpi['iconTone']"
            :tone="$kpi['tone']"
            :value="$kpi['format'] === 'currency' ? Format::idr($kpi['value']) : Format::number($kpi['value'])"
            :delta="$kpi['delta']"
            :good-when-down="$kpi['goodWhenDown']"
            :hint="$kpi['hint']"
        />
    @endforeach
</div>
