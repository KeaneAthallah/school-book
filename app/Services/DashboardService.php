<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PickingStatus;
use App\Enums\ShipmentStatus;
use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Shipment;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Builds the dashboard payload.
 *
 * Period-scoped metrics respect the requested date range and are compared with
 * the immediately preceding period of equal length. Point-in-time metrics
 * (stock, receivables, overdue balances) are deliberately not ranged.
 */
class DashboardService
{
    private const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    /**
     * @return array<string, mixed>
     */
    public function report(?string $from = null, ?string $to = null): array
    {
        $end = $to !== null && $to !== '' ? Carbon::parse($to)->startOfDay() : Carbon::today();
        $start = $from !== null && $from !== '' ? Carbon::parse($from)->startOfDay() : $end->copy()->startOfMonth();

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end->copy(), $start->copy()];
        }

        $days = (int) $start->diffInDays($end) + 1;
        $previousEnd = $start->copy()->subDay();
        $previousStart = $previousEnd->copy()->subDays($days - 1);

        return [
            'range' => [
                'from' => $start->toDateString(),
                'to' => $end->toDateString(),
                'days' => $days,
                'label' => $start->format('d/m/Y').' – '.$end->format('d/m/Y'),
            ],
            'kpis' => $this->kpis($start, $end, $previousStart, $previousEnd),
            'sales_series' => $this->salesSeries($start, $end),
            'order_status' => $this->orderStatusDistribution(),
            'invoice_status' => $this->invoiceStatusDistribution(),
            'recent_orders' => $this->recentOrders(),
            'top_books' => $this->topBooks(),
            'overdue_receivables' => $this->overdueReceivables(),
            'fulfillment' => $this->fulfillment(),
            'today_shipments' => $this->todayShipments(),
            'sales_by_school' => $this->salesBySchool($start, $end),
            'sales_by_publisher' => $this->salesByPublisher($start, $end),
            'recent_activity' => $this->recentActivity(),
        ];
    }

    /**
     * @return array<string, array{value: int|string, previous: int|string, delta: float|null, format: string, count?: int}>
     */
    private function kpis(Carbon $start, Carbon $end, Carbon $previousStart, Carbon $previousEnd): array
    {
        $ordersNow = $this->ordersBetween($start, $end);
        $ordersPrevious = $this->ordersBetween($previousStart, $previousEnd);

        $invoicedNow = $this->invoiceTotalBetween($start, $end);
        $invoicedPrevious = $this->invoiceTotalBetween($previousStart, $previousEnd);

        $paidNow = $this->paidTotalBetween($start, $end);
        $paidPrevious = $this->paidTotalBetween($previousStart, $previousEnd);

        $outstanding = (string) Invoice::query()
            ->whereNotIn('status', [InvoiceStatus::Cancelled->value, InvoiceStatus::Draft->value])
            ->selectRaw('COALESCE(SUM(total_amount - paid_amount), 0) as total')
            ->value('total');

        $overdueQuery = $this->overdueInvoiceQuery();
        $overdueTotal = (string) (clone $overdueQuery)
            ->selectRaw('COALESCE(SUM(total_amount - paid_amount), 0) as total')
            ->value('total');

        return [
            'orders_received' => [
                'value' => $ordersNow,
                'previous' => $ordersPrevious,
                'delta' => $this->delta($ordersNow, $ordersPrevious),
                'format' => 'number',
            ],
            'ready_picking' => [
                'value' => Order::query()->whereIn('status', [OrderStatus::PartialReady->value, OrderStatus::Ready->value])->count(),
                'previous' => 0,
                'delta' => null,
                'format' => 'number',
            ],
            'awaiting_stock' => [
                'value' => Order::query()->where('status', OrderStatus::Processing->value)->count(),
                'previous' => 0,
                'delta' => null,
                'format' => 'number',
            ],
            'in_delivery' => [
                'value' => Shipment::query()->where('status', ShipmentStatus::Shipped->value)->count(),
                'previous' => 0,
                'delta' => null,
                'format' => 'number',
            ],
            'total_invoice' => [
                'value' => $invoicedNow,
                'previous' => $invoicedPrevious,
                'delta' => $this->delta((float) $invoicedNow, (float) $invoicedPrevious),
                'format' => 'currency',
            ],
            'paid' => [
                'value' => $paidNow,
                'previous' => $paidPrevious,
                'delta' => $this->delta((float) $paidNow, (float) $paidPrevious),
                'format' => 'currency',
            ],
            'receivable' => [
                'value' => Money::add($outstanding, '0'),
                'previous' => '0.00',
                'delta' => null,
                'format' => 'currency',
            ],
            'overdue' => [
                'value' => Money::add($overdueTotal, '0'),
                'previous' => '0.00',
                'delta' => null,
                'format' => 'currency',
                'count' => (clone $overdueQuery)->count(),
            ],
        ];
    }

    /**
     * @return array{labels: list<string>, invoiced: list<float>, paid: list<float>, granularity: string}
     */
    private function salesSeries(Carbon $start, Carbon $end): array
    {
        $monthly = (int) $start->diffInDays($end) > 62;

        $invoices = Invoice::query()
            ->whereNotIn('status', [InvoiceStatus::Cancelled->value])
            ->whereDate('invoice_date', '>=', $start->toDateString())
            ->whereDate('invoice_date', '<=', $end->toDateString())
            ->selectRaw('invoice_date as d, SUM(total_amount) as total')
            ->groupBy('invoice_date')
            ->pluck('total', 'd')
            ->mapWithKeys(fn (mixed $total, mixed $day): array => [substr((string) $day, 0, 10) => $total]);

        $payments = Payment::query()
            ->where('status', PaymentStatus::Verified->value)
            ->whereDate('payment_date', '>=', $start->toDateString())
            ->whereDate('payment_date', '<=', $end->toDateString())
            ->selectRaw('payment_date as d, SUM(amount) as total')
            ->groupBy('payment_date')
            ->pluck('total', 'd')
            ->mapWithKeys(fn (mixed $total, mixed $day): array => [substr((string) $day, 0, 10) => $total]);

        $labels = [];
        $invoiced = [];
        $paid = [];

        $cursor = $start->copy();

        while ($cursor->lessThanOrEqualTo($end)) {
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
    private function sumMatchingMonth($rows, string $prefix): float
    {
        $total = 0.0;

        foreach ($rows as $date => $value) {
            if (str_starts_with((string) $date, $prefix)) {
                $total += (float) $value;
            }
        }

        return $total;
    }

    /**
     * @return list<array{label: string, value: int, color: string}>
     */
    private function orderStatusDistribution(): array
    {
        $counts = Order::query()
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
     * Mutually exclusive: fully paid first, then overdue, then partially paid,
     * then unpaid. Precedence prevents double counting.
     *
     * @return list<array{label: string, value: int, color: string}>
     */
    private function invoiceStatusDistribution(): array
    {
        $buckets = ['paid' => 0, 'partial' => 0, 'unpaid' => 0, 'overdue' => 0];
        $today = now()->startOfDay();

        $invoices = Invoice::query()
            ->whereNotIn('status', [InvoiceStatus::Cancelled->value, InvoiceStatus::Draft->value])
            ->get(['total_amount', 'paid_amount', 'due_date']);

        foreach ($invoices as $invoice) {
            $bucket = match (true) {
                Money::compare($invoice->paid_amount, $invoice->total_amount) >= 0 => 'paid',
                $invoice->due_date->lessThan($today) => 'overdue',
                Money::compare($invoice->paid_amount, '0') > 0 => 'partial',
                default => 'unpaid',
            };

            $buckets[$bucket]++;
        }

        return [
            ['label' => 'Lunas', 'value' => $buckets['paid'], 'color' => '#10b981'],
            ['label' => 'Sebagian', 'value' => $buckets['partial'], 'color' => '#2560e8'],
            ['label' => 'Belum Bayar', 'value' => $buckets['unpaid'], 'color' => '#94a3b8'],
            ['label' => 'Terlambat', 'value' => $buckets['overdue'], 'color' => '#ef4444'],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentOrders(): array
    {
        return Order::query()
            ->with(['school:id,name', 'orderSource:id,name'])
            ->orderByDesc('order_date')
            ->orderByDesc('id')
            ->limit(6)
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
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function topBooks(): array
    {
        return DB::table('stocks')
            ->join('books', 'books.id', '=', 'stocks.book_id')
            ->groupBy('books.id', 'books.code', 'books.title', 'books.min_stock')
            ->selectRaw('books.code as code, books.title as title, books.min_stock as min_stock, SUM(stocks.quantity) as quantity, SUM(stocks.reserved_quantity) as reserved')
            ->orderByRaw('(SUM(stocks.quantity) - SUM(stocks.reserved_quantity)) asc')
            ->limit(5)
            ->get()
            ->map(function (object $row): array {
                $quantity = (int) $row->quantity;
                $reserved = (int) $row->reserved;
                $available = max(0, $quantity - $reserved);
                $minStock = (int) $row->min_stock;

                return [
                    'code' => (string) $row->code,
                    'title' => (string) $row->title,
                    'quantity' => $quantity,
                    'reserved' => $reserved,
                    'available' => $available,
                    'min_stock' => $minStock,
                    'state' => $available <= 0 ? 'out' : ($minStock > 0 && $quantity <= $minStock ? 'low' : 'ok'),
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function overdueReceivables(): array
    {
        $today = now()->startOfDay();

        return $this->overdueInvoiceQuery()
            ->with('school:id,name')
            ->orderBy('due_date')
            ->limit(6)
            ->get(['id', 'number', 'school_id', 'total_amount', 'paid_amount', 'due_date'])
            ->map(fn (Invoice $invoice): array => [
                'id' => $invoice->getKey(),
                'number' => $invoice->number,
                'school' => $invoice->school?->name,
                'outstanding' => $invoice->outstandingAmount(),
                'due_date' => $invoice->due_date->toDateString(),
                'days_overdue' => (int) $invoice->due_date->diffInDays($today),
            ])
            ->all();
    }

    /**
     * Pipeline stages are counted from different entities, so they are shown
     * as a progression rather than summed into a single total.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fulfillment(): array
    {
        return [
            [
                'label' => 'Pesanan Masuk',
                'value' => Order::query()->whereNotIn('status', [OrderStatus::Draft->value, OrderStatus::Cancelled->value])->count(),
                'icon' => 'cart',
                'tone' => 'bg-brand-50 text-brand-600',
            ],
            [
                'label' => 'Siap Picking',
                'value' => Order::query()->whereIn('status', [OrderStatus::PartialReady->value, OrderStatus::Ready->value])->count(),
                'icon' => 'clipboard',
                'tone' => 'bg-violet-50 text-violet-600',
            ],
            [
                'label' => 'Picking / Packing',
                'value' => (int) DB::table('pickings')->whereIn('status', [PickingStatus::Draft->value, PickingStatus::Picking->value, PickingStatus::Picked->value])->count(),
                'icon' => 'boxes',
                'tone' => 'bg-amber-50 text-amber-600',
            ],
            [
                'label' => 'Siap Kirim',
                'value' => Shipment::query()->whereIn('status', [ShipmentStatus::Draft->value, ShipmentStatus::ReadyToShip->value])->count(),
                'icon' => 'truck',
                'tone' => 'bg-sky-50 text-sky-600',
            ],
            [
                'label' => 'Terkirim',
                'value' => Shipment::query()->whereIn('status', [ShipmentStatus::Shipped->value, ShipmentStatus::Delivered->value])->count(),
                'icon' => 'checkCircle',
                'tone' => 'bg-emerald-50 text-emerald-600',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function todayShipments(): array
    {
        $today = now()->toDateString();

        return Shipment::query()
            ->with(['order:id,number,school_id', 'order.school:id,name'])
            ->withSum('items', 'quantity')
            ->where(function (Builder $query) use ($today): void {
                $query->whereDate('ship_date', $today)
                    ->orWhere(function (Builder $inner) use ($today): void {
                        $inner->whereNull('ship_date')->whereDate('created_at', $today);
                    });
            })
            ->orderByDesc('id')
            ->limit(6)
            ->get()
            ->map(fn (Shipment $shipment): array => [
                'id' => $shipment->getKey(),
                'number' => $shipment->number,
                'school' => $shipment->order?->school?->name,
                'quantity' => (int) ($shipment->getAttribute('items_sum_quantity') ?? 0),
                'status' => $shipment->status->value,
                'status_label' => $shipment->status->label(),
            ])
            ->all();
    }

    /**
     * @return array<int, array{label: string, value: float}>
     */
    private function salesBySchool(Carbon $start, Carbon $end): array
    {
        return $this->shippedValueQuery($start, $end)
            ->join('schools', 'schools.id', '=', 'orders.school_id')
            ->groupBy('schools.id', 'schools.name')
            ->selectRaw('schools.name as label, SUM(order_items.unit_price * order_items.shipped_quantity) as value')
            ->orderByDesc('value')
            ->limit(8)
            ->get()
            ->map(fn (object $row): array => [
                'label' => (string) $row->label,
                'value' => (float) $row->value,
            ])
            ->all();
    }

    /**
     * @return array<int, array{label: string, value: float, share: float}>
     */
    private function salesByPublisher(Carbon $start, Carbon $end): array
    {
        $rows = $this->shippedValueQuery($start, $end)
            ->join('books', 'books.id', '=', 'order_items.book_id')
            ->join('publishers', 'publishers.id', '=', 'books.publisher_id')
            ->groupBy('publishers.id', 'publishers.name')
            ->selectRaw('publishers.name as label, SUM(order_items.unit_price * order_items.shipped_quantity) as value')
            ->orderByDesc('value')
            ->get();

        $total = (float) $rows->sum('value');

        return $rows->map(fn (object $row): array => [
            'label' => (string) $row->label,
            'value' => (float) $row->value,
            'share' => $total > 0 ? ((float) $row->value / $total) * 100 : 0.0,
        ])->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentActivity(): array
    {
        return AuditLog::query()
            ->with('user:id,name')
            ->orderByDesc('id')
            ->limit(8)
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

    /**
     * @return Builder<Invoice>
     */
    private function overdueInvoiceQuery(): Builder
    {
        return Invoice::query()
            ->whereNotIn('status', [InvoiceStatus::Cancelled->value, InvoiceStatus::Draft->value])
            ->whereDate('due_date', '<', now()->toDateString())
            ->whereColumn('paid_amount', '<', 'total_amount');
    }

    private function shippedValueQuery(Carbon $start, Carbon $end): \Illuminate\Database\Query\Builder
    {
        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereNotIn('orders.status', [OrderStatus::Cancelled->value, OrderStatus::Draft->value])
            ->where('order_items.shipped_quantity', '>', 0)
            ->whereDate('orders.order_date', '>=', $start->toDateString())
            ->whereDate('orders.order_date', '<=', $end->toDateString());
    }

    private function ordersBetween(Carbon $start, Carbon $end): int
    {
        return Order::query()
            ->whereNotIn('status', [OrderStatus::Cancelled->value, OrderStatus::Draft->value])
            ->whereDate('order_date', '>=', $start->toDateString())
            ->whereDate('order_date', '<=', $end->toDateString())
            ->count();
    }

    private function invoiceTotalBetween(Carbon $start, Carbon $end): string
    {
        return Money::add((string) Invoice::query()
            ->whereNotIn('status', [InvoiceStatus::Cancelled->value])
            ->whereDate('invoice_date', '>=', $start->toDateString())
            ->whereDate('invoice_date', '<=', $end->toDateString())
            ->sum('total_amount'), '0');
    }

    private function paidTotalBetween(Carbon $start, Carbon $end): string
    {
        return Money::add((string) Payment::query()
            ->where('status', PaymentStatus::Verified->value)
            ->whereDate('payment_date', '>=', $start->toDateString())
            ->whereDate('payment_date', '<=', $end->toDateString())
            ->sum('amount'), '0');
    }

    private function delta(float|int $current, float|int $previous): ?float
    {
        if ((float) $previous === 0.0) {
            return null;
        }

        return (((float) $current - (float) $previous) / (float) $previous) * 100;
    }
}
