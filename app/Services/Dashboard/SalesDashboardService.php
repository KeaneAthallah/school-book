<?php

namespace App\Services\Dashboard;

use App\Dashboard\DashboardScope;
use App\Dashboard\Kpi;
use App\Dashboard\Period;
use App\Enums\OrderStatus;
use App\Models\User;

/**
 * Kinerja Sekolah dan Tindak Lanjut - the per-user portfolio view.
 *
 * Every metric here runs through a restricted DashboardScope, so a Sales / PIC
 * user only ever sees schools whose `sales_user_id` is their own. The scope is
 * applied inside SQL by DashboardMetrics rather than filtered afterwards.
 */
class SalesDashboardService extends RoleDashboard
{
    /**
     * @return array<string, mixed>
     */
    public function build(User $user, Period $period, DashboardScope $scope): array
    {
        $queues = $this->metrics->orderQueues($scope);
        $incoming = $this->metrics->ordersInPeriod($period, $scope);
        $incomingPrevious = $this->metrics->ordersInPeriod($period, $scope, true);
        $sales = $this->metrics->salesTotal($period, $scope);
        $salesPrevious = $this->metrics->salesTotal($period, $scope, true);
        $stale = $this->metrics->schoolsWithoutRecentOrders($scope);

        return [
            'kpis' => [
                Kpi::point('Sekolah Ditugaskan', $this->metrics->schoolCount($scope), 'school', 'brand'),
                Kpi::number('Pesanan Sekolah Saya', $incoming, 'cart', 'sky', [
                    'delta' => Kpi::delta($incoming, $incomingPrevious),
                    'hint' => $period->label(),
                ]),
                Kpi::point('Perlu Tindak Lanjut', count($stale), 'clock', 'amber', [
                    'valueTone' => count($stale) > 0 ? 'amber' : 'default',
                    'hint' => 'tanpa pesanan 45 hari',
                ]),
                Kpi::point('Diproses', $queues['in_progress'], 'refresh', 'sky'),
                Kpi::point('Menunggu Stok', $queues['waiting_stock'], 'alert', 'amber'),
                Kpi::money('Penjualan Periode Ini', $sales, 'chart', 'emerald', [
                    'delta' => Kpi::delta((float) $sales, (float) $salesPrevious),
                    'hint' => 'nilai terkirim',
                ]),
                Kpi::money('Piutang Sekolah Saya', $this->metrics->outstandingTotal($scope), 'fileText', 'amber'),
                Kpi::point('Back Order', $this->metrics->activeBackorderCount($scope), 'inbox', 'rose'),
            ],
            'notifications' => $this->alerts($user, $scope),
            'queues' => $queues,
            'assigned_schools' => $this->metrics->assignedSchools($scope),
            'stale_schools' => $stale,
            'recent_orders' => $this->metrics->recentOrders($scope),
            'ready_queue' => $this->metrics->ordersByStatus([
                OrderStatus::PartialReady->value,
                OrderStatus::Ready->value,
            ], $scope),
            'sales_by_school' => $this->metrics->salesBySchool($period, $scope),
            'pipeline' => $this->metrics->fulfillmentPipeline($scope),
        ];
    }
}
