{{--
    Assigned schools. Expects: $rows

    Deliberately unlinked: schools are edited through the master-data screens,
    which require `schools.manage`. A Sales / PIC user only holds
    `schools.view`, so linking here would offer a 403.
--}}
<x-data-table
    empty="Belum ada sekolah yang ditugaskan."
    empty-icon="school"
    :rows="$rows"
    :columns="[
        ['key' => 'name', 'label' => 'Sekolah'],
        ['key' => 'district', 'label' => 'Kecamatan'],
        ['key' => 'regency', 'label' => 'Kabupaten'],
        ['key' => 'orders_count', 'label' => 'Pesanan', 'align' => 'right', 'class' => 'tabular-nums'],
    ]"
/>
