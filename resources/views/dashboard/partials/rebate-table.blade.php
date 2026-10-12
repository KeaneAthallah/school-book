{{--
    Rebates. Expects: $rows
--}}
<x-data-table
    empty="Belum ada pengajuan rebate."
    empty-icon="percent"
    :rows="$rows"
    :columns="[
        ['key' => 'number', 'label' => 'No. Rebate'],
        ['key' => 'school', 'label' => 'Sekolah'],
        ['key' => 'status', 'label' => 'Status', 'view' => 'components.status-label'],
        ['key' => 'amount', 'label' => 'Nilai', 'type' => 'currency', 'align' => 'right'],
    ]"
/>
