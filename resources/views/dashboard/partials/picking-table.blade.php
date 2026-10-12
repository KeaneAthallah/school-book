{{--
    Picking / packing queue. Expects: $rows
--}}
<x-data-table
    empty="Tidak ada tugas picking."
    empty-icon="clipboard"
    :rows="$rows"
    :columns="[
        ['key' => 'number', 'label' => 'No. Picking', 'link' => 'pickings.show'],
        ['key' => 'order', 'label' => 'No. Pesanan'],
        ['key' => 'school', 'label' => 'Sekolah'],
        ['key' => 'status', 'label' => 'Status', 'view' => 'components.status-label'],
    ]"
/>
