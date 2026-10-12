<?php

namespace App\Services\Dashboard;

use App\Dashboard\DashboardScope;
use App\Dashboard\Kpi;
use App\Dashboard\Period;
use App\Enums\OrderStatus;
use App\Models\User;

/**
 * Ringkasan Kinerja Bisnis - the read-only executive view.
 *
 * Emphasis is on decisions rather than transactions, so the payload is trends,
 * distributions and risk rather than work queues. No write action is offered
 * here; rebate approval appears only because the Pimpinan role holds
 * `rebates.approve`, and the view checks that permission itself.
 */
class ExecutiveDashboardService extends RoleDashboard
{
    /**
     * @return array<string, mixed>
     */
    public function build(User $user, Period $period, DashboardScope $scope): array
    {
        $sales = $this->metrics->salesTotal($period, $scope);
        $salesPrevious = $this->metrics->salesTotal($period, $scope, true);
        $invoiced = $this->metrics->invoiceTotal($period, $scope);
        $invoicedPrevious = $this->metrics->invoiceTotal($period, $scope, true);
        $paid = $this->metrics->paymentTotal($period, $scope);
        $stock = $this->metrics->stockAlerts();
        $awaitingFulfillment = $this->metrics->ordersWithStatus([
            OrderStatus::Processing->value,
            OrderStatus::PartialReady->value,
            OrderStatus::Ready->value,
            OrderStatus::PartialShipped->value,
        ], $scope);
        $rebates = $this->metrics->rebateCounts($scope);

        return [
            'kpis' => [
                Kpi::money('Total Penjualan', $sales, 'chart', 'emerald', [
                    'delta' => Kpi::delta((float) $sales, (float) $salesPrevious),
                    'hint' => 'nilai terkirim',
                ]),
                Kpi::money('Total Invoice', $invoiced, 'receipt', 'brand', [
                    'delta' => Kpi::delta((float) $invoiced, (float) $invoicedPrevious),
                ]),
                Kpi::money('Pembayaran Diterima', $paid, 'wallet', 'emerald'),
                Kpi::money('Piutang', $this->metrics->outstandingTotal($scope), 'fileText', 'amber', [
                    'hint' => 'seluruh invoice',
                ]),
                Kpi::money('Jatuh Tempo', $this->metrics->overdueTotal($scope), 'alert', 'rose', [
                    'valueTone' => 'rose',
                    'hint' => $this->metrics->overdueCount($scope).' invoice',
                    'goodWhenDown' => true,
                ]),
                Kpi::point('Menunggu Pemenuhan', $awaitingFulfillment, 'truck', 'sky'),
                Kpi::point('Judul Buku Menipis', $stock['low'] + $stock['out'], 'boxes', 'amber', [
                    'valueTone' => ($stock['low'] + $stock['out']) > 0 ? 'amber' : 'default',
                    'hint' => $stock['out'].' habis',
                ]),
                Kpi::point('Rebate Menunggu', $rebates['submitted'] + $rebates['approved'], 'percent', 'violet'),
            ],
            'notifications' => $this->alerts($user, $scope),
            'sales_series' => $this->metrics->salesSeries($period, $scope),
            'order_status' => $this->metrics->orderStatusDistribution($scope),
            'invoice_status' => $this->metrics->invoiceStatusDistribution($scope),
            'sales_by_school' => $this->metrics->salesBySchool($period, $scope),
            'sales_by_publisher' => $this->metrics->salesByPublisher($period, $scope),
            'aging' => $this->metrics->agingBuckets($scope),
            'pipeline' => $this->metrics->fulfillmentPipeline($scope),
            'overdue_invoices' => $this->metrics->overdueInvoices($scope),
            'stock_rows' => $this->metrics->stockRows(),
            'rebate_counts' => $rebates,
        ];
    }
}
