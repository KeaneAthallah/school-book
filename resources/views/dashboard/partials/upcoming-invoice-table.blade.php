{{--
    Invoices due soon. Expects: $rows
--}}
<x-data-table
    empty="Tidak ada invoice mendekati jatuh tempo."
    empty-icon="checkCircle"
    :rows="$rows"
    :columns="[
        ['key' => 'school', 'label' => 'Sekolah'],
        ['key' => 'number', 'label' => 'Invoice', 'link' => 'invoices.show'],
        ['key' => 'outstanding', 'label' => 'Sisa', 'type' => 'currency', 'align' => 'right'],
        ['key' => 'due_date', 'label' => 'Jatuh Tempo', 'type' => 'date'],
        ['label' => 'Selisih', 'view' => 'dashboard.partials.due-in-days'],
    ]"
/>
