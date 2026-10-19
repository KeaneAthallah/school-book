<?php

namespace Database\Factories;

use App\Enums\OrderItemStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'quantity' => 1,
            'unit_price' => '100000.00',
            'discount_amount' => '0.00',
            'subtotal' => '100000.00',
            'reserved_quantity' => 0,
            'shipped_quantity' => 0,
            'backorder_quantity' => 0,
            'status' => OrderItemStatus::Pending,
        ];
    }
}
