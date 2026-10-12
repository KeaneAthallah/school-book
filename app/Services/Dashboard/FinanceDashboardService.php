<?php

namespace App\Services\Dashboard;

use App\Dashboard\DashboardScope;
use App\Dashboard\Kpi;
use App\Dashboard\Period;
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
        $overdueCount = $this->metrics->overdueCount($scope);
        $rebates = $this->metrics->rebateCounts($scope);
        $awaitingRebates = $rebates['submitted'] + $rebates['approved'];

        return [
            'kpis' => [
                Kpi::money('Total Invoice', $invoiced, 'receipt', 'brand', [
                    'delta' => Kpi::delta((float) $invoiced, (float) $invoicedPrevious),
                    'hint' => $period->label(),
                ]),
                Kpi::point('Invoice Terbit', $this->metrics->issuedInvoiceCount($scope), 'fileText', 'sky'),
                Kpi::point('Belum Bayar', $buckets['unpaid'], 'clock', 'slate'),
                Kpi::point('Sebagian Dibayar', $buckets['partial'], 'refresh', 'sky'),
                Kpi::point('Menunggu Verifikasi', $pending, 'alert', 'amber', [
                    'valueTone' => $pending > 0 ? 'amber' : 'default',
                ]),
                Kpi::money('Piutang', $this->metrics->outstandingTotal($scope), 'wallet', 'amber', [
                    'hint' => 'seluruh invoice',
                ]),
                Kpi::money('Jatuh Tempo', $this->metrics->overdueTotal($scope), 'alert', 'rose', [
                    'valueTone' => 'rose',
                    'hint' => $overdueCount.' invoice',
                    'goodWhenDown' => true,
                ]),
                Kpi::point('Rebate Menunggu', $awaitingRebates, 'percent', 'violet', [
                    'hint' => $rebates['approved'].' siap dibayar',
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
