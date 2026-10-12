<?php

namespace App\Services;

use App\Dashboard\DashboardScope;
use App\Enums\BackorderStatus;
use App\Enums\GoodsReceiptStatus;
use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShipmentStatus;
use App\Models\GoodsReceipt;
use App\Models\IntegrationLog;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Shipment;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

/**
 * Derives the actionable alerts shown in the notification menu and on the
 * dashboard. Every entry is computed from real records; nothing is stubbed.
 *
 * Alerts are gated twice: a permission decides whether a category is relevant
 * to the user at all, and the optional DashboardScope restricts the records
 * inside that category to what the user is actually allowed to see. Passing a
 * scope is what stops a Sales / PIC user from being told about another
 * portfolio's overdue invoices.
 */
class NotificationService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function for(User $user, ?DashboardScope $scope = null): array
    {
        $alerts = [];

        if ($user->hasPermission('invoices.view')) {
            $this->push($alerts, $this->overdueInvoices($scope));
            $this->push($alerts, $this->invoicesDueSoon($scope));
        }

        if ($user->hasPermission('payments.verify')) {
            $this->push($alerts, $this->paymentsToVerify($scope));
        }

        if ($user->hasPermission('fulfillment.view')) {
            $this->push($alerts, $this->readyToPick($scope));
        }

        if ($user->hasPermission('shipments.view')) {
            $this->push($alerts, $this->shipmentsToDispatch($scope));
        }

        if ($user->hasPermission('receipts.confirm')) {
            $this->push($alerts, $this->draftReceipts());
        }

        if ($user->hasPermission('orders.view')) {
            $this->push($alerts, $this->openBackorders($scope));
        }

        if ($user->hasPermission('integrations.view')) {
            $this->push($alerts, $this->integrationIssues());
        }

        return $alerts;
    }

    public function count(User $user, ?DashboardScope $scope = null): int
    {
        return array_sum(array_map(
            fn (array $alert): int => $alert['count'],
            $this->for($user, $scope),
        ));
    }

    /**
     * @param  list<array<string, mixed>>  $alerts
     * @param  array<string, mixed>|null  $alert
     */
    private function push(array &$alerts, ?array $alert): void
    {
        if ($alert !== null && $alert['count'] > 0) {
            $alerts[] = $alert;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function overdueInvoices(?DashboardScope $scope): ?array
    {
        $query = Invoice::query()
            ->whereNotIn('status', [InvoiceStatus::Cancelled->value, InvoiceStatus::Draft->value])
            ->whereDate('due_date', '<', now()->toDateString())
            ->whereColumn('paid_amount', '<', 'total_amount');

        if ($scope !== null) {
            $query = $scope->invoices($query);
        }

        $count = (clone $query)->count();

        if ($count === 0) {
            return null;
        }

        $outstanding = (string) (clone $query)->selectRaw('COALESCE(SUM(total_amount - paid_amount), 0) as total')->value('total');

        return [
            'key' => 'overdue_invoices',
            'title' => 'Invoice jatuh tempo',
            'description' => $count.' invoice dengan sisa '.Money::add($outstanding, '0'),
            'tone' => 'danger',
            'count' => $count,
            'href' => '/invoices?payment_status=overdue',
            'time' => (clone $query)->min('due_date'),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function invoicesDueSoon(?DashboardScope $scope): ?array
    {
        $query = Invoice::query()
            ->whereNotIn('status', [InvoiceStatus::Cancelled->value, InvoiceStatus::Draft->value])
            ->whereDate('due_date', '>=', now()->toDateString())
            ->whereDate('due_date', '<=', now()->addDays(7)->toDateString())
            ->whereColumn('paid_amount', '<', 'total_amount');

        if ($scope !== null) {
            $query = $scope->invoices($query);
        }

        $count = (clone $query)->count();

        if ($count === 0) {
            return null;
        }

        return [
            'key' => 'invoices_due_soon',
            'title' => 'Invoice mendekati jatuh tempo',
            'description' => $count.' invoice jatuh tempo dalam 7 hari',
            'tone' => 'warning',
            'count' => $count,
            'href' => '/invoices?payment_status=unpaid',
            'time' => (clone $query)->min('due_date'),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function paymentsToVerify(?DashboardScope $scope): ?array
    {
        $query = Payment::query()->where('status', PaymentStatus::Draft->value);

        if ($scope !== null) {
            $query = $scope->payments($query);
        }

        $count = (clone $query)->count();

        if ($count === 0) {
            return null;
        }

        return [
            'key' => 'payments_to_verify',
            'title' => 'Pembayaran menunggu verifikasi',
            'description' => $count.' pembayaran belum diverifikasi',
            'tone' => 'info',
            'count' => $count,
            'href' => '/payments',
            'time' => (clone $query)->max('created_at'),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readyToPick(?DashboardScope $scope): ?array
    {
        $query = Order::query()->whereIn('status', [
            OrderStatus::PartialReady->value,
            OrderStatus::Ready->value,
        ]);

        if ($scope !== null) {
            $query = $scope->orders($query);
        }

        $count = (clone $query)->count();

        if ($count === 0) {
            return null;
        }

        return [
            'key' => 'ready_to_pick',
            'title' => 'Pesanan siap picking',
            'description' => $count.' pesanan memiliki stok siap diambil',
            'tone' => 'info',
            'count' => $count,
            'href' => '/orders?status=ready',
            'time' => (clone $query)->max('confirmed_at'),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function shipmentsToDispatch(?DashboardScope $scope): ?array
    {
        $query = Shipment::query()->whereIn('status', [
            ShipmentStatus::Draft->value,
            ShipmentStatus::ReadyToShip->value,
        ]);

        if ($scope !== null) {
            $query = $scope->shipments($query);
        }

        $count = (clone $query)->count();

        if ($count === 0) {
            return null;
        }

        return [
            'key' => 'shipments_to_dispatch',
            'title' => 'Pengiriman belum dikirim',
            'description' => $count.' pengiriman menunggu konfirmasi',
            'tone' => 'info',
            'count' => $count,
            'href' => '/shipments',
            'time' => (clone $query)->max('created_at'),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function draftReceipts(): ?array
    {
        $query = GoodsReceipt::query()->where('status', GoodsReceiptStatus::Draft->value);
        $count = (clone $query)->count();

        if ($count === 0) {
            return null;
        }

        return [
            'key' => 'draft_receipts',
            'title' => 'Penerimaan barang belum dikonfirmasi',
            'description' => $count.' penerimaan menunggu konfirmasi stok',
            'tone' => 'warning',
            'count' => $count,
            'href' => '/receipts',
            'time' => (clone $query)->max('created_at'),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function openBackorders(?DashboardScope $scope): ?array
    {
        $query = DB::table('backorders')
            ->whereIn('status', [BackorderStatus::Open->value, BackorderStatus::Partial->value]);

        if ($scope !== null && $scope->isRestricted()) {
            $query->whereIn('order_item_id', fn ($sub) => $sub
                ->select('order_items.id')
                ->from('order_items')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->whereIn('orders.school_id', fn ($schools) => $schools
                    ->select('id')
                    ->from('schools')
                    ->where('sales_user_id', $scope->ownerId)));
        }

        $outstanding = (int) (clone $query)
            ->selectRaw('COALESCE(SUM(quantity - fulfilled_quantity), 0) as total')
            ->value('total');

        if ($outstanding <= 0) {
            return null;
        }

        return [
            'key' => 'open_backorders',
            'title' => 'Back order belum terpenuhi',
            'description' => $outstanding.' buku menunggu stok masuk',
            'tone' => 'warning',
            'count' => $outstanding,
            'href' => '/reports?type=backorders',
            'time' => (clone $query)->max('updated_at'),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function integrationIssues(): ?array
    {
        $query = IntegrationLog::query()
            ->whereIn('status', ['skipped', 'error', 'failed'])
            ->where('created_at', '>=', now()->subDays(7));
        $count = (clone $query)->count();

        if ($count === 0) {
            return null;
        }

        return [
            'key' => 'integration_issues',
            'title' => 'Kendala integrasi',
            'description' => $count.' catatan integrasi gagal/dilewati (7 hari)',
            'tone' => 'danger',
            'count' => $count,
            'href' => '/integrations',
            'time' => (clone $query)->max('created_at'),
        ];
    }
}
