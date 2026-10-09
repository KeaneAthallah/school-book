<?php

/**
 * Role-based access control matrix.
 *
 * Roles map to permission slugs. The wildcard "*" grants every permission
 * and is only intended for the built-in admin role.
 */
return [

    'permissions' => [
        'dashboard.view' => ['name' => 'Lihat Dashboard', 'group' => 'Dashboard'],

        'schools.view' => ['name' => 'Lihat Sekolah', 'group' => 'Master Data'],
        'schools.manage' => ['name' => 'Kelola Sekolah', 'group' => 'Master Data'],
        'publishers.view' => ['name' => 'Lihat Penerbit', 'group' => 'Master Data'],
        'publishers.manage' => ['name' => 'Kelola Penerbit', 'group' => 'Master Data'],
        'books.view' => ['name' => 'Lihat Buku', 'group' => 'Master Data'],
        'books.manage' => ['name' => 'Kelola Buku', 'group' => 'Master Data'],
        'categories.manage' => ['name' => 'Kelola Kategori Buku', 'group' => 'Master Data'],
        'warehouses.manage' => ['name' => 'Kelola Gudang', 'group' => 'Master Data'],
        'order-sources.manage' => ['name' => 'Kelola Sumber Pesanan', 'group' => 'Master Data'],
        'couriers.manage' => ['name' => 'Kelola Kurir & Metode Kirim', 'group' => 'Master Data'],
        'payment-methods.manage' => ['name' => 'Kelola Metode Pembayaran', 'group' => 'Master Data'],
        'rebate-rules.manage' => ['name' => 'Kelola Aturan Rebate', 'group' => 'Master Data'],
        'settings.manage' => ['name' => 'Kelola Konfigurasi', 'group' => 'Master Data'],

        'users.view' => ['name' => 'Lihat Pengguna', 'group' => 'Pengguna'],
        'users.manage' => ['name' => 'Kelola Pengguna', 'group' => 'Pengguna'],
        'roles.manage' => ['name' => 'Kelola Peran & Izin', 'group' => 'Pengguna'],

        'orders.view' => ['name' => 'Lihat Pesanan', 'group' => 'Pesanan'],
        'orders.manage' => ['name' => 'Kelola Pesanan', 'group' => 'Pesanan'],
        'orders.confirm' => ['name' => 'Konfirmasi Pesanan', 'group' => 'Pesanan'],
        'orders.cancel' => ['name' => 'Batalkan Pesanan', 'group' => 'Pesanan'],

        'receipts.view' => ['name' => 'Lihat Penerimaan Barang', 'group' => 'Inventori'],
        'receipts.manage' => ['name' => 'Kelola Penerimaan Barang', 'group' => 'Inventori'],
        'receipts.confirm' => ['name' => 'Konfirmasi Penerimaan Barang', 'group' => 'Inventori'],
        'stock.view' => ['name' => 'Lihat Stok', 'group' => 'Inventori'],
        'stock.adjust' => ['name' => 'Penyesuaian Stok', 'group' => 'Inventori'],
        'fulfillment.view' => ['name' => 'Lihat Picking & Packing', 'group' => 'Inventori'],
        'fulfillment.pick' => ['name' => 'Proses Picking', 'group' => 'Inventori'],
        'fulfillment.pack' => ['name' => 'Konfirmasi Packing', 'group' => 'Inventori'],

        'shipments.view' => ['name' => 'Lihat Pengiriman', 'group' => 'Pengiriman'],
        'shipments.manage' => ['name' => 'Kelola Pengiriman', 'group' => 'Pengiriman'],
        'shipments.confirm' => ['name' => 'Konfirmasi Pengiriman', 'group' => 'Pengiriman'],

        'invoices.view' => ['name' => 'Lihat Invoice', 'group' => 'Keuangan'],
        'invoices.manage' => ['name' => 'Kelola Invoice', 'group' => 'Keuangan'],
        'invoices.issue' => ['name' => 'Terbitkan Invoice', 'group' => 'Keuangan'],
        'payments.view' => ['name' => 'Lihat Pembayaran', 'group' => 'Keuangan'],
        'payments.manage' => ['name' => 'Kelola Pembayaran', 'group' => 'Keuangan'],
        'payments.verify' => ['name' => 'Verifikasi Pembayaran', 'group' => 'Keuangan'],
        'rebates.view' => ['name' => 'Lihat Rebate', 'group' => 'Keuangan'],
        'rebates.request' => ['name' => 'Ajukan Rebate', 'group' => 'Keuangan'],
        'rebates.approve' => ['name' => 'Setujui Rebate', 'group' => 'Keuangan'],
        'rebates.pay' => ['name' => 'Bayarkan Rebate', 'group' => 'Keuangan'],

        'reports.view' => ['name' => 'Lihat Laporan', 'group' => 'Laporan'],
        'audit.view' => ['name' => 'Lihat Audit Log', 'group' => 'Laporan'],

        'integrations.view' => ['name' => 'Lihat Integrasi', 'group' => 'Integrasi'],
        'integrations.manage' => ['name' => 'Kelola Integrasi', 'group' => 'Integrasi'],
    ],

    'roles' => [
        'admin' => [
            'name' => 'Admin',
            'description' => 'Administrator sistem dengan akses penuh.',
            'permissions' => ['*'],
        ],
        'operator_pesanan' => [
            'name' => 'Operator Pesanan',
            'description' => 'Menginput dan memvalidasi pesanan.',
            'permissions' => [
                'dashboard.view', 'schools.view', 'books.view', 'orders.view',
                'orders.manage', 'orders.confirm', 'stock.view', 'reports.view',
            ],
        ],
        'gudang' => [
            'name' => 'Gudang',
            'description' => 'Mengelola penerimaan, stok, picking, packing, dan pengiriman.',
            'permissions' => [
                'dashboard.view', 'books.view', 'orders.view', 'receipts.view',
                'receipts.manage', 'receipts.confirm', 'stock.view', 'stock.adjust',
                'fulfillment.view', 'fulfillment.pick', 'fulfillment.pack',
                'shipments.view', 'shipments.manage', 'shipments.confirm', 'reports.view',
            ],
        ],
        'keuangan' => [
            'name' => 'Keuangan',
            'description' => 'Mengelola invoice, pembayaran, piutang, dan rebate.',
            'permissions' => [
                'dashboard.view', 'schools.view', 'orders.view', 'invoices.view',
                'invoices.manage', 'invoices.issue', 'payments.view', 'payments.manage',
                'payments.verify', 'rebates.view', 'rebates.approve', 'rebates.pay',
                'reports.view',
            ],
        ],
        'sales' => [
            'name' => 'Sales / PIC',
            'description' => 'Menangani sekolah yang ditugaskan dan transaksinya.',
            'permissions' => [
                'dashboard.view', 'schools.view', 'books.view', 'orders.view',
                'orders.manage', 'invoices.view', 'payments.view', 'rebates.view',
                'rebates.request', 'reports.view',
            ],
        ],
        'pimpinan' => [
            'name' => 'Pimpinan',
            'description' => 'Melihat dashboard manajemen dan menyetujui permintaan.',
            'permissions' => [
                'dashboard.view', 'orders.view', 'invoices.view', 'payments.view',
                'rebates.view', 'rebates.approve', 'reports.view', 'audit.view',
            ],
        ],
    ],

];
