<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => 'PAY-TEST-'.Str::upper(Str::random(8)),
            'school_id' => School::factory(),
            'payment_date' => now()->toDateString(),
            'amount' => '0.00',
            'payment_method_id' => null,
            'reference' => null,
            'notes' => null,
            'status' => PaymentStatus::Draft,
            'verified_by' => null,
            'verified_at' => null,
            'created_by' => null,
        ];
    }
}
