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
        $rebates = $this->metrics->rebateCounts($scope);
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
