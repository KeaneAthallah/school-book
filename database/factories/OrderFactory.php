<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderSource;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => 'ORD-TEST-'.Str::upper(Str::random(8)),
            'order_date' => now()->toDateString(),
            'school_id' => School::factory(),
            'order_source_id' => OrderSource::factory(),
            'operator_id' => null,
            'status' => OrderStatus::Draft,
            'subtotal' => '0.00',
            'discount_amount' => '0.00',
            'tax_amount' => '0.00',
            'total_amount' => '0.00',
            'notes' => null,
            'external_reference' => null,
            'external_source' => null,
            'source_payload' => null,
            'confirmed_at' => null,
            'completed_at' => null,
        ];
    }
}
