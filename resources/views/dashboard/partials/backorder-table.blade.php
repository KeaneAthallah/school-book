{{--
    Back orders. Expects: $rows
--}}
<x-data-table
    empty="Tidak ada back order."
    empty-icon="checkCircle"
    :rows="$rows"
    :columns="[
        ['key' => 'number', 'label' => 'No. Pesanan'],
        ['key' => 'school', 'label' => 'Sekolah'],
        ['key' => 'book', 'label' => 'Buku'],
        ['key' => 'quantity', 'label' => 'Sisa', 'align' => 'right', 'class' => 'tabular-nums'],
        ['key' => 'status', 'label' => 'Status', 'view' => 'components.status-label'],
    ]"
/>
