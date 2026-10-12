<?php

namespace App\Dashboard;

/**
 * The six BOOKFLOW workspaces.
 *
 * These are dashboard concepts, not database roles. Each case maps onto the
 * existing role slugs in config/rbac.php so no duplicate roles are created.
 */
enum DashboardRole: string
{
    case Admin = 'admin';

    case Operator = 'operator';

    case Warehouse = 'warehouse';

    case Finance = 'finance';

    case Sales = 'sales';

    case Executive = 'executive';

    /**
     * Role slugs (config/rbac.php) that resolve to this workspace.
     *
     * @return list<string>
     */
    public function roleSlugs(): array
    {
        return match ($this) {
            self::Admin => ['admin'],
            self::Operator => ['operator_pesanan'],
            self::Warehouse => ['gudang'],
            self::Finance => ['keuangan'],
            self::Sales => ['sales'],
            self::Executive => ['pimpinan'],
        };
    }

    /**
     * Welcome heading shown on the page header.
     */
    public function title(): string
    {
        return match ($this) {
            self::Admin => 'Ringkasan Sistem',
            self::Operator => 'Pusat Pemrosesan Pesanan',
            self::Warehouse => 'Operasional Gudang',
            self::Finance => 'Ringkasan Keuangan',
            self::Sales => 'Kinerja Sekolah dan Tindak Lanjut',
            self::Executive => 'Ringkasan Kinerja Bisnis',
        };
    }

    public function subtitle(): string
    {
        return match ($this) {
            self::Admin => 'Kesehatan sistem, data induk, dan aktivitas operasional.',
            self::Operator => 'Pesanan masuk dari SIPLah, e-Katalog, dan entri manual.',
            self::Warehouse => 'Penerimaan barang, stok, picking, packing, dan pengiriman.',
            self::Finance => 'Invoice, piutang, pembayaran, dan rebate.',
            self::Sales => 'Sekolah yang ditugaskan, pesanan, dan tindak lanjut.',
            self::Executive => 'Performa penjualan, pemenuhan pesanan, dan risiko.',
        };
    }

    /**
     * Blade view rendered for this workspace.
     *
     * @return view-string
     */
    public function view(): string
    {
        return 'dashboard.'.$this->value;
    }

    /**
     * Whether this workspace only ever shows records owned by the logged-in
     * user. Sales / PIC is the only role with a per-user data scope.
     */
    public function isScopedToOwner(): bool
    {
        return $this === self::Sales;
    }
}
