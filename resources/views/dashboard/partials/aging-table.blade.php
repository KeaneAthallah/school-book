{{--
    Receivables aging. Expects: $rows
--}}
<x-data-table
    empty="Tidak ada piutang."
    empty-icon="checkCircle"
    :rows="$rows"
    :columns="[
        ['key' => 'label', 'label' => 'Kelompok Umur'],
        ['key' => 'count', 'label' => 'Jumlah Invoice', 'align' => 'right', 'class' => 'tabular-nums'],
        ['key' => 'total', 'label' => 'Nilai Piutang', 'type' => 'currency', 'align' => 'right'],
    ]"
/>
