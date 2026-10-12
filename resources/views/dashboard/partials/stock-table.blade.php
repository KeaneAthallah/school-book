{{--
    Low-stock table. Expects: $rows
--}}
<x-data-table
    empty="Belum ada stok tercatat."
    empty-icon="book"
    :rows="$rows"
    :columns="[
        ['label' => 'Judul Barang', 'view' => 'dashboard.partials.top-book'],
        ['key' => 'available', 'label' => 'Tersedia', 'align' => 'right', 'class' => 'tabular-nums'],
        ['label' => 'Status', 'view' => 'dashboard.partials.top-book-status'],
    ]"
/>
