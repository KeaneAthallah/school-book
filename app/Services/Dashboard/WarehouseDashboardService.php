<?php

namespace App\Services\Dashboard;

use App\Dashboard\DashboardScope;
use App\Dashboard\Kpi;
use App\Dashboard\Period;
use App\Models\User;

/**
 * Operasional Gudang - inventory health, receipts and the picking, packing and
 * dispatch pipeline.
 *
 * Stock figures are global by design: a warehouse is not owned by a sales user,
 * so these metrics are intentionally not owner-scoped. Access to them is still
 * gated by the stock/receipt/fulfillment permissions.
 */
class WarehouseDashboardService extends RoleDashboard
{
    /**
     * @return array<string, mixed>
     */
    public function build(User $user, Period $period, DashboardScope $scope): array
    {
        $stock = $this->metrics->stockAlerts();
        $queues = $this->metrics->fulfillmentQueues($scope);
        $receipts = $this->metrics->receiptCounts();
        $backorders = $this->metrics->activeBackorderCount($scope);

        return [
            'kpis' => [
                Kpi::point('Total Judul Buku', $this->metrics->counts()['books'], 'book', 'brand'),
                Kpi::point('Stok Menipis', $stock['low'], 'trendDown', 'amber', [
                    'valueTone' => $stock['low'] > 0 ? 'amber' : 'default',
                    'hint' => 'di bawah minimum',
                ]),
                Kpi::point('Stok Habis', $stock['out'], 'alert', 'rose', [
                    'valueTone' => $stock['out'] > 0 ? 'rose' : 'emerald',
                ]),
                Kpi::point('Barang Masuk', $receipts['draft'], 'inbox', 'sky', [
                    'hint' => 'belum dikonfirmasi',
                ]),
                Kpi::point('Menunggu Picking', $queues['picking'], 'clipboard', 'violet'),
                Kpi::point('Menunggu Packing', $queues['packing'], 'boxes', 'amber'),
                Kpi::point('Back Order', $backorders, 'refresh', 'rose', [
                    'valueTone' => $backorders > 0 ? 'rose' : 'emerald',
                ]),
                Kpi::point('Siap Kirim', $queues['ready_to_ship'], 'truck', 'sky'),
            ],
            'notifications' => $this->alerts($user, $scope),
            'stock_alerts' => $stock,
            'stock_rows' => $this->metrics->stockRows(),
            'stock_movements' => $this->metrics->stockMovements(),
            'recent_receipts' => $this->metrics->recentReceipts(),
            'pickup_queue' => $this->metrics->pickupQueue($scope),
            'pipeline' => $this->metrics->fulfillmentPipeline($scope),
            'backorders' => $this->metrics->backorders($scope),
        ];
    }
}
