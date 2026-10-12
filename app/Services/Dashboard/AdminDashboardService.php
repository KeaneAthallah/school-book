<?php

namespace App\Services\Dashboard;

use App\Dashboard\DashboardScope;
use App\Dashboard\Kpi;
use App\Dashboard\Period;
use App\Enums\OrderStatus;
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
