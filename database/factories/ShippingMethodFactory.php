<?php

namespace Database\Factories;

use App\Models\Courier;
use App\Models\ShippingMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShippingMethod>
 */
class ShippingMethodFactory extends Factory
{
    protected $model = ShippingMethod::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Metode '.fake()->unique()->word(),
            'courier_id' => Courier::factory(),
            'cost' => '25000.00',
            'status' => 'active',
        ];
    }
}
