<?php

namespace Database\Seeders;

use App\Models\Backorder;
use App\Models\BackorderOrder;
use App\Models\BackorderOrderItem;
use App\Models\CashLoan;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCatalog;
use App\Models\StockMutation;
use App\Models\User;
use App\Services\ShipmentService;
use Illuminate\Database\Seeder;

class FullCoverageSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@sekolah.test')->first();

        // Cash loans
        if (CashLoan::query()->count() === 0) {
            CashLoan::query()->create([
                'number' => 'PIN-DEMO-001',
                'person_name' => 'Budi Santoso',
                'school_name' => 'SD Contoh 1',
                'loan_date' => now()->subDays(5)->toDateString(),
                'due_date' => now()->addDays(10)->toDateString(),
                'amount' => 500000,
                'notes' => 'Pinjaman tunai demo',
                'status' => 'open',
            ]);
            CashLoan::query()->create([
                'number' => 'PIN-DEMO-002',
                'person_name' => 'Siti Aminah',
                'school_name' => 'SD Contoh 2',
                'loan_date' => now()->subDays(10)->toDateString(),
                'due_date' => now()->subDays(2)->toDateString(),
                'amount' => 250000,
                'notes' => 'Pinjaman tunai demo 2',
                'status' => 'open',
            ]);
        }

        // Stock mutations
        if (StockMutation::query()->count() === 0) {
            $product = Product::query()->first();
            if ($product !== null) {
                StockMutation::query()->create([
                    'product_id' => $product->getKey(),
                    'type' => 'lost',
                    'quantity' => 2,
                    'reason' => 'Hilang demo',
                    'created_by' => $admin?->getKey(),
                ]);
                StockMutation::query()->create([
                    'product_id' => $product->getKey(),
                    'type' => 'damaged',
                    'quantity' => 1,
                    'reason' => 'Rusak demo',
                    'created_by' => $admin?->getKey(),
                ]);
            }
        }

        // One back-order purchase order to a publisher, so the procurement
        // history and its print layout have a real example.
        if (BackorderOrder::query()->count() === 0) {
            $backorder = Backorder::query()
                ->whereIn('status', ['open', 'partial'])
                ->with('product')
                ->first();

            $publisherId = $backorder?->product?->book?->publisher_id;

            if ($backorder !== null && $publisherId !== null) {
                $order = BackorderOrder::query()->create([
                    'backorder_id' => $backorder->getKey(),
                    'publisher_id' => $publisherId,
                    'quantity' => $backorder->outstandingQuantity(),
                    'number' => 'PO-DEMO-001',
                    'note' => 'Pesanan demo ke penerbit',
                    'status' => 'pending',
                    'ordered_at' => now()->subDays(2),
                    'created_by' => $admin?->getKey(),
                ]);

                BackorderOrderItem::query()->create([
                    'backorder_order_id' => $order->getKey(),
                    'backorder_id' => $backorder->getKey(),
                    'quantity' => $backorder->outstandingQuantity(),
                ]);
            }
        }

        // One shipment left unconfirmed so the proof-photo confirmation form can
        // be documented without creating data while taking screenshots.
        if (! \App\Models\Shipment::query()->where('status', 'draft')->exists()) {
            $order = Order::query()
                ->whereIn('status', ['ready', 'partial_ready', 'processing'])
                ->whereHas('items', fn ($query) => $query->where('reserved_quantity', '>', 0))
                ->with(['items' => fn ($query) => $query->where('reserved_quantity', '>', 0), 'school:id,name,address'])
                ->orderByDesc('id')
                ->first();

            if ($order !== null) {
                try {
                    app(ShipmentService::class)->create($order, [
                        'courier_id' => 1,
                        'ship_date' => now()->toDateString(),
                        'recipient_name' => 'Petugas Sekolah',
                        'address' => $order->school?->address,
                        'items' => $order->items->map(fn ($item): array => [
                            'order_item_id' => $item->getKey(),
                            'quantity' => min(2, $item->reserved_quantity),
                        ])->all(),
                    ], $admin);
                } catch (\Throwable) {
                    // Leave the demo data as-is when no line can be shipped.
                }
            }
        }

        // Product catalog
        if (ProductCatalog::query()->count() === 0) {
            $catalog = ProductCatalog::query()->create([
                'name' => 'Katalog Demo',
                'description' => 'Katalog contoh untuk preview',
                'type' => 'book',
                'zona_id' => null,
            ]);
            $products = Product::query()->limit(5)->pluck('id');
            foreach ($products as $index => $productId) {
                $catalog->products()->attach($productId, ['sort_order' => $index + 1]);
            }
        }
    }
}