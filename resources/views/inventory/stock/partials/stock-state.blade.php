
@if ($row['low'])
    <x-status-badge value="open" label="Menipis" />
@elseif ((int) $row['available_quantity'] <= 0)
    <x-status-badge value="cancelled" label="Habis" />
@else
    <x-status-badge value="active" label="Aman" />
@endif
