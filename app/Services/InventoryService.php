<?php

namespace App\Services;

use App\Enums\BackorderStatus;
use App\Enums\GoodsReceiptStatus;
use App\Enums\StockMovementType;
use App\Models\Backorder;
use App\Models\GoodsReceipt;
use App\Models\OrderItem;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly OrderStatusResolver $statusResolver,
    ) {}

    public function stock(int $productId, int $warehouseId): Stock
    {
        return Stock::query()->firstOrCreate(
            ['product_id' => $productId, 'warehouse_id' => $warehouseId],
            ['quantity' => 0, 'reserved_quantity' => 0],
        );
    }

    /**
     * Return the stock row locked for update. Must be called inside a transaction.
     */
    public function lockedStock(int $productId, int $warehouseId): Stock
    {
        $this->stock($productId, $warehouseId);

        return Stock::query()
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    public function availableQuantity(int $productId, int $warehouseId): int
    {
        return $this->stock($productId, $warehouseId)->availableQuantity();
    }

    /**
     * Confirm a goods receipt, increasing physical stock and allocating any
     * waiting back orders for the received titles.
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

            foreach ($locked->items as $item) {
                $stock = $this->lockedStock($item->product_id, $locked->warehouse_id);
                $stock->quantity += $item->quantity;
                $stock->save();

                $this->recordMovement(
                    productId: $item->product_id,
                    warehouseId: $locked->warehouse_id,
                    type: StockMovementType::Receipt,
                    quantity: $item->quantity,
                    balanceAfter: $stock->quantity,
                    reference: $locked,
                    note: 'Penerimaan '.$locked->number,
                    user: $user,
                );
            }

            $locked->forceFill([
                'status' => GoodsReceiptStatus::Confirmed,
                'confirmed_by' => $user->getKey(),
                'confirmed_at' => now(),
            ])->save();

            foreach ($locked->items as $item) {
                $this->allocateBackorders($item->product_id, $locked->warehouse_id, $user);
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
     * Apply a manual stock adjustment.
     */
    public function adjust(int $productId, int $warehouseId, int $delta, string $reason, User $user): Stock
    {
        if ($delta === 0) {
            throw ValidationException::withMessages(['quantity' => 'Jumlah penyesuaian tidak boleh nol.']);
        }

        return DB::transaction(function () use ($productId, $warehouseId, $delta, $reason, $user): Stock {
            $stock = $this->lockedStock($productId, $warehouseId);

            if ($stock->quantity + $delta < 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Penyesuaian akan membuat stok fisik menjadi negatif.',
                ]);
            }

            $stock->quantity += $delta;
            $stock->save();

            $this->recordMovement(
                productId: $productId,
                warehouseId: $warehouseId,
                type: $delta > 0 ? StockMovementType::AdjustmentIn : StockMovementType::AdjustmentOut,
                quantity: $delta,
                balanceAfter: $stock->quantity,
                reference: null,
                note: $reason,
                user: $user,
            );

            $this->audit->log(
                action: 'stock.adjusted',
                subject: $stock,
                description: 'Penyesuaian stok '.$delta.' ('.$reason.')',
                newValues: ['quantity' => $stock->quantity, 'delta' => $delta],
                user: $user,
            );

            return $stock;
        });
    }

    /**
     * Reserve stock for an order line. Must run inside a transaction.
     */
    public function reserve(OrderItem $item, int $quantity, User $user): void
    {
        if ($quantity <= 0) {
            return;
        }

        $warehouseId = (int) $item->warehouse_id;
        $stock = $this->lockedStock($item->product_id, $warehouseId);

        if ($stock->availableQuantity() < $quantity) {
            throw ValidationException::withMessages([
                'quantity' => "Stok tersedia untuk barang #{$item->product_id} tidak mencukupi.",
            ]);
        }

        $stock->reserved_quantity += $quantity;
        $stock->save();

        $this->recordMovement(
            productId: $item->product_id,
            warehouseId: $warehouseId,
            type: StockMovementType::Reservation,
            quantity: $quantity,
            balanceAfter: $stock->reserved_quantity,
            reference: $item,
            note: 'Reservasi pesanan '.$item->order->number,
            user: $user,
        );
    }

    /**
     * Reserve as much of the requested quantity as is available, returning the
     * quantity that was actually reserved. Must run inside a transaction.
     */
    public function reserveAvailable(OrderItem $item, int $requested, User $user): int
    {
        if ($requested <= 0) {
            return 0;
        }

        $warehouseId = (int) $item->warehouse_id;
        $stock = $this->lockedStock($item->product_id, $warehouseId);
        $quantity = min($requested, $stock->availableQuantity());

        if ($quantity <= 0) {
            return 0;
        }

        $stock->reserved_quantity += $quantity;
        $stock->save();

        $this->recordMovement(
            productId: $item->product_id,
            warehouseId: $warehouseId,
            type: StockMovementType::Reservation,
            quantity: $quantity,
            balanceAfter: $stock->reserved_quantity,
            reference: $item,
            note: 'Reservasi pesanan '.$item->order->number,
            user: $user,
        );

        return $quantity;
    }

    /**
     * Release previously reserved stock. Must run inside a transaction.
     */
    public function release(OrderItem $item, int $quantity, User $user, string $note = 'Pelepasan reservasi'): void
    {
        if ($quantity <= 0) {
            return;
        }

        $warehouseId = (int) $item->warehouse_id;
        $stock = $this->lockedStock($item->product_id, $warehouseId);
        $stock->reserved_quantity = max(0, $stock->reserved_quantity - $quantity);
        $stock->save();

        $this->recordMovement(
            productId: $item->product_id,
            warehouseId: $warehouseId,
            type: StockMovementType::ReservationRelease,
            quantity: -$quantity,
            balanceAfter: $stock->reserved_quantity,
            reference: $item,
            note: $note,
            user: $user,
        );
    }

    /**
     * Deduct physical and reserved stock for a confirmed shipment.
     * Must run inside a transaction.
     */
    public function ship(OrderItem $item, int $quantity, User $user, string $note): void
    {
        if ($quantity <= 0) {
            return;
        }

        $warehouseId = (int) $item->warehouse_id;
        $stock = $this->lockedStock($item->product_id, $warehouseId);

        if ($stock->quantity < $quantity || $stock->reserved_quantity < $quantity) {
            throw ValidationException::withMessages([
                'quantity' => "Stok fisik atau reservasi untuk barang #{$item->product_id} tidak mencukupi untuk dikirim.",
            ]);
        }

        $stock->quantity -= $quantity;
        $stock->reserved_quantity -= $quantity;
        $stock->save();

        $this->recordMovement(
            productId: $item->product_id,
            warehouseId: $warehouseId,
            type: StockMovementType::Shipment,
            quantity: -$quantity,
            balanceAfter: $stock->quantity,
            reference: $item,
            note: $note,
            user: $user,
        );
    }

    /**
     * Allocate available stock to waiting back orders using a FIFO policy
     * (oldest order date first, then oldest back order).
     */
    public function allocateBackorders(int $productId, int $warehouseId, User $user): int
    {
        $stock = $this->lockedStock($productId, $warehouseId);
        $available = $stock->availableQuantity();

        if ($available <= 0) {
            return 0;
        }

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
            $item->status = $this->statusResolver->resolveItem(
                $item->quantity,
                $item->reserved_quantity,
                $item->shipped_quantity,
            );
            $item->save();

            $backorder->fulfilled_quantity += $take;
            $backorder->status = $backorder->outstandingQuantity() <= 0
                ? BackorderStatus::Fulfilled
                : BackorderStatus::Partial;
            $backorder->save();

            $stock->reserved_quantity += $take;
            $available -= $take;
            $allocated += $take;

            $this->recordMovement(
                productId: $productId,
                warehouseId: $warehouseId,
                type: StockMovementType::Reservation,
                quantity: $take,
                balanceAfter: $stock->reserved_quantity,
                reference: $backorder,
                note: 'Alokasi back order',
                user: $user,
            );

            if ($item->order !== null) {
                $this->statusResolver->apply($item->order);
            }
        }

        $stock->save();

        return $allocated;
    }

    private function recordMovement(
        int $productId,
        int $warehouseId,
        StockMovementType $type,
        int $quantity,
        int $balanceAfter,
        ?Model $reference,
        string $note,
        User $user,
    ): void {
        StockMovement::query()->create([
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'type' => $type,
            'quantity' => $quantity,
            'balance_after' => $balanceAfter,
            'reference_type' => $reference === null ? null : $reference::class,
            'reference_id' => $reference?->getKey(),
            'note' => $note,
            'user_id' => $user->getKey(),
        ]);
    }
}
