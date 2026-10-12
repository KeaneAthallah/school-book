{{--
    Orders table. Expects: $rows, optional $empty and $columns
--}}
<x-data-table
    :empty="$empty ?? 'Belum ada pesanan.'"
    empty-icon="cart"
    :rows="$rows"
    :columns="$columns ?? [
        ['key' => 'number', 'label' => 'No. Pesanan', 'link' => 'orders.show'],
        ['key' => 'date', 'label' => 'Tanggal', 'type' => 'date'],
        ['key' => 'school', 'label' => 'Sekolah'],
        ['key' => 'status', 'label' => 'Status', 'view' => 'components.status-label'],
        ['key' => 'total', 'label' => 'Nilai', 'type' => 'currency', 'align' => 'right'],
    ]"
/>
