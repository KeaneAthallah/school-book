<?php

namespace App\Services;

use App\Enums\BackorderStatus;
use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Support\Money;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * @return array<string, string>
     */
    public static function types(): array
    {
        return [
            'sales_by_school' => 'Penjualan per Sekolah',
            'sales_by_publisher' => 'Penjualan per Penerbit',
            'sales_by_book' => 'Penjualan per Barang',
            'order_summary' => 'Ringkasan Pesanan',
            'receivables' => 'Piutang per Sekolah',
            'overdue_invoices' => 'Invoice Jatuh Tempo',
            'backorders' => 'Back Order',
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{columns: list<array{key: string, label: string, type?: string}>, rows: array<int, array<string, mixed>>}
     */
    public function build(string $type, array $filters = []): array
    {
        return match ($type) {
            'sales_by_school' => $this->salesBySchool($filters),
            'sales_by_publisher' => $this->salesByPublisher($filters),
            'sales_by_book' => $this->salesByBook($filters),
            'order_summary' => $this->orderSummary($filters),
            'receivables' => $this->receivables(),
            'overdue_invoices' => $this->overdueInvoices(),
            'backorders' => $this->backorders(),
            default => ['columns' => [], 'rows' => []],
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{columns: list<array{key: string, label: string, type?: string}>, rows: array<int, array<string, mixed>>}
     */
    private function salesBySchool(array $filters): array
    {
        $rows = $this->salesQuery($filters)
            ->join('schools', 'schools.id', '=', 'orders.school_id')
            ->groupBy('schools.id', 'schools.name')
            ->selectRaw('schools.name as name, SUM(order_items.shipped_quantity) as quantity, SUM(order_items.unit_price * order_items.shipped_quantity) as value')
            ->orderByDesc('value')
            ->get();

        return $this->table(
            [['key' => 'name', 'label' => 'Sekolah'], ['key' => 'quantity', 'label' => 'Jumlah', 'type' => 'number'], ['key' => 'value', 'label' => 'Nilai', 'type' => 'currency']],
            $rows->map(fn (object $row): array => [
                'name' => (string) $row->name,
                'quantity' => (int) $row->quantity,
                'value' => Money::add((string) $row->value, '0'),
            ])->all(),
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{columns: list<array{key: string, label: string, type?: string}>, rows: array<int, array<string, mixed>>}
     */
    private function salesByPublisher(array $filters): array
    {
        $rows = $this->salesQuery($filters)
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->join('books', 'books.product_id', '=', 'products.id')
            ->join('publishers', 'publishers.id', '=', 'books.publisher_id')
            ->groupBy('publishers.id', 'publishers.name')
            ->selectRaw('publishers.name as name, SUM(order_items.shipped_quantity) as quantity, SUM(order_items.unit_price * order_items.shipped_quantity) as value')
            ->orderByDesc('value')
            ->get();

        return $this->table(
            [['key' => 'name', 'label' => 'Penerbit'], ['key' => 'quantity', 'label' => 'Jumlah', 'type' => 'number'], ['key' => 'value', 'label' => 'Nilai', 'type' => 'currency']],
            $rows->map(fn (object $row): array => [
                'name' => (string) $row->name,
                'quantity' => (int) $row->quantity,
                'value' => Money::add((string) $row->value, '0'),
            ])->all(),
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{columns: list<array{key: string, label: string, type?: string}>, rows: array<int, array<string, mixed>>}
     */
    private function salesByBook(array $filters): array
    {
        $rows = $this->salesQuery($filters)
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->groupBy('products.id', 'products.title', 'products.code')
            ->selectRaw('products.code as code, products.title as title, SUM(order_items.shipped_quantity) as quantity, SUM(order_items.unit_price * order_items.shipped_quantity) as value')
            ->orderByDesc('value')
            ->get();

        return $this->table(
            [['key' => 'code', 'label' => 'Kode'], ['key' => 'title', 'label' => 'Judul'], ['key' => 'quantity', 'label' => 'Jumlah', 'type' => 'number'], ['key' => 'value', 'label' => 'Nilai', 'type' => 'currency']],
            $rows->map(fn (object $row): array => [
                'code' => (string) $row->code,
                'title' => (string) $row->title,
                'quantity' => (int) $row->quantity,
                'value' => Money::add((string) $row->value, '0'),
            ])->all(),
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{columns: list<array{key: string, label: string, type?: string}>, rows: array<int, array<string, mixed>>}
     */
    private function orderSummary(array $filters): array
    {
        $query = DB::table('orders')
            ->join('schools', 'schools.id', '=', 'orders.school_id')
            ->leftJoin('order_items', 'order_items.order_id', '=', 'orders.id')
            ->selectRaw('orders.number as number, orders.order_date as order_date, schools.name as school, orders.status as status, SUM(order_items.quantity) as ordered, SUM(order_items.shipped_quantity) as shipped, orders.total_amount as total')
            ->groupBy('orders.id', 'orders.number', 'orders.order_date', 'schools.name', 'orders.status', 'orders.total_amount')
            ->orderByDesc('orders.order_date');

        $this->applyDates($query, $filters);

        return $this->table(
            [
                ['key' => 'number', 'label' => 'No. Pesanan'],
                ['key' => 'order_date', 'label' => 'Tanggal'],
                ['key' => 'school', 'label' => 'Sekolah'],
                ['key' => 'status', 'label' => 'Status'],
                ['key' => 'ordered', 'label' => 'Dipesan', 'type' => 'number'],
                ['key' => 'shipped', 'label' => 'Terkirim', 'type' => 'number'],
                ['key' => 'total', 'label' => 'Nilai', 'type' => 'currency'],
            ],
            $query->get()->map(fn (object $row): array => [
                'number' => (string) $row->number,
                'order_date' => (string) $row->order_date,
                'school' => (string) $row->school,
                'status' => (string) $row->status,
                'ordered' => (int) $row->ordered,
                'shipped' => (int) $row->shipped,
                'total' => (string) $row->total,
            ])->all(),
        );
    }

    /**
     * @return array{columns: list<array{key: string, label: string, type?: string}>, rows: array<int, array<string, mixed>>}
     */
    private function receivables(): array
    {
        $rows = DB::table('invoices')
            ->join('schools', 'schools.id', '=', 'invoices.school_id')
            ->whereNotIn('invoices.status', [InvoiceStatus::Cancelled->value, InvoiceStatus::Draft->value])
            ->groupBy('schools.id', 'schools.name')
            ->selectRaw('schools.name as school, SUM(invoices.total_amount) as total, SUM(invoices.paid_amount) as paid, SUM(invoices.total_amount - invoices.paid_amount) as outstanding')
            ->orderByDesc('outstanding')
            ->get();

        return $this->table(
            [
                ['key' => 'school', 'label' => 'Sekolah'],
                ['key' => 'total', 'label' => 'Total Invoice', 'type' => 'currency'],
                ['key' => 'paid', 'label' => 'Dibayar', 'type' => 'currency'],
                ['key' => 'outstanding', 'label' => 'Sisa Piutang', 'type' => 'currency'],
            ],
            $rows->map(fn (object $row): array => [
                'school' => (string) $row->school,
                'total' => Money::add((string) $row->total, '0'),
                'paid' => Money::add((string) $row->paid, '0'),
                'outstanding' => Money::add((string) $row->outstanding, '0'),
            ])->all(),
        );
    }

    /**
     * @return array{columns: list<array{key: string, label: string, type?: string}>, rows: array<int, array<string, mixed>>}
     */
    private function overdueInvoices(): array
    {
        $rows = DB::table('invoices')
            ->join('schools', 'schools.id', '=', 'invoices.school_id')
            ->whereNotIn('invoices.status', [InvoiceStatus::Cancelled->value, InvoiceStatus::Draft->value])
            ->whereDate('invoices.due_date', '<', now()->toDateString())
            ->whereColumn('invoices.paid_amount', '<', 'invoices.total_amount')
            ->selectRaw('invoices.number as number, invoices.due_date as due_date, schools.name as school, invoices.total_amount as total, invoices.paid_amount as paid, (invoices.total_amount - invoices.paid_amount) as outstanding')
            ->orderBy('invoices.due_date')
            ->get();

        return $this->table(
            [
                ['key' => 'number', 'label' => 'No. Invoice'],
                ['key' => 'due_date', 'label' => 'Jatuh Tempo'],
                ['key' => 'school', 'label' => 'Sekolah'],
                ['key' => 'total', 'label' => 'Total', 'type' => 'currency'],
                ['key' => 'outstanding', 'label' => 'Sisa', 'type' => 'currency'],
            ],
            $rows->map(fn (object $row): array => [
                'number' => (string) $row->number,
                'due_date' => (string) $row->due_date,
                'school' => (string) $row->school,
                'total' => Money::add((string) $row->total, '0'),
                'outstanding' => Money::add((string) $row->outstanding, '0'),
            ])->all(),
        );
    }

    /**
     * @return array{columns: list<array{key: string, label: string, type?: string}>, rows: array<int, array<string, mixed>>}
     */
    private function backorders(): array
    {
        $rows = DB::table('backorders')
            ->join('order_items', 'order_items.id', '=', 'backorders.order_item_id')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'backorders.product_id')
            ->join('schools', 'schools.id', '=', 'orders.school_id')
            ->whereIn('backorders.status', [BackorderStatus::Open->value, BackorderStatus::Partial->value])
            ->selectRaw('orders.number as number, orders.order_date as order_date, schools.name as school, products.title as product, (backorders.quantity - backorders.fulfilled_quantity) as outstanding')
            ->orderBy('orders.order_date')
            ->get();

        return $this->table(
            [
                ['key' => 'number', 'label' => 'No. Pesanan'],
                ['key' => 'order_date', 'label' => 'Tanggal'],
                ['key' => 'school', 'label' => 'Sekolah'],
                ['key' => 'product', 'label' => 'Barang'],
                ['key' => 'outstanding', 'label' => 'Sisa', 'type' => 'number'],
            ],
            $rows->map(fn (object $row): array => [
                'number' => (string) $row->number,
                'order_date' => (string) $row->order_date,
                'school' => (string) $row->school,
                'product' => (string) $row->product,
                'outstanding' => (int) $row->outstanding,
            ])->all(),
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function salesQuery(array $filters): Builder
    {
        $query = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereNotIn('orders.status', [OrderStatus::Cancelled->value, OrderStatus::Draft->value])
            ->where('order_items.shipped_quantity', '>', 0);

        $this->applyDates($query, $filters);

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyDates(Builder $query, array $filters): void
    {
        $from = $filters['date_from'] ?? null;
        $to = $filters['date_to'] ?? null;

        if (is_string($from) && $from !== '') {
            $query->whereDate('orders.order_date', '>=', $from);
        }

        if (is_string($to) && $to !== '') {
            $query->whereDate('orders.order_date', '<=', $to);
        }
    }

    /**
     * @param  list<array{key: string, label: string, type?: string}>  $columns
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{columns: list<array{key: string, label: string, type?: string}>, rows: array<int, array<string, mixed>>}
     */
    private function table(array $columns, array $rows): array
    {
        return ['columns' => $columns, 'rows' => $rows];
    }
}
