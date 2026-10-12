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
        $backorders = $this->metrics->activeBackorderCount($scope);

        return [
            'kpis' => [
                Kpi::number('Pesanan Masuk', $incoming, 'cart', 'brand', [
                    'delta' => Kpi::delta($incoming, $incomingPrevious),
                    'hint' => $period->label(),
                ]),
                Kpi::point('Draft Pesanan', $queues['draft'], 'fileText', 'slate'),
                Kpi::point('Perlu Validasi', $queues['validation'], 'alert', 'amber', [
                    'valueTone' => $queues['validation'] > 0 ? 'amber' : 'default',
                ]),
                Kpi::point('Diproses', $queues['in_progress'], 'refresh', 'sky'),
                Kpi::point('Siap Picking', $queues['ready_picking'], 'clipboard', 'violet'),
                Kpi::point('Menunggu Stok', $queues['waiting_stock'], 'clock', 'amber', [
                    'valueTone' => $queues['waiting_stock'] > 0 ? 'amber' : 'default',
                ]),
                Kpi::point('Back Order', $backorders, 'inbox', 'rose', [
                    'valueTone' => $backorders > 0 ? 'rose' : 'emerald',
                ]),
                Kpi::point('Pesanan Bermasalah', $queues['cancelled'], 'close', 'slate'),
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
