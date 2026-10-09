<?php

namespace App\Services;

use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Models\Order;

class OrderStatusResolver
{
    /**
     * Derive the order status from its line items.
     */
    public function resolve(Order $order): OrderStatus
    {
        $order->loadMissing('items');

        if ($order->items->isEmpty()) {
            return $order->status === OrderStatus::Draft ? OrderStatus::Draft : OrderStatus::Processing;
        }

        $requested = (int) $order->items->sum('quantity');
        $reserved = (int) $order->items->sum('reserved_quantity');
        $shipped = (int) $order->items->sum('shipped_quantity');

        if ($requested === 0 || $shipped >= $requested) {
            return OrderStatus::FullyShipped;
        }

        if ($shipped > 0) {
            return OrderStatus::PartialShipped;
        }

        if ($reserved >= $requested) {
            return OrderStatus::Ready;
        }

        if ($reserved > 0) {
            return OrderStatus::PartialReady;
        }

        return OrderStatus::Processing;
    }

    /**
     * Recompute and persist the order status based on current line items.
     */
    public function apply(Order $order): void
    {
        $status = $this->resolve($order);

        if ($order->status !== $status) {
            $order->forceFill(['status' => $status])->save();
        }
    }

    /**
     * Derive the status for a single order line item.
     */
    public function resolveItem(int $quantity, int $reserved, int $shipped): OrderItemStatus
    {
        if ($quantity > 0 && $shipped >= $quantity) {
            return OrderItemStatus::Fulfilled;
        }

        if ($quantity > 0 && $reserved >= $quantity) {
            return OrderItemStatus::Reserved;
        }

        if ($reserved > 0 || $shipped > 0) {
            return OrderItemStatus::Partial;
        }

        return OrderItemStatus::Pending;
    }
}
