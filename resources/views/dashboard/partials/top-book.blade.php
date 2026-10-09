
<div>
    <p class="font-medium text-ink">{{ $row['title'] }}</p>
    <p class="text-xs text-ink-soft">
        {{ $row['code'] }} · fisik {{ number_format((float) $row['quantity'], 0, ',', '.') }}
        · reservasi {{ number_format((float) $row['reserved'], 0, ',', '.') }}
    </p>
</div>
