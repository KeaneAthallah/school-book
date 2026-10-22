<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Removes ONLY the records created by DashboardDemoSeeder, identified by the
 * demo markers it writes. It never touches records outside those markers.
 *
 * Guarded exactly like the demo seeder: it refuses to run in production.
 *
 * Run with: php artisan db:seed --class=DemoDataResetSeeder
 */
class DemoDataResetSeeder extends Seeder
{
    private const PREFIX = '9990';

    private const PUBLISHER_EMAIL = 'kontak@demo-penerbit.test';

    private const RECEIPT_NOTES = ['Stok awal demo', 'Menunggu pemeriksaan gudang'];

    /**
     * The console command when the seeder runs through `db:seed`.
     * Null when a seeder is invoked directly (for example from a test).
     */
    private function console(): ?Command
    {
        /** @var Command|null $command */
        $command = $this->command;

        return $command;
    }

    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException(
                'DemoDataResetSeeder refuses to run: the application is in the production environment.'
            );
        }

        $environment = (string) config('app.env');

        if (! in_array($environment, ['local', 'development', 'testing'], true)) {
            throw new RuntimeException("DemoDataResetSeeder refuses to run in the '{$environment}' environment.");
        }

        $database = config('database.connections.'.config('database.default').'.database');

        $this->console()?->warn('Removing demo-only records from: '.$database);

        $schoolIds = DB::table('schools')->where('npsn', 'like', self::PREFIX.'%')->pluck('id')->all();
        $productIds = DB::table('products')->where('code', 'like', 'BK-DEMO-%')->pluck('id')->all();
        $orderIds = DB::table('orders')->where('external_source', 'DEMO')->pluck('id')->all();
        $receiptIds = DB::table('goods_receipts')->whereIn('notes', self::RECEIPT_NOTES)->pluck('id')->all();
        $publisherIds = DB::table('publishers')->where('email', self::PUBLISHER_EMAIL)->pluck('id')->all();

        // Demo orders may point at real schools (the seeder prefers existing
        // master data), so invoices and payments cannot be found through the
        // demo-school marker alone. Resolve them through the demo orders too.
        $invoiceIds = DB::table('invoices')
            ->whereIn('school_id', $schoolIds)
            ->orWhereIn('order_id', $orderIds)
            ->pluck('id')
            ->all();

        $paymentIds = DB::table('payments')
            ->whereIn('school_id', $schoolIds)
            ->orWhere('reference', 'like', 'DEMO-%')
            ->orWhereIn('id', fn ($query) => $query
                ->select('payment_id')
                ->from('payment_allocations')
                ->whereIn('invoice_id', $invoiceIds))
            ->pluck('id')
            ->all();

        $deleted = [];

        DB::transaction(function () use ($schoolIds, $productIds, $orderIds, $receiptIds, $invoiceIds, $paymentIds, &$deleted): void {
            $step = function (string $table, callable $query) use (&$deleted): void {
                if (! Schema::hasTable($table)) {
                    return;
                }
                $count = $query()->delete();
                $deleted[$table] = ($deleted[$table] ?? 0) + $count;
            };

            // Financial records first (child -> parent).
            $step('payment_allocations', fn () => DB::table('payment_allocations')->whereIn('payment_id', $paymentIds));
            $step('payments', fn () => DB::table('payments')->whereIn('id', $paymentIds));
            if (Schema::hasTable('rebates')) {
                $step('rebates', fn () => DB::table('rebates')->whereIn('school_id', $schoolIds));
            }
            $step('invoice_items', fn () => DB::table('invoice_items')->whereIn('invoice_id', $invoiceIds));
            $step('invoices', fn () => DB::table('invoices')->whereIn('id', $invoiceIds));

            // Fulfilment.
            $step('shipment_items', fn () => DB::table('shipment_items')
                ->whereIn('shipment_id', fn ($q) => $q->select('id')->from('shipments')->whereIn('order_id', $orderIds)));
            $step('shipments', fn () => DB::table('shipments')->whereIn('order_id', $orderIds));
            $step('picking_items', fn () => DB::table('picking_items')
                ->whereIn('picking_id', fn ($q) => $q->select('id')->from('pickings')->whereIn('order_id', $orderIds)));
            $step('pickings', fn () => DB::table('pickings')->whereIn('order_id', $orderIds));
            $step('backorders', fn () => DB::table('backorders')
                ->whereIn('order_item_id', fn ($q) => $q->select('id')->from('order_items')->whereIn('order_id', $orderIds)));
            $step('order_items', fn () => DB::table('order_items')->whereIn('order_id', $orderIds));
            $step('orders', fn () => DB::table('orders')->where('external_source', 'DEMO'));

            // Inventory.
            $step('stock_movements', fn () => DB::table('stock_movements')->whereIn('product_id', $productIds));
            $step('stocks', fn () => DB::table('stocks')->whereIn('product_id', $productIds));
            $step('goods_receipt_items', fn () => DB::table('goods_receipt_items')->whereIn('goods_receipt_id', $receiptIds));
            $step('goods_receipts', fn () => DB::table('goods_receipts')->whereIn('notes', self::RECEIPT_NOTES));

            // Master data (children before parents).
            $step('books', fn () => DB::table('books')->whereIn('product_id', $productIds));
            $step('products', fn () => DB::table('products')->where('code', 'like', 'BK-DEMO-%'));
            $step('book_categories', fn () => DB::table('book_categories')->where('slug', 'buku-pelajaran-demo'));
            $step('schools', fn () => DB::table('schools')->where('npsn', 'like', self::PREFIX.'%'));
            $step('publishers', fn () => DB::table('publishers')->where('email', self::PUBLISHER_EMAIL));

            // Sentinel last, so a crash mid-reset still reports as seeded.
            Setting::query()->where('key', 'demo.seeded_version')->delete();
        });

        foreach ($deleted as $table => $count) {
            $this->console()?->line(sprintf('  %-22s %d row(s) removed', $table, $count));
        }

        $this->console()?->info('Demo data removed. Run DashboardDemoSeeder to regenerate.');
    }
}
