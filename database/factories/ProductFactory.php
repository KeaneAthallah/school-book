<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => 'book',
            'code' => 'BK-'.Str::upper(Str::random(8)),
            'title' => fake()->unique()->sentence(3),
            'price' => (string) (fake()->randomNumber(5) / 1000),
            'cost_price' => '70000.00',
            'stock' => 0,
            'min_stock' => 0,
            'rack_location' => null,
            'status' => 'active',
        ];
    }
}
