{{--
    Rebates. Expects: $rows
--}}
<x-data-table
    empty="Belum ada pengajuan rebate."
    empty-icon="percent"
    :rows="$rows"
    :columns="[
        ['key' => 'number', 'label' => 'No. Nota'],
        ['key' => 'school', 'label' => 'Sekolah'],
        ['key' => 'percent', 'label' => 'Rebate %', 'align' => 'right'],
        ['key' => 'amount', 'label' => 'Nilai Rebate', 'type' => 'currency', 'align' => 'right'],
        ['key' => 'date', 'label' => 'Tanggal'],
    ]"
/>
