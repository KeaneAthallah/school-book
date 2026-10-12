<?php

namespace Database\Factories;

use App\Models\Goods;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Goods>
 */
class GoodsFactory extends Factory
{
    protected $model = Goods::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(['type' => 'goods']),
            'unit' => fake()->randomElement(['pcs', 'unit', 'pak']),
            'description' => null,
        ];
    }
}
