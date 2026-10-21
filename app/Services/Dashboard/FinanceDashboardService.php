<?php

namespace App\Services\Dashboard;

use App\Dashboard\DashboardScope;
use App\Dashboard\Kpi;
use App\Dashboard\Period;
use App\Enums\OrderStatus;
use App\Models\User;

/**
 * Ringkasan Keuangan - invoicing, receivables, payment verification and rebates.
 *
 * Money metrics come from three deliberately separate sources and are never
 * summed together: invoiced value (invoice dates), received payments (verified
 * payments only) and outstanding balances (invoice totals minus allocations).
 */
class FinanceDashboardService extends RoleDashboard
{
    /**
     * @return array<string, mixed>
     */
    public function build(User $user, Period $period, DashboardScope $scope): array
    {
        $invoiced = $this->metrics->invoiceTotal($period, $scope);
        $invoicedPrevious = $this->metrics->invoiceTotal($period, $scope, true);
        $buckets = $this->metrics->invoicePaymentBuckets($scope);
        $pending = $this->metrics->paymentsAwaitingVerification($scope);
        $rebates = $this->metrics->rebateSummary($period, $scope);
        $incoming = $this->metrics->ordersInPeriod($period, $scope);
        $incomingPrevious = $this->metrics->ordersInPeriod($period, $scope, true);
        $inTransit = $this->metrics->inTransitCount($scope);
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
            'buckets' => $buckets,
            'invoice_status' => $this->metrics->invoiceStatusDistribution($scope),
            'sales_series' => $this->metrics->salesSeries($period, $scope),
            'aging' => $this->metrics->agingBuckets($scope),
            'overdue_invoices' => $this->metrics->overdueInvoices($scope),
            'upcoming_invoices' => $this->metrics->upcomingDueInvoices($scope),
            'recent_payments' => $this->metrics->recentPayments($scope),
            'payments_to_verify' => $pending,
            'rebate_counts' => $rebates,
            'rebates' => $this->metrics->rebates($scope),
        ];
    }
}
