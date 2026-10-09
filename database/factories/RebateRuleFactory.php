<?php

namespace Database\Factories;

use App\Models\RebateRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RebateRule>
 */
class RebateRuleFactory extends Factory
{
    protected $model = RebateRule::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Aturan '.fake()->unique()->word(),
            'publisher_id' => null,
            'type' => 'percentage',
            'value' => '2.00',
            'min_invoice_amount' => null,
            'requires_full_payment' => true,
            'status' => 'active',
        ];
    }
}
