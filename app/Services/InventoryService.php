<?php

namespace App\Services;

use App\Enums\BackorderStatus;
use App\Enums\GoodsReceiptStatus;
use App\Enums\OrderItemStatus;
use App\Models\Backorder;
use App\Models\BackorderOrder;
use App\Models\GoodsReceipt;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly OrderStatusResolver $statusResolver,
    ) {}

    /**
     * Confirm a goods receipt: allocate received quantities to waiting
     * back orders (FIFO), without touching physical stock.
     */
    public function receive(GoodsReceipt $receipt, User $user): GoodsReceipt
    {
        return DB::transaction(function () use ($receipt, $user): GoodsReceipt {
            $locked = GoodsReceipt::query()->lockForUpdate()->findOrFail((int) $receipt->getKey());

            if ($locked->status !== GoodsReceiptStatus::Draft) {
                throw ValidationException::withMessages([
                    'status' => 'Penerimaan barang ini sudah dikonfirmasi sebelumnya.',
                ]);
            }

            $locked->load('items');

            if ($locked->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'Penerimaan barang tidak memiliki item.',
                ]);
            }

            $locked->forceFill([
                'status' => GoodsReceiptStatus::Confirmed,
                'confirmed_by' => $user->getKey(),
                'confirmed_at' => now(),
            ])->save();

            foreach ($locked->items as $item) {
                $this->allocateBackorders((int) $item->product_id, (int) $item->quantity, $user);
            }

            $this->audit->log(
                action: 'goods_receipt.confirmed',
                subject: $locked,
                description: 'Konfirmasi penerimaan barang '.$locked->number,
                user: $user,
            );

            return $locked;
        });
    }

    /**
     * Allocate received quantity to waiting back orders (FIFO by order date).
     */
    public function allocateBackorders(int $productId, int $quantity, User $user): int
    {
        if ($quantity <= 0) {
            return 0;
        }

        $available = $quantity;

        $backorders = Backorder::query()
            ->where('backorders.product_id', $productId)
            ->whereIn('backorders.status', [BackorderStatus::Open->value, BackorderStatus::Partial->value])
            ->join('order_items', 'order_items.id', '=', 'backorders.order_item_id')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->orderBy('orders.order_date')
            ->orderBy('backorders.id')
            ->select('backorders.*')
            ->lockForUpdate()
            ->get();

        $allocated = 0;

        foreach ($backorders as $backorder) {
            if ($available <= 0) {
                break;
            }

            $take = min($available, $backorder->outstandingQuantity());

            if ($take <= 0) {
                continue;
            }

            $item = OrderItem::query()->lockForUpdate()->findOrFail((int) $backorder->order_item_id);

            $item->reserved_quantity += $take;
            $item->backorder_quantity = max(0, $item->backorder_quantity - $take);
            $item->status = $this->resolveItemStatus($item);
            $item->save();

            $backorder->fulfilled_quantity += $take;
            $backorder->status = $backorder->outstandingQuantity() <= 0
                ? BackorderStatus::Fulfilled
                : BackorderStatus::Partial;
            $backorder->save();

            // Mark backorder_orders as received when all their backorders are fulfilled.
            $pendingOrders = BackorderOrder::query()
                ->where('status', 'pending')
                ->whereHas('items', fn ($q) => $q->where('backorder_id', $backorder->getKey()))
                ->lockForUpdate()
                ->get();

            foreach ($pendingOrders as $bo) {
                $bo->load('items.backorder');
                $allFulfilled = $bo->items->every(fn ($item) => $item->backorder?->status === BackorderStatus::Fulfilled);
                if ($allFulfilled) {
                    $bo->status = 'received';
                    $bo->save();
                }
            }

            $available -= $take;
            $allocated += $take;

            if ($item->order !== null) {
                $this->statusResolver->apply($item->order);
            }
        }

        return $allocated;
    }

    private function resolveItemStatus(OrderItem $item): OrderItemStatus
    {
        return $this->statusResolver->resolveItem(
            $item->quantity,
            $item->reserved_quantity,
            $item->shipped_quantity,
        );
    }
}
