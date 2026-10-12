{{--
    Overdue receivables. Expects: $rows
--}}
<x-data-table
    empty="Tidak ada piutang jatuh tempo."
    empty-icon="checkCircle"
    :rows="$rows"
    :columns="[
        ['key' => 'school', 'label' => 'Sekolah'],
        ['key' => 'number', 'label' => 'Invoice', 'link' => 'invoices.show'],
        ['key' => 'outstanding', 'label' => 'Nilai Piutang', 'type' => 'currency', 'align' => 'right'],
        ['key' => 'due_date', 'label' => 'Jatuh Tempo', 'type' => 'date'],
        ['label' => 'Status', 'view' => 'dashboard.partials.overdue-days'],
    ]"
/>
