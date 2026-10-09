
@php
    use App\Support\Format;

    $quantity = (int) ($row['quantity'] ?? 0);
@endphp

<span class="font-medium tabular-nums {{ $quantity < 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-700 dark:text-emerald-400' }}">
    {{ $quantity > 0 ? '+' : '' }}{{ Format::number($quantity) }}
</span>
