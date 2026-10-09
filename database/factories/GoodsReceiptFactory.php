<?php

namespace Database\Factories;

use App\Enums\GoodsReceiptStatus;
use App\Models\GoodsReceipt;
use App\Models\Publisher;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<GoodsReceipt>
 */
class GoodsReceiptFactory extends Factory
{
    protected $model = GoodsReceipt::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => 'GR-TEST-'.Str::upper(Str::random(8)),
            'publisher_id' => Publisher::factory(),
            'warehouse_id' => Warehouse::factory(),
            'receipt_date' => now()->toDateString(),
            'status' => GoodsReceiptStatus::Draft,
            'notes' => null,
            'created_by' => null,
            'confirmed_by' => null,
            'confirmed_at' => null,
        ];
    }
}
