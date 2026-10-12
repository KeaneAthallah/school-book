<?php

namespace App\Services\Dashboard;

use App\Dashboard\DashboardScope;
use App\Dashboard\Kpi;
use App\Dashboard\Period;
use App\Enums\OrderStatus;
use App\Models\User;

/**
 * Pusat Pemrosesan Pesanan - order intake, validation and the queues an
 * operator works through.
 *
 * The queues are mutually exclusive so no order is counted twice:
 * draft -> received (validation) -> processing (waiting stock) ->
 * partial_ready (in progress) -> ready (picking) -> shipped.
 */
class OperatorDashboardService extends RoleDashboard
{
    /**
     * @return array<string, mixed>
     */
    public function build(User $user, Period $period, DashboardScope $scope): array
    {
        $queues = $this->metrics->orderQueues($scope);
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
            'queues' => $queues,
            'order_status' => $this->metrics->orderStatusDistribution($scope),
            'recent_orders' => $this->metrics->recentOrders($scope),
            'validation_queue' => $this->metrics->ordersByStatus([OrderStatus::Received->value], $scope),
            'waiting_stock_queue' => $this->metrics->ordersByStatus([OrderStatus::Processing->value], $scope),
            'ready_queue' => $this->metrics->ordersByStatus([
                OrderStatus::PartialReady->value,
                OrderStatus::Ready->value,
            ], $scope),
            'backorders' => $this->metrics->backorders($scope),
        ];
    }
}
