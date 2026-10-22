<?php

namespace App\Services;

use App\Enums\BackorderStatus;
use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Models\Backorder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\School;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        private readonly DocumentNumberService $numbers,
        private readonly OrderStatusResolver $statusResolver,
        private readonly AuditLogger $audit,
        private readonly TaxCalculator $taxes,
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
                'discount_percent' => $data['discount_percent'] ?? '0',
                'discount_amount' => $data['discount_amount'] ?? '0',
            ]);

            $this->syncItems($order, $data['items'], (int) $data['school_id']);
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
                'discount_percent' => $data['discount_percent'] ?? '0',
                'discount_amount' => $data['discount_amount'] ?? '0',
            ])->save();

            $this->syncItems($order, $data['items'], (int) $data['school_id']);
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
                $item->reserved_quantity = 0;
                $item->backorder_quantity = $item->quantity;
                $item->status = $this->statusResolver->resolveItem($item->quantity, 0, 0);
                $item->save();

                Backorder::query()->create([
                    'order_item_id' => $item->getKey(),
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'fulfilled_quantity' => 0,
                    'status' => BackorderStatus::Open,
                ]);
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

    public function revertToDraft(Order $order, User $user): Order
    {
        return DB::transaction(function () use ($order, $user): Order {
            $locked = Order::query()->lockForUpdate()->findOrFail((int) $order->getKey());

            if ($locked->status !== OrderStatus::Processing) {
                throw ValidationException::withMessages([
                    'status' => 'Hanya pesanan berstatus Diproses yang dapat dikembalikan ke Draft.',
                ]);
            }

            $locked->load('items');

            foreach ($locked->items as $item) {
                // Kembalikan reserved ke 0, restore backorder_quantity.
                $item->reserved_quantity = 0;
                $item->backorder_quantity = $item->quantity;
                $item->status = OrderItemStatus::Pending;
                $item->save();

                Backorder::query()
                    ->where('order_item_id', $item->getKey())
                    ->delete();
            }

            $locked->forceFill([
                'status' => OrderStatus::Draft,
                'confirmed_at' => null,
            ])->save();

            $this->audit->log('order.reverted_to_draft', $locked, 'Pengembalian pesanan '.$locked->number.' ke Draft', user: $user);

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
                $item->reserved_quantity = 0;
                $item->status = OrderItemStatus::Pending;
                $item->save();

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
    private function syncItems(Order $order, array $items, int $schoolId = 0): void
    {
        if ($items === []) {
            throw ValidationException::withMessages([
                'items' => 'Pesanan harus memiliki minimal satu item.',
            ]);
        }

        $order->items()->delete();

        $zonaId = $schoolId > 0 ? School::query()->whereKey($schoolId)->value('zona_id') : null;

        foreach ($items as $line) {
            $quantity = (int) $line['quantity'];
            $productId = (int) $line['product_id'];

            $price = $line['unit_price'] ?? null;
            if ($price === null || $price === '') {
                $product = Product::query()->find($productId);
                if ($zonaId !== null && $product !== null) {
                    $zonaPrice = $product->priceForZona((int) $zonaId);
                    if ($zonaPrice !== null) {
                        $price = $zonaPrice;
                    } else {
                        $price = $product->price;
                    }
                } else {
                    $price = $product !== null ? $product->price : '0';
                }
            }
            $price = (string) $price;

            $lineAmount = Money::mul($price, (string) $quantity);
            $discountPercent = (string) ($line['discount_percent'] ?? '0');
            $discount = '0';

            if (Money::compare($discountPercent, '0') > 0) {
                $discount = Money::percentage($lineAmount, $discountPercent);
            } else {
                $discount = (string) ($line['discount_amount'] ?? '0');
            }

            $subtotal = Money::sub($lineAmount, $discount);

            $order->items()->create([
                'product_id' => $productId,
                'quantity' => $quantity,
                'unit_price' => $price,
                'discount_percent' => $discountPercent,
                'discount_amount' => $discount,
                'subtotal' => $subtotal,
                'status' => OrderItemStatus::Pending,
                'availability_status' => $line['availability_status'] ?? 'available',
            ]);
        }
    }

    private function recalculate(Order $order, ?string $taxAmount): void
    {
        $order->load('items.product:products.id,type');

        $subtotal = $order->items->reduce(
            fn (string $carry, OrderItem $item): string => Money::add($carry, $item->subtotal),
            '0.00',
        );

        $discountPercent = (string) $order->discount_percent;

        // Diskon pesanan kini persentase dari subtotal (bukan lagi nilai tetap).
        if (Money::compare($discountPercent, '0') > 0) {
            $discount = Money::percentage($subtotal, $discountPercent);
        } else {
            $discount = (string) $order->discount_amount;
        }

        $tax = $taxAmount;
        $dpp = '0.00';
        $ppn = '0.00';
        $pph = '0.00';

        if ($tax === null) {
            $calc = $this->taxes->calculate($order->items->map(fn (OrderItem $item): array => [
                'type' => $item->product->type ?? 'book',
                'unit_price' => $item->unit_price,
                'quantity' => $item->quantity,
            ]));

            $tax = Money::add($calc['ppn'], $calc['pph']);
            [$dpp, $ppn, $pph] = [$calc['dpp'], $calc['ppn'], $calc['pph']];
        }

        $total = Money::add(Money::sub(Money::add($subtotal, $ppn), $discount), $pph);

        $order->forceFill([
            'subtotal' => $subtotal,
            'discount_amount' => $discount,
            'tax_amount' => $tax,
            'dpp_amount' => $dpp,
            'ppn_amount' => $ppn,
            'pph_amount' => $pph,
            'total_amount' => $total,
        ])->save();
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
