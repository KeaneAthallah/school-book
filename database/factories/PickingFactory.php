<?php

namespace Database\Factories;

use App\Enums\PickingStatus;
use App\Models\Order;
use App\Models\Picking;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Picking>
 */
class PickingFactory extends Factory
{
    protected $model = Picking::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => 'PCK-TEST-'.Str::upper(Str::random(8)),
            'order_id' => Order::factory(),
            'status' => PickingStatus::Draft,
            'notes' => null,
            'created_by' => null,
            'picked_at' => null,
            'packed_at' => null,
        ];
    }
}
