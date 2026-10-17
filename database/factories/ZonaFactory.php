<?php

namespace Database\Factories;

use App\Models\Zona;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Zona> */
class ZonaFactory extends Factory
{
    protected $model = Zona::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->city(),
            'code' => strtoupper($this->faker->unique()->lexify('Z??')),
            'status' => 'active',
        ];
    }
}
