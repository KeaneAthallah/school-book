{{--
    Payments. Expects: $rows
--}}
<x-data-table
    empty="Belum ada pembayaran."
    empty-icon="wallet"
    :rows="$rows"
    :columns="[
        ['key' => 'number', 'label' => 'No. Pembayaran'],
        ['key' => 'date', 'label' => 'Tanggal', 'type' => 'date'],
        ['key' => 'school', 'label' => 'Sekolah'],
        ['key' => 'status', 'label' => 'Status', 'view' => 'components.status-label'],
        ['key' => 'amount', 'label' => 'Nilai', 'type' => 'currency', 'align' => 'right'],
    ]"
/>
