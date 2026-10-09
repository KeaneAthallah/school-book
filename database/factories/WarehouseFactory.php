<?php

namespace Database\Factories;

use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Warehouse>
 */
class WarehouseFactory extends Factory
{
    protected $model = Warehouse::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'GDG-'.Str::upper(Str::random(5)),
            'name' => 'Gudang '.fake()->unique()->city(),
            'address' => fake()->address(),
            'status' => 'active',
        ];
    }
}
