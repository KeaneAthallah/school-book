<?php

namespace Database\Factories;

use App\Enums\BackorderStatus;
use App\Models\Backorder;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Backorder>
 */
class BackorderFactory extends Factory
{
    protected $model = Backorder::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_item_id' => OrderItem::factory(),
            'product_id' => Product::factory(),
            'quantity' => 1,
            'fulfilled_quantity' => 0,
            'status' => BackorderStatus::Open,
        ];
    }
}
