<?php

namespace Database\Factories;

use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<School>
 */
class SchoolFactory extends Factory
{
    protected $model = School::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'npsn' => (string) fake()->unique()->numberBetween(10000000, 99999999),
            'name' => 'SMP '.Str::ucfirst(fake()->unique()->city()),
            'address' => fake()->address(),
            'district' => fake()->citySuffix(),
            'regency' => fake()->city(),
            'province' => 'Sulawesi Selatan',
            'contact_person' => fake()->name(),
            'phone' => fake()->phoneNumber(),
            'principal_name' => fake()->name(),
            'status' => 'active',
            'sales_user_id' => null,
        ];
    }
}
