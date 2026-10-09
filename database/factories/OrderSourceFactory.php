<?php

namespace Database\Factories;

use App\Enums\OrderSourceType;
use App\Models\OrderSource;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<OrderSource>
 */
class OrderSourceFactory extends Factory
{
    protected $model = OrderSource::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'SRC-'.Str::upper(Str::random(5)),
            'name' => 'Sumber '.fake()->unique()->word(),
            'type' => OrderSourceType::Manual,
            'is_active' => true,
            'config' => null,
        ];
    }
}
