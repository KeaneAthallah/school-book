<?php

namespace Database\Factories;

use App\Models\CashLoan;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CashLoan> */
class CashLoanFactory extends Factory
{
    protected $model = CashLoan::class;

    public function definition(): array
    {
        return [
            'number' => 'PIN-'.fake()->unique()->numerify('######'),
            'person_name' => fake()->name(),
            'school_name' => fake()->company(),
            'loan_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'amount' => '100000.00',
            'notes' => null,
            'status' => 'active',
        ];
    }
}
