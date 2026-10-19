{{--
    Goods receipts. Expects: $rows
--}}
<x-data-table
    empty="Belum ada penerimaan barang."
    empty-icon="inbox"
    :rows="$rows"
    :columns="[
        ['key' => 'number', 'label' => 'No. Penerimaan', 'link' => 'receipts.show'],
        ['key' => 'date', 'label' => 'Tanggal', 'type' => 'date'],
        ['key' => 'publisher', 'label' => 'Penerbit'],
        ['key' => 'status', 'label' => 'Status', 'view' => 'components.status-label'],
    ]"
/>
