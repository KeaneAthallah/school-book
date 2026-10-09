<?php

namespace Database\Factories;

use App\Models\Courier;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Courier>
 */
class CourierFactory extends Factory
{
    protected $model = Courier::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'CR-'.Str::upper(Str::random(5)),
            'name' => fake()->unique()->company(),
            'phone' => fake()->phoneNumber(),
            'status' => 'active',
        ];
    }
}
