<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GoodsReceiptItem>
 */
class GoodsReceiptItemFactory extends Factory
{
    protected $model = GoodsReceiptItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'goods_receipt_id' => GoodsReceipt::factory(),
            'book_id' => Book::factory(),
            'quantity' => 1,
            'unit_cost' => '70000.00',
        ];
    }
}
