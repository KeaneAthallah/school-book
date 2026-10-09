
@php
    $new = data_get($row, 'changes.new');
@endphp

@if ($new)
    <code class="text-xs break-all text-ink-soft">{{ json_encode($new, JSON_UNESCAPED_UNICODE) }}</code>
@else
    <span class="text-ink-muted">-</span>
@endif
