<?php

namespace Database\Factories;

use App\Enums\ShipmentStatus;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Shipment>
 */
class ShipmentFactory extends Factory
{
    protected $model = Shipment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => 'SHP-TEST-'.Str::upper(Str::random(8)),
            'delivery_note_number' => null,
            'order_id' => Order::factory(),
            'warehouse_id' => Warehouse::factory(),
            'courier_id' => null,
            'shipping_method_id' => null,
            'ship_date' => null,
            'tracking_number' => null,
            'recipient_name' => null,
            'address' => null,
            'status' => ShipmentStatus::Draft,
            'notes' => null,
            'created_by' => null,
            'confirmed_at' => null,
        ];
    }
}
