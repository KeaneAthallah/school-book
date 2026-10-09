<?php

namespace App\Services;

use App\Enums\BackorderStatus;
use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Models\Backorder;
use App\Models\Book;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Setting;
use App\Models\Stock;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        private readonly DocumentNumberService $numbers,
        private readonly InventoryService $inventory,
        private readonly OrderStatusResolver $statusResolver,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $operator): Order
    {
        $this->guardDuplicateReference($data);

        return DB::transaction(function () use ($data, $operator): Order {
            $order = Order::query()->create([
                'number' => $this->numbers->generate('ORD', 'orders', $data['order_date'] ?? null),
                'order_date' => $data['order_date'],
                'school_id' => (int) $data['school_id'],
                'order_source_id' => (int) $data['order_source_id'],
                'operator_id' => $operator->getKey(),
                'status' => OrderStatus::Draft,
                'notes' => $data['notes'] ?? null,
                'external_reference' => $data['external_reference'] ?? null,
                'external_source' => $data['external_source'] ?? null,
                'discount_amount' => $data['discount_amount'] ?? '0',
            ]);

            $this->syncItems($order, $data['items']);
            $this->recalculate($order, $data['tax_amount'] ?? null);

            $this->audit->log('order.created', $order, 'Pembuatan pesanan '.$order->number, newValues: [
                'total_amount' => $order->total_amount,
            ], user: $operator);

            return $order->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Order $order, array $data, User $user): Order
    {
        if (! $order->isEditable()) {
            throw ValidationException::withMessages([
                'status' => 'Pesanan yang sudah diproses tidak dapat diubah.',
            ]);
        }

        $this->guardDuplicateReference($data, $order);

        return DB::transaction(function () use ($order, $data, $user): Order {
            $order->fill([
                'school_id' => (int) $data['school_id'],
                'order_source_id' => (int) $data['order_source_id'],
                'order_date' => $data['order_date'],
                'notes' => $data['notes'] ?? null,
                'external_reference' => $data['external_reference'] ?? null,
                'external_source' => $data['external_source'] ?? null,
                'discount_amount' => $data['discount_amount'] ?? '0',
            ])->save();

            $this->syncItems($order, $data['items']);
            $this->recalculate($order, $data['tax_amount'] ?? null);

            $this->audit->log('order.updated', $order, 'Perubahan pesanan '.$order->number, user: $user);

            return $order->refresh();
        });
    }

    /**
     * Confirm an order: reserve available stock and record shortages
     * as back orders. Never allows negative stock.
     */
    public function confirm(Order $order, User $user): Order
    {
        return DB::transaction(function () use ($order, $user): Order {
            $locked = Order::query()->lockForUpdate()->findOrFail((int) $order->getKey());

            if (! in_array($locked->status, [OrderStatus::Draft, OrderStatus::Received], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Pesanan ini tidak dapat dikonfirmasi pada status saat ini.',
                ]);
            }

            $locked->load('items');

            if ($locked->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'Pesanan tidak memiliki item.',
                ]);
            }

            foreach ($locked->items as $item) {
                $item->warehouse_id = $this->pickWarehouse((int) $item->book_id)->getKey();
                $item->save();

                $reserved = $this->inventory->reserveAvailable($item, $item->quantity, $user);
                $shortage = $item->quantity - $reserved;

                $item->reserved_quantity = $reserved;
                $item->backorder_quantity = $shortage;
                $item->status = $this->statusResolver->resolveItem($item->quantity, $reserved, 0);
                $item->save();

                if ($shortage > 0) {
                    Backorder::query()->create([
                        'order_item_id' => $item->getKey(),
                        'book_id' => $item->book_id,
                        'quantity' => $shortage,
                        'fulfilled_quantity' => 0,
                        'status' => BackorderStatus::Open,
                    ]);
                }
            }

            $locked->forceFill([
                'status' => OrderStatus::Processing,
                'confirmed_at' => now(),
            ])->save();

            $this->statusResolver->apply($locked);

            $this->audit->log('order.confirmed', $locked, 'Konfirmasi pesanan '.$locked->number, user: $user);

            return $locked->refresh();
        });
    }

    public function cancel(Order $order, User $user): Order
    {
        return DB::transaction(function () use ($order, $user): Order {
            $locked = Order::query()->lockForUpdate()->findOrFail((int) $order->getKey());

            if ($locked->status === OrderStatus::Cancelled) {
                return $locked;
            }

            if ($locked->items()->where('shipped_quantity', '>', 0)->exists()) {
                throw ValidationException::withMessages([
                    'status' => 'Pesanan yang sudah dikirim sebagian tidak dapat dibatalkan.',
                ]);
            }

            $locked->load('items');

            foreach ($locked->items as $item) {
                if ($item->reserved_quantity > 0) {
                    $this->inventory->release($item, $item->reserved_quantity, $user, 'Pembatalan pesanan '.$locked->number);
                    $item->reserved_quantity = 0;
                    $item->status = OrderItemStatus::Pending;
                    $item->save();
                }

                Backorder::query()
                    ->where('order_item_id', $item->getKey())
                    ->whereIn('status', [BackorderStatus::Open->value, BackorderStatus::Partial->value])
                    ->update(['status' => BackorderStatus::Cancelled->value]);
            }

            $locked->forceFill(['status' => OrderStatus::Cancelled])->save();

            $this->audit->log('order.cancelled', $locked, 'Pembatalan pesanan '.$locked->number, user: $user);

            return $locked->refresh();
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function syncItems(Order $order, array $items): void
    {
        if ($items === []) {
            throw ValidationException::withMessages([
                'items' => 'Pesanan harus memiliki minimal satu item.',
            ]);
        }

        $order->items()->delete();

        foreach ($items as $line) {
            $quantity = (int) $line['quantity'];
            $price = (string) ($line['unit_price'] ?? '0');
            $discount = (string) ($line['discount_amount'] ?? '0');
            $subtotal = Money::sub(Money::mul($price, (string) $quantity), $discount);

            $order->items()->create([
                'book_id' => (int) $line['book_id'],
                'quantity' => $quantity,
                'unit_price' => $price,
                'discount_amount' => $discount,
                'subtotal' => $subtotal,
                'status' => OrderItemStatus::Pending,
            ]);
        }
    }

    private function recalculate(Order $order, ?string $taxAmount): void
    {
        $order->load('items');

        $subtotal = $order->items->reduce(
            fn (string $carry, OrderItem $item): string => Money::add($carry, $item->subtotal),
            '0.00',
        );

        $discount = (string) $order->discount_amount;
        $tax = $taxAmount;

        if ($tax === null) {
            $rate = (string) Setting::value('finance.tax_rate', '0');
            $tax = Money::percentage($subtotal, $rate);
        }

        $total = Money::sub(Money::add($subtotal, $tax), $discount);

        $order->forceFill([
            'subtotal' => $subtotal,
            'tax_amount' => $tax,
            'total_amount' => $total,
        ])->save();
    }

    /**
     * Pick the warehouse holding the most available stock for a book,
     * falling back to the default warehouse.
     */
    private function pickWarehouse(int $bookId): Warehouse
    {
        $best = Stock::query()
            ->where('book_id', $bookId)
            ->orderByRaw('(quantity - reserved_quantity) DESC')
            ->first();

        if ($best !== null && $best->availableQuantity() > 0) {
            return Warehouse::query()->findOrFail((int) $best->warehouse_id);
        }

        return Warehouse::default();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function guardDuplicateReference(array $data, ?Order $ignore = null): void
    {
        $reference = $data['external_reference'] ?? null;

        if ($reference === null || $reference === '') {
            return;
        }

        $exists = Order::query()
            ->where('school_id', (int) $data['school_id'])
            ->where('external_reference', $reference)
            ->when($ignore !== null, fn ($query) => $query->whereKeyNot($ignore->getKey()))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'external_reference' => 'Referensi pesanan eksternal ini sudah pernah diimpor untuk sekolah tersebut.',
            ]);
        }
    }
}
