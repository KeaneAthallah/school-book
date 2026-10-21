<?php

namespace App\Services\Dashboard;

use App\Dashboard\DashboardScope;
use App\Dashboard\Period;
use App\Enums\BackorderStatus;
use App\Enums\GoodsReceiptStatus;
use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PickingStatus;
use App\Enums\ShipmentStatus;
use App\Models\AuditLog;
use App\Models\Backorder;
use App\Models\Book;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Picking;
use App\Models\Publisher;
use App\Models\Role;
use App\Models\School;
use App\Models\Shipment;
use App\Models\User;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Every dashboard metric in one place.
 *
 * Definitions (authoritative for the whole application):
 *
 *  - Pesanan Masuk ......... orders whose `order_date` falls in the period,
 *                            excluding cancelled and still-draft records.
 *  - Penjualan ............. SUM(order_items.unit_price * shipped_quantity) on
 *                            non-cancelled, non-draft orders in the period. This
 *                            is shipped value, never invoices or payments.
 *  - Invoice ............... SUM(invoices.total_amount) by `invoice_date`,
 *                            excluding cancelled. Drafts are excluded from
 *                            receivables but not from issued totals.
 *  - Pembayaran ............ SUM(payments.amount) with status `verified` by
 *                            `payment_date`. Draft and reversed are excluded, so
 *                            a payment never counts before it is verified.
 *  - Piutang ............... outstanding = total_amount - paid_amount over
 *                            non-cancelled, non-draft invoices, floored at zero
 *                            per invoice.
 *  - Piutang Jatuh Tempo ... outstanding > 0 AND due_date < today. A fully paid
 *                            invoice is never overdue debt.
 *  - Stok .................. physical stock tracking (stocks, warehouses,
 *                            min_stock) was removed, so the stock panels are
 *                            placeholders that return empty data.
 *
 * Every method takes a DashboardScope so role restrictions are applied inside
 * SQL rather than being filtered after the fact.
 */
class DashboardMetrics
{
    /**
     * @var list<string>
     */
    private const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    // ---------------------------------------------------------------- master

    /**
     * @return array{users: int, schools: int, publishers: int, books: int}
     */
    public function counts(): array
    {
        return [
            'users' => User::query()->count(),
            'schools' => School::query()->count(),
            'publishers' => Publisher::query()->count(),
            'books' => Book::query()->count(),
        ];
    }

    public function schoolCount(DashboardScope $scope): int
    {
        return $scope->schools(School::query())->count();
    }

    /**
     * Inventory valuation: on-hand quantity at cost price.
     */
    public function inventoryValue(): string
    {
        return '0.00';
    }

    // ---------------------------------------------------------------- orders

    public function ordersInPeriod(Period $period, DashboardScope $scope, bool $previous = false): int
    {
        $start = $previous ? $period->previousStart : $period->start;
        $end = $previous ? $period->previousEnd : $period->end;

        return $scope->orders(Order::query())
            ->whereNotIn('status', [OrderStatus::Cancelled->value, OrderStatus::Draft->value])
            ->whereDate('order_date', '>=', $start->toDateString())
            ->whereDate('order_date', '<=', $end->toDateString())
            ->count();
    }

    /**
     * Shipments handed to the courier but not yet marked delivered.
     */
    public function inTransitCount(DashboardScope $scope): int
    {
        return $scope->shipments(Shipment::query())
            ->where('status', ShipmentStatus::Shipped->value)
            ->count();
    }

    /**
     * @param  list<string>  $statuses
     */
    public function ordersWithStatus(array $statuses, DashboardScope $scope): int
    {
        return $scope->orders(Order::query())->whereIn('status', $statuses)->count();
    }

    /**
     * Mutually exclusive order buckets used by the operator dashboard.
     *
     * @return array<string, int>
     */
    public function orderQueues(DashboardScope $scope): array
    {
        return [
            'draft' => $this->ordersWithStatus([OrderStatus::Draft->value], $scope),
            'validation' => $this->ordersWithStatus([OrderStatus::Received->value], $scope),
            'waiting_stock' => $this->ordersWithStatus([OrderStatus::Processing->value], $scope),
            'in_progress' => $this->ordersWithStatus([OrderStatus::PartialReady->value], $scope),
            'ready_picking' => $this->ordersWithStatus([OrderStatus::Ready->value], $scope),
            'cancelled' => $this->ordersWithStatus([OrderStatus::Cancelled->value], $scope),
        ];
    }

    /**
     * @return list<array{label: string, value: int, color: string}>
     */
    public function orderStatusDistribution(DashboardScope $scope): array
    {
        $counts = $scope->orders(Order::query())
            ->whereNotIn('status', [OrderStatus::Draft->value, OrderStatus::Cancelled->value])
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $bucket = fn (array $statuses): int => (int) collect($statuses)
            ->sum(fn (string $status): int => (int) ($counts[$status] ?? 0));

        return [
            ['label' => 'Menunggu Stok', 'value' => $bucket([OrderStatus::Processing->value]), 'color' => '#f59e0b'],
            ['label' => 'Sebagian Ready', 'value' => $bucket([OrderStatus::PartialReady->value]), 'color' => '#8b5cf6'],
            ['label' => 'Siap Kirim', 'value' => $bucket([OrderStatus::Ready->value]), 'color' => '#2560e8'],
            ['label' => 'Sebagian Terkirim', 'value' => $bucket([OrderStatus::PartialShipped->value]), 'color' => '#06b6d4'],
            ['label' => 'Terkirim', 'value' => $bucket([OrderStatus::FullyShipped->value, OrderStatus::Completed->value]), 'color' => '#10b981'],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function recentOrders(DashboardScope $scope, int $limit = 6): array
    {
        return $this->orderRows(
            $scope->orders(Order::query())->orderByDesc('order_date')->orderByDesc('id'),
            $limit,
        );
    }

    /**
     * @param  list<string>  $statuses
     * @return array<int, array<string, mixed>>
     */
    public function ordersByStatus(array $statuses, DashboardScope $scope, int $limit = 6): array
    {
        return $this->orderRows(
            $scope->orders(Order::query())
                ->whereIn('status', $statuses)
                ->orderByDesc('order_date')
                ->orderByDesc('id'),
            $limit,
        );
    }

    /**
     * @param  Builder<Order>  $query
     * @return array<int, array<string, mixed>>
     */
    private function orderRows(Builder $query, int $limit): array
    {
        return $query
            ->with(['school:id,name', 'orderSource:id,name'])
            ->limit($limit)
            ->get()
            ->map(fn (Order $order): array => [
                'id' => $order->getKey(),
                'number' => $order->number,
                'date' => $order->order_date->toDateString(),
                'school' => $order->school?->name,
                'source' => $order->orderSource?->name,
                'status' => $order->status->value,
                'status_label' => $order->status->label(),
                'total' => $order->total_amount,
                'due_date' => null,
            ])
            ->all();
    }

    public function activeBackorderCount(DashboardScope $scope): int
    {
        return $this->backorderQuery($scope)->count();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function backorders(DashboardScope $scope, int $limit = 6): array
    {
        return $this->backorderQuery($scope)
            ->with(['orderItem.order:id,number,school_id', 'orderItem.order.school:id,name', 'orderItem.product:id,title'])
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (Backorder $backorder): array => [
                'id' => $backorder->getKey(),
                'number' => $backorder->orderItem?->order?->number,
                'school' => $backorder->orderItem?->order?->school?->name,
                'product' => $backorder->orderItem?->product?->title,
                'quantity' => max(0, $backorder->quantity - $backorder->fulfilled_quantity),
                'status' => $backorder->status->value,
                'status_label' => $backorder->status->label(),
            ])
            ->all();
    }

    /**
     * @return Builder<Backorder>
     */
    private function backorderQuery(DashboardScope $scope): Builder
    {
        $query = Backorder::query()->whereIn('status', [
            BackorderStatus::Open->value,
            BackorderStatus::Partial->value,
        ]);

        if (! $scope->isRestricted()) {
            return $query;
        }

        return $query->whereHas('orderItem.order', fn (Builder $order) => $order->whereIn(
            'school_id',
            fn ($sub) => $sub->select('id')->from('schools')->where('sales_user_id', $scope->ownerId),
        ));
    }

    // ----------------------------------------------------------------- stock

    /**
     * @return array{low: int, out: int}
     */
    public function stockAlerts(): array
    {
        return ['low' => 0, 'out' => 0];
    }

    /**
     * Lowest-availability titles first.
     *
     * @return array<int, array<string, mixed>>
     */
    public function stockRows(int $limit = 6): array
    {
        return [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function stockMovements(int $limit = 8): array
    {
        return [];
    }

    // ----------------------------------------------------------- fulfillment

    /**
     * @return array{picking: int, packing: int, ready_to_ship: int, shipped: int}
     */
    public function fulfillmentQueues(DashboardScope $scope): array
    {
        $pickings = fn (array $statuses): int => $scope->pickings(Picking::query())
            ->whereIn('status', $statuses)
            ->count();

        return [
            'picking' => $pickings([PickingStatus::Draft->value, PickingStatus::Picking->value]),
            'packing' => $pickings([PickingStatus::Picked->value]),
            'ready_to_ship' => $scope->shipments(Shipment::query())
                ->whereIn('status', [ShipmentStatus::Draft->value, ShipmentStatus::ReadyToShip->value])
                ->count(),
            'shipped' => $scope->shipments(Shipment::query())
                ->whereIn('status', [ShipmentStatus::Shipped->value, ShipmentStatus::Delivered->value])
                ->count(),
        ];
    }

    /**
     * @return list<array{label: string, value: int, icon: string, tone: string}>
     */
    public function fulfillmentPipeline(DashboardScope $scope): array
    {
        $queues = $this->fulfillmentQueues($scope);

        return [
            [
                'label' => 'Pesanan Masuk',
                'value' => $scope->orders(Order::query())
                    ->whereNotIn('status', [OrderStatus::Draft->value, OrderStatus::Cancelled->value])
                    ->count(),
                'icon' => 'cart',
                'tone' => 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-300',
            ],
            [
                'label' => 'Siap Picking',
                'value' => $this->ordersWithStatus([OrderStatus::PartialReady->value, OrderStatus::Ready->value], $scope),
                'icon' => 'clipboard',
                'tone' => 'bg-violet-50 text-violet-600 dark:bg-violet-500/15 dark:text-violet-300',
            ],
            [
                'label' => 'Picking / Packing',
                'value' => $queues['picking'] + $queues['packing'],
                'icon' => 'boxes',
                'tone' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/15 dark:text-amber-300',
            ],
            [
                'label' => 'Siap Kirim',
                'value' => $queues['ready_to_ship'],
                'icon' => 'truck',
                'tone' => 'bg-sky-50 text-sky-600 dark:bg-sky-500/15 dark:text-sky-300',
            ],
            [
                'label' => 'Terkirim',
                'value' => $queues['shipped'],
                'icon' => 'checkCircle',
                'tone' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-300',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function pickupQueue(DashboardScope $scope, int $limit = 6): array
    {
        return $scope->pickings(Picking::query())
            ->with(['order:id,number,school_id', 'order.school:id,name'])
            ->whereIn('status', [PickingStatus::Draft->value, PickingStatus::Picking->value, PickingStatus::Picked->value])
            ->orderBy('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (Picking $picking): array => [
                'id' => $picking->getKey(),
                'number' => $picking->number,
                'order' => $picking->order?->number,
                'school' => $picking->order?->school?->name,
                'status' => $picking->status->value,
                'status_label' => $picking->status->label(),
                'date' => $picking->created_at?->toDateString(),
            ])
            ->all();
    }

    // --------------------------------------------------------------- finance

    /**
     * Outstanding receivables, floored per invoice so a credit never offsets
     * another invoice's debt.
     */
    public function outstandingTotal(DashboardScope $scope): string
    {
        $total = $this->receivableBase($scope)
            ->selectRaw('COALESCE(SUM(CASE WHEN total_amount - paid_amount > 0 THEN total_amount - paid_amount ELSE 0 END), 0) as total')
            ->value('total');

        return Money::add((string) $total, '0');
    }

    public function overdueTotal(DashboardScope $scope): string
    {
        $total = $this->overdueQuery($scope)
            ->selectRaw('COALESCE(SUM(total_amount - paid_amount), 0) as total')
            ->value('total');

        return Money::add((string) $total, '0');
    }

    public function overdueCount(DashboardScope $scope): int
    {
        return $this->overdueQuery($scope)->count();
    }

    public function invoiceTotal(Period $period, DashboardScope $scope, bool $previous = false): string
    {
        $start = $previous ? $period->previousStart : $period->start;
        $end = $previous ? $period->previousEnd : $period->end;

        return Money::add((string) $scope->invoices(Invoice::query())
            ->whereNotIn('status', [InvoiceStatus::Cancelled->value])
            ->whereDate('invoice_date', '>=', $start->toDateString())
            ->whereDate('invoice_date', '<=', $end->toDateString())
            ->sum('total_amount'), '0');
    }

    public function paymentTotal(Period $period, DashboardScope $scope, bool $previous = false): string
    {
        $start = $previous ? $period->previousStart : $period->start;
        $end = $previous ? $period->previousEnd : $period->end;

        return Money::add((string) $scope->payments(Payment::query())
            ->where('status', PaymentStatus::Verified->value)
            ->whereDate('payment_date', '>=', $start->toDateString())
            ->whereDate('payment_date', '<=', $end->toDateString())
            ->sum('amount'), '0');
    }

    public function paymentsAwaitingVerification(DashboardScope $scope): int
    {
        return $scope->payments(Payment::query())->where('status', PaymentStatus::Draft->value)->count();
    }

    public function issuedInvoiceCount(DashboardScope $scope): int
    {
        return $this->receivableBase($scope)->count();
    }

    /**
     * Mutually exclusive payment buckets. Precedence prevents double counting:
     * fully paid first, then overdue, then partially paid, then unpaid.
     *
     * @return array{paid: int, partial: int, unpaid: int, overdue: int}
     */
    public function invoicePaymentBuckets(DashboardScope $scope): array
    {
        $today = Carbon::today()->toDateString();

        $open = fn (): Builder => $this->receivableBase($scope)
            ->where('paid_amount', '>', 0)
            ->whereColumn('paid_amount', '<', 'total_amount')
            ->whereDate('due_date', '>=', $today);

        return [
            'paid' => $this->receivableBase($scope)->whereColumn('paid_amount', '>=', 'total_amount')->count(),
            'overdue' => $this->overdueQuery($scope)->count(),
            'partial' => $open()->count(),
            'unpaid' => $this->receivableBase($scope)
                ->where('paid_amount', '<=', 0)
                ->whereDate('due_date', '>=', $today)
                ->count(),
        ];
    }

    /**
     * @return list<array{label: string, value: int, color: string}>
     */
    public function invoiceStatusDistribution(DashboardScope $scope): array
    {
        $buckets = $this->invoicePaymentBuckets($scope);

        return [
            ['label' => 'Lunas', 'value' => $buckets['paid'], 'color' => '#10b981'],
            ['label' => 'Sebagian', 'value' => $buckets['partial'], 'color' => '#2560e8'],
            ['label' => 'Belum Bayar', 'value' => $buckets['unpaid'], 'color' => '#94a3b8'],
            ['label' => 'Terlambat', 'value' => $buckets['overdue'], 'color' => '#ef4444'],
        ];
    }

    /**
     * Receivables aging, bucketed by days past a positive due date.
     *
     * @return list<array{label: string, count: int, total: string}>
     */
    public function agingBuckets(DashboardScope $scope): array
    {
        $buckets = [
            'Belum jatuh tempo' => [null, null],
            '1 – 30 hari' => [1, 30],
            '31 – 60 hari' => [31, 60],
            '> 60 hari' => [61, null],
        ];

        $rows = [];
        $today = Carbon::today();

        foreach ($buckets as $label => [$min, $max]) {
            $query = $this->receivableBase($scope)->whereColumn('paid_amount', '<', 'total_amount');

            if ($min === null) {
                $query->whereDate('due_date', '>=', $today->toDateString());
            } else {
                $query->whereDate('due_date', '<', $today->toDateString());

                if ($max === null) {
                    $query->whereDate('due_date', '<=', $today->copy()->subDays($min)->toDateString());
                } else {
                    $query->whereDate('due_date', '<=', $today->copy()->subDays($min)->toDateString())
                        ->whereDate('due_date', '>', $today->copy()->subDays($max + 1)->toDateString());
                }
            }

            $rows[] = [
                'label' => $label,
                'count' => $query->count(),
                'total' => Money::add((string) $query->sum(DB::raw('total_amount - paid_amount')), '0'),
            ];
        }

        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function overdueInvoices(DashboardScope $scope, int $limit = 6): array
    {
        $today = Carbon::today()->startOfDay();

        return $this->overdueQuery($scope)
            ->with('school:id,name')
            ->orderBy('due_date')
            ->limit($limit)
            ->get(['id', 'number', 'school_id', 'total_amount', 'paid_amount', 'due_date', 'status'])
            ->map(fn (Invoice $invoice): array => [
                'id' => $invoice->getKey(),
                'number' => $invoice->number,
                'school' => $invoice->school?->name,
                'outstanding' => $invoice->outstandingAmount(),
                'due_date' => $invoice->due_date->toDateString(),
                'days_overdue' => (int) $invoice->due_date->diffInDays($today),
                'status' => $invoice->status->value,
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function upcomingDueInvoices(DashboardScope $scope, int $limit = 6): array
    {
        $today = Carbon::today()->startOfDay();

        return $this->receivableBase($scope)
            ->whereColumn('paid_amount', '<', 'total_amount')
            ->whereDate('due_date', '>=', $today->toDateString())
            ->with('school:id,name')
            ->orderBy('due_date')
            ->limit($limit)
            ->get(['id', 'number', 'school_id', 'total_amount', 'paid_amount', 'due_date', 'status'])
            ->map(fn (Invoice $invoice): array => [
                'id' => $invoice->getKey(),
                'number' => $invoice->number,
                'school' => $invoice->school?->name,
                'outstanding' => $invoice->outstandingAmount(),
                'due_date' => $invoice->due_date->toDateString(),
                'days_until_due' => (int) $today->diffInDays($invoice->due_date),
                'status' => $invoice->status->value,
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function recentPayments(DashboardScope $scope, int $limit = 6): array
    {
        return $scope->payments(Payment::query())
            ->with('school:id,name')
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (Payment $payment): array => [
                'id' => $payment->getKey(),
                'number' => $payment->number,
                'school' => $payment->school?->name,
                'amount' => $payment->amount,
                'date' => $payment->payment_date->toDateString(),
                'status' => $payment->status->value,
                'status_label' => $payment->status->label(),
            ])
            ->all();
    }

    /**
     * Rekap rebate sekolah: nilai diskon otomatis dari schools.rebate_percent
     * yang tersimpan di orders.discount_amount.
     *
     * @return array<string, string>
     */
    public function rebateSummary(Period $period, DashboardScope $scope): array
    {
        $orders = $scope->orders(Order::query())
            ->where('discount_percent', '>', 0)
            ->whereDate('order_date', '>=', $period->start->toDateString())
            ->whereDate('order_date', '<=', $period->end->toDateString());

        return [
            'count' => (string) (clone $orders)->count(),
            'amount' => (string) ((clone $orders)->sum('discount_amount') ?: '0'),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function rebates(DashboardScope $scope, int $limit = 6): array
    {
        return $scope->orders(Order::query())
            ->with('school:id,name')
            ->where('discount_percent', '>', 0)
            ->orderByDesc('order_date')
            ->limit($limit)
            ->get()
            ->map(fn (Order $order): array => [
                'id' => $order->getKey(),
                'number' => $order->number,
                'school' => $order->school?->name,
                'amount' => $order->discount_amount,
                'percent' => $order->discount_percent,
                'date' => $order->order_date->toDateString(),
            ])
            ->all();
    }

    // --------------------------------------------------------- sales / trends

    /**
     * Shipped value per school. This is the shared definition of "Penjualan".
     *
     * @return array<int, array{label: string, value: float}>
     */
    public function salesBySchool(Period $period, DashboardScope $scope, int $limit = 8): array
    {
        $query = $this->shippedValueQuery($period)
            ->join('schools', 'schools.id', '=', 'orders.school_id')
            ->groupBy('schools.id', 'schools.name')
            ->selectRaw('schools.name as label, SUM(order_items.unit_price * order_items.shipped_quantity) as value')
            ->orderByDesc('value')
            ->limit($limit);

        if ($scope->isRestricted()) {
            $query->where('schools.sales_user_id', $scope->ownerId);
        }

        return $query->get()
            ->map(fn (object $row): array => [
                'label' => (string) $row->label,
                'value' => (float) $row->value,
            ])
            ->all();
    }

    /**
     * @return array<int, array{label: string, value: float, share: float}>
     */
    public function salesByPublisher(Period $period, DashboardScope $scope): array
    {
        $query = $this->shippedValueQuery($period)
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->join('books', 'books.product_id', '=', 'products.id')
            ->join('publishers', 'publishers.id', '=', 'books.publisher_id')
            ->groupBy('publishers.id', 'publishers.name')
            ->selectRaw('publishers.name as label, SUM(order_items.unit_price * order_items.shipped_quantity) as value')
            ->orderByDesc('value');

        if ($scope->isRestricted()) {
            $query->join('schools', 'schools.id', '=', 'orders.school_id')
                ->where('schools.sales_user_id', $scope->ownerId);
        }

        $rows = $query->get();
        $total = (float) $rows->sum('value');

        return $rows->map(fn (object $row): array => [
            'label' => (string) $row->label,
            'value' => (float) $row->value,
            'share' => $total > 0 ? ((float) $row->value / $total) * 100 : 0.0,
        ])->all();
    }

    /**
     * Invoiced versus verified payments across the period.
     *
     * @return array{labels: list<string>, invoiced: list<float>, paid: list<float>, granularity: string}
     */
    public function salesSeries(Period $period, DashboardScope $scope): array
    {
        $invoices = $scope->invoices(Invoice::query())
            ->whereNotIn('status', [InvoiceStatus::Cancelled->value])
            ->whereDate('invoice_date', '>=', $period->start->toDateString())
            ->whereDate('invoice_date', '<=', $period->end->toDateString())
            ->selectRaw('invoice_date as d, SUM(total_amount) as total')
            ->groupBy('invoice_date')
            ->pluck('total', 'd')
            ->mapWithKeys(fn (mixed $total, mixed $day): array => [substr((string) $day, 0, 10) => $total]);

        $payments = $scope->payments(Payment::query())
            ->where('status', PaymentStatus::Verified->value)
            ->whereDate('payment_date', '>=', $period->start->toDateString())
            ->whereDate('payment_date', '<=', $period->end->toDateString())
            ->selectRaw('payment_date as d, SUM(amount) as total')
            ->groupBy('payment_date')
            ->pluck('total', 'd')
            ->mapWithKeys(fn (mixed $total, mixed $day): array => [substr((string) $day, 0, 10) => $total]);

        $labels = [];
        $invoiced = [];
        $paid = [];
        $monthly = $period->isMonthly();

        $cursor = $period->start->copy();

        while ($cursor->lessThanOrEqualTo($period->end)) {
            if ($monthly) {
                $prefix = $cursor->format('Y-m');
                $labels[] = self::MONTHS[(int) $cursor->format('n') - 1].' '.$cursor->format('y');
                $invoiced[] = $this->sumMatchingMonth($invoices, $prefix);
                $paid[] = $this->sumMatchingMonth($payments, $prefix);
                $cursor->addMonthNoOverflow()->startOfMonth();
            } else {
                $key = $cursor->toDateString();
                $labels[] = $cursor->format('j').' '.self::MONTHS[(int) $cursor->format('n') - 1];
                $invoiced[] = (float) ($invoices[$key] ?? 0);
                $paid[] = (float) ($payments[$key] ?? 0);
                $cursor->addDay();
            }
        }

        return [
            'labels' => $labels,
            'invoiced' => $invoiced,
            'paid' => $paid,
            'granularity' => $monthly ? 'bulan' : 'hari',
        ];
    }

    /**
     * @param  Collection<string, mixed>  $rows
     */
    private function sumMatchingMonth(Collection $rows, string $prefix): float
    {
        $total = 0.0;

        foreach ($rows as $date => $value) {
            if (str_starts_with((string) $date, $prefix)) {
                $total += (float) $value;
            }
        }

        return $total;
    }

    // ----------------------------------------------------------------- sales

    /**
     * @return array<int, array<string, mixed>>
     */
    public function assignedSchools(DashboardScope $scope, int $limit = 8): array
    {
        return $scope->schools(School::query())
            ->where('status', 'active')
            ->withCount(['orders' => fn (Builder $query) => $query->whereNotIn('status', [
                OrderStatus::Cancelled->value,
            ])])
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'district', 'regency', 'status'])
            ->map(fn (School $school): array => [
                'id' => $school->getKey(),
                'name' => $school->name,
                'district' => $school->district,
                'regency' => $school->regency,
                'orders_count' => (int) $school->getAttribute('orders_count'),
            ])
            ->all();
    }

    /**
     * Assigned schools that have not ordered within the given window.
     *
     * @return array<int, array<string, mixed>>
     */
    public function schoolsWithoutRecentOrders(DashboardScope $scope, int $days = 45, int $limit = 6): array
    {
        $cutoff = Carbon::today()->subDays($days)->toDateString();

        return $scope->schools(School::query())
            ->where('status', 'active')
            ->whereDoesntHave('orders', fn (Builder $query) => $query
                ->whereNotIn('status', [OrderStatus::Cancelled->value])
                ->whereDate('order_date', '>=', $cutoff))
            ->withMax('orders as last_order_date', 'order_date')
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'district', 'regency'])
            ->map(fn (School $school): array => [
                'id' => $school->getKey(),
                'name' => $school->name,
                'district' => $school->district,
                'last_order_date' => $school->getAttribute('last_order_date'),
            ])
            ->all();
    }

    // ------------------------------------------------------------- activity

    /**
     * @return array<int, array<string, mixed>>
     */
    public function recentActivity(int $limit = 8): array
    {
        return AuditLog::query()
            ->with('user:id,name')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (AuditLog $log): array => [
                'id' => $log->getKey(),
                'time' => $log->created_at?->toDateTimeString(),
                'action' => $log->action,
                'description' => $log->description,
                'user' => $log->user?->name,
            ])
            ->all();
    }

    // ---------------------------------------------------- administration

    /**
     * Roles with their user and permission counts, for the admin overview.
     *
     * @return array<int, array{name: string, slug: string, users: int, permissions: int}>
     */
    public function roleBreakdown(): array
    {
        return Role::query()
            ->withCount(['users', 'permissions'])
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role): array => [
                'name' => $role->name,
                'slug' => $role->slug,
                'users' => (int) $role->getAttribute('users_count'),
                'permissions' => (int) $role->getAttribute('permissions_count'),
            ])
            ->all();
    }

    /**
     * @return array{draft: int, confirmed: int}
     */
    public function receiptCounts(): array
    {
        $counts = DB::table('goods_receipts')
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'draft' => (int) ($counts[GoodsReceiptStatus::Draft->value] ?? 0),
            'confirmed' => (int) ($counts[GoodsReceiptStatus::Confirmed->value] ?? 0),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function recentReceipts(int $limit = 6): array
    {
        return DB::table('goods_receipts')
            ->leftJoin('publishers', 'publishers.id', '=', 'goods_receipts.publisher_id')
            ->orderByDesc('goods_receipts.receipt_date')
            ->orderByDesc('goods_receipts.id')
            ->limit($limit)
            ->get([
                'goods_receipts.id',
                'goods_receipts.number',
                'goods_receipts.receipt_date',
                'goods_receipts.status',
                'publishers.name as publisher',
            ])
            ->map(fn (object $row): array => [
                'id' => (int) $row->id,
                'number' => (string) $row->number,
                'date' => substr((string) $row->receipt_date, 0, 10),
                'publisher' => $row->publisher === null ? null : (string) $row->publisher,
                'status' => (string) $row->status,
                'status_label' => GoodsReceiptStatus::from((string) $row->status)->label(),
            ])
            ->all();
    }

    // ------------------------------------------------------------- sales

    /**
     * Total shipped value for the period - the shared "Penjualan" definition.
     */
    public function salesTotal(Period $period, DashboardScope $scope, bool $previous = false): string
    {
        $start = $previous ? $period->previousStart : $period->start;
        $end = $previous ? $period->previousEnd : $period->end;

        $query = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereNotIn('orders.status', [OrderStatus::Cancelled->value, OrderStatus::Draft->value])
            ->where('order_items.shipped_quantity', '>', 0)
            ->whereDate('orders.order_date', '>=', $start->toDateString())
            ->whereDate('orders.order_date', '<=', $end->toDateString());

        if ($scope->isRestricted()) {
            $query->join('schools', 'schools.id', '=', 'orders.school_id')
                ->where('schools.sales_user_id', $scope->ownerId);
        }

        return Money::add((string) $query->sum(DB::raw('order_items.unit_price * order_items.shipped_quantity')), '0');
    }

    // -------------------------------------------------------------- helpers

    /**
     * @return Builder<Invoice>
     */
    private function receivableBase(DashboardScope $scope): Builder
    {
        return $scope->invoices(Invoice::query())
            ->whereNotIn('status', [InvoiceStatus::Cancelled->value, InvoiceStatus::Draft->value]);
    }

    /**
     * @return Builder<Invoice>
     */
    private function overdueQuery(DashboardScope $scope): Builder
    {
        return $this->receivableBase($scope)
            ->whereDate('due_date', '<', Carbon::today()->toDateString())
            ->whereColumn('paid_amount', '<', 'total_amount');
    }

    private function shippedValueQuery(Period $period): \Illuminate\Database\Query\Builder
    {
        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereNotIn('orders.status', [OrderStatus::Cancelled->value, OrderStatus::Draft->value])
            ->where('order_items.shipped_quantity', '>', 0)
            ->whereDate('orders.order_date', '>=', $period->start->toDateString())
            ->whereDate('orders.order_date', '<=', $period->end->toDateString());
    }
}
