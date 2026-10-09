<?php

namespace App\Services;

use App\Enums\PickingStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Picking;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FulfillmentService
{
    public function __construct(
        private readonly DocumentNumberService $numbers,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Generate a picking list from the reserved quantities of an order.
     */
    public function create(Order $order, User $user): Picking
    {
        return DB::transaction(function () use ($order, $user): Picking {
            $locked = Order::query()->lockForUpdate()->findOrFail((int) $order->getKey());
            $locked->load('items');

            $ready = $locked->items->filter(fn (OrderItem $item): bool => $item->reserved_quantity > 0);

            if ($ready->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'Belum ada item yang siap dipicking untuk pesanan ini.',
                ]);
            }

            $picking = Picking::query()->create([
                'number' => $this->numbers->generate('PCK', 'pickings'),
                'order_id' => $locked->getKey(),
                'warehouse_id' => (int) ($ready->first()->warehouse_id ?? Warehouse::default()->getKey()),
                'status' => PickingStatus::Draft,
                'created_by' => $user->getKey(),
            ]);

            foreach ($ready as $item) {
                $picking->items()->create([
                    'order_item_id' => $item->getKey(),
                    'book_id' => $item->book_id,
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
