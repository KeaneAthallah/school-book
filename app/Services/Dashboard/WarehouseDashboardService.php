<?php

namespace App\Services\Dashboard;

use App\Dashboard\DashboardScope;
use App\Dashboard\Kpi;
use App\Dashboard\Period;
use App\Enums\OrderStatus;
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
        $incoming = $this->metrics->ordersInPeriod($period, $scope);
        $incomingPrevious = $this->metrics->ordersInPeriod($period, $scope, true);
        $inTransit = $this->metrics->inTransitCount($scope);
        $invoiced = $this->metrics->invoiceTotal($period, $scope);
        $invoicedPrevious = $this->metrics->invoiceTotal($period, $scope, true);
        $paid = $this->metrics->paymentTotal($period, $scope);
        $paidPrevious = $this->metrics->paymentTotal($period, $scope, true);
        $overdueCount = $this->metrics->overdueCount($scope);
        $overdueTotal = $this->metrics->overdueTotal($scope);

        return [
            'kpis' => [
                Kpi::number('Pesanan Masuk', $incoming, 'cart', 'brand', [
                    'delta' => Kpi::delta($incoming, $incomingPrevious),
                    'hint' => $period->label(),
                ]),
                Kpi::number('Siap Picking', $this->metrics->ordersWithStatus([
                    OrderStatus::PartialReady->value,
                    OrderStatus::Ready->value,
                ], $scope), 'clipboard', 'violet'),
                Kpi::number('Menunggu Stok', $this->metrics->ordersWithStatus([
                    OrderStatus::Processing->value,
                ], $scope), 'clock', 'amber'),
                Kpi::number('Dalam Pengiriman', $inTransit, 'truck', 'sky'),
                Kpi::money('Total Invoice', $invoiced, 'receipt', 'brand', [
                    'delta' => Kpi::delta((float) $invoiced, (float) $invoicedPrevious),
                    'hint' => $period->label(),
                ]),
                Kpi::money('Sudah Dibayar', $paid, 'checkCircle', 'emerald', [
                    'delta' => Kpi::delta((float) $paid, (float) $paidPrevious),
                ]),
                Kpi::money('Piutang', $this->metrics->outstandingTotal($scope), 'fileText', 'amber'),
                Kpi::money('Jatuh Tempo', $overdueTotal, 'alert', 'rose', [
                    'valueTone' => (float) $overdueTotal > 0 ? 'rose' : 'default',
                    'hint' => $overdueCount.' invoice',
                    'goodWhenDown' => true,
                ]),
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
