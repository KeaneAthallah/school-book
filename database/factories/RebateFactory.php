<?php

namespace Database\Factories;

use App\Enums\RebateStatus;
use App\Models\Rebate;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Rebate>
 */
class RebateFactory extends Factory
{
    protected $model = Rebate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => 'RBT-TEST-'.Str::upper(Str::random(8)),
            'school_id' => School::factory(),
            'invoice_id' => null,
            'publisher_id' => null,
            'rebate_rule_id' => null,
            'type' => 'percentage',
            'base_amount' => '0.00',
            'value' => '2.00',
            'amount' => '0.00',
            'status' => RebateStatus::Draft,
            'requested_by' => null,
            'approved_by' => null,
            'approved_at' => null,
            'rejection_reason' => null,
            'payment_reference' => null,
            'paid_at' => null,
            'notes' => null,
        ];
    }
}
