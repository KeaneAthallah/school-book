<?php

namespace App\Services;

use App\Enums\BackorderStatus;
use App\Enums\PickingStatus;
use App\Models\Backorder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Picking;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FulfillmentService
{
    public function __construct(
        private readonly DocumentNumberService $numbers,
        private readonly AuditLogger $audit,
        private readonly OrderStatusResolver $statusResolver,
    ) {}

    /**
     * Generate a picking list from the reserved quantities of an order.
     */
    public function create(Order $order, User $user): Picking
    {
        return DB::transaction(function () use ($order, $user): Picking {
            $locked = Order::query()->lockForUpdate()->findOrFail((int) $order->getKey());
            $locked->load('items');

            $existing = Picking::query()
                ->where('order_id', $locked->getKey())
                ->whereIn('status', [PickingStatus::Draft->value, PickingStatus::Picking->value, PickingStatus::Picked->value])
                ->first();

            if ($existing instanceof Picking) {
                return $existing->refresh();
            }

            $ready = $locked->items->filter(fn (OrderItem $item): bool => $item->reserved_quantity > 0);

            if ($ready->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'Belum ada item yang siap dipicking untuk pesanan ini.',
                ]);
            }

            $picking = Picking::query()->create([
                'number' => $this->numbers->generate('PCK', 'pickings'),
                'order_id' => $locked->getKey(),
                'status' => PickingStatus::Draft,
                'created_by' => $user->getKey(),
            ]);

            foreach ($ready as $item) {
                $picking->items()->create([
                    'order_item_id' => $item->getKey(),
                    'product_id' => $item->product_id,
                    'quantity' => $item->reserved_quantity,
                    'picked_quantity' => 0,
                ]);
            }

            $this->audit->log('picking.created', $picking, 'Pembuatan picking '.$picking->number, user: $user);

            return $picking->refresh();
        });
    }

    /**
     * Record the actually picked quantities.
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    public function recordPicked(Picking $picking, array $items, User $user): Picking
    {
        if (! in_array($picking->status, [PickingStatus::Draft, PickingStatus::Picking], true)) {
            throw ValidationException::withMessages([
                'status' => 'Picking ini tidak dapat diubah pada status saat ini.',
            ]);
        }

        return DB::transaction(function () use ($picking, $items, $user): Picking {
            $picking->load('items');

            foreach ($items as $entry) {
                $pickingItem = $picking->items->firstWhere('id', (int) ($entry['id'] ?? 0));

                if ($pickingItem === null) {
                    continue;
                }

                $quantity = max(0, min($pickingItem->quantity, (int) ($entry['picked_quantity'] ?? 0)));

                $pickingItem->forceFill(['picked_quantity' => $quantity])->save();

                // Stok yang hilang/rusak saat picking: kurangi reserved_quantity
                // dan kembalikan ke backorder agar dapat diprocure ulang.
                $missing = max(0, $pickingItem->quantity - $quantity);
                if ($missing > 0) {
                    $orderItem = OrderItem::query()->lockForUpdate()->find((int) $pickingItem->order_item_id);
                    if ($orderItem !== null) {
                        $orderItem->reserved_quantity = max(0, $orderItem->reserved_quantity - $missing);
                        $orderItem->backorder_quantity += $missing;
                        $orderItem->status = $this->statusResolver->resolveItem(
                            $orderItem->quantity,
                            $orderItem->reserved_quantity,
                            $orderItem->shipped_quantity,
                        );
                        $orderItem->save();

                        $backorder = Backorder::query()
                            ->where('order_item_id', $orderItem->getKey())
                            ->first();
                        if ($backorder !== null) {
                            $backorder->fulfilled_quantity = max(0, $backorder->fulfilled_quantity - $missing);
                            $backorder->status = $backorder->outstandingQuantity() <= 0
                                ? BackorderStatus::Fulfilled
                                : ($backorder->fulfilled_quantity > 0 ? BackorderStatus::Partial : BackorderStatus::Open);
                            $backorder->save();
                        }
                    }
                }
            }

            $picking->forceFill([
                'status' => PickingStatus::Picked,
                'picked_at' => now(),
            ])->save();

            $this->audit->log('picking.picked', $picking, 'Picking selesai '.$picking->number, user: $user);

            return $picking->refresh();
        });
    }

    public function pack(Picking $picking, User $user): Picking
    {
        if ($picking->status !== PickingStatus::Picked) {
            throw ValidationException::withMessages([
                'status' => 'Picking harus selesai dipicking sebelum packing.',
            ]);
        }

        $picking->forceFill([
            'status' => PickingStatus::Packed,
            'packed_at' => now(),
        ])->save();

        $this->audit->log('picking.packed', $picking, 'Packing selesai '.$picking->number, user: $user);

        return $picking;
    }
}
