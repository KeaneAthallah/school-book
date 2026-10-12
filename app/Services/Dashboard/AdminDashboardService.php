<?php

namespace App\Services\Dashboard;

use App\Dashboard\DashboardScope;
use App\Dashboard\Kpi;
use App\Dashboard\Period;
use App\Models\User;

/**
 * Ringkasan Sistem - application health, master data volumes, users and roles,
 * integration activity and operational exceptions.
 *
 * Deliberately explicit about permissions: the Admin workspace summarises the
 * system but the controller still requires each underlying module's permission
 * before an admin may open it, and the quick actions below are filtered by the
 * same permission checks in the view.
 */
class AdminDashboardService extends RoleDashboard
{
    /**
     * @return array<string, mixed>
     */
    public function build(User $user, Period $period, DashboardScope $scope): array
    {
        $counts = $this->metrics->counts();
        $alerts = $this->alerts($user, $scope);
        $orders = $this->metrics->ordersInPeriod($period, $scope);
        $ordersPrevious = $this->metrics->ordersInPeriod($period, $scope, true);
        $overdueCount = $this->metrics->overdueCount($scope);

        return [
            'kpis' => [
                Kpi::point('Total Pengguna', $counts['users'], 'users', 'brand'),
                Kpi::point('Total Sekolah', $counts['schools'], 'school', 'sky'),
                Kpi::point('Total Penerbit', $counts['publishers'], 'database', 'violet'),
                Kpi::point('Total Buku', $counts['books'], 'book', 'brand'),
                Kpi::number('Pesanan Periode Ini', $orders, 'cart', 'brand', [
                    'delta' => Kpi::delta($orders, $ordersPrevious),
                    'hint' => $period->label(),
                ]),
                Kpi::money('Nilai Persediaan', $this->metrics->inventoryValue(), 'boxes', 'sky', [
                    'hint' => 'harga pokok',
                ]),
                Kpi::money('Piutang', $this->metrics->outstandingTotal($scope), 'fileText', 'amber', [
                    'hint' => $overdueCount.' jatuh tempo',
                ]),
                Kpi::point('Peringatan Sistem', count($alerts), 'bell', 'rose', [
                    'valueTone' => count($alerts) > 0 ? 'rose' : 'emerald',
                ]),
            ],
            'notifications' => $alerts,
            'counts' => $counts,
            'stock_alerts' => $this->metrics->stockAlerts(),
            'role_breakdown' => $this->metrics->roleBreakdown(),
            'order_status' => $this->metrics->orderStatusDistribution($scope),
            'invoice_status' => $this->metrics->invoiceStatusDistribution($scope),
            'sales_series' => $this->metrics->salesSeries($period, $scope),
            'recent_orders' => $this->metrics->recentOrders($scope),
            'stock_rows' => $this->metrics->stockRows(),
            'recent_activity' => $this->metrics->recentActivity(),
            'overdue_invoices' => $this->metrics->overdueInvoices($scope),
            'payments_to_verify' => $this->metrics->paymentsAwaitingVerification($scope),
            'open_backorders' => $this->metrics->activeBackorderCount($scope),
        ];
    }
}
