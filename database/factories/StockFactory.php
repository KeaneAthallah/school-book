<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\Stock;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Stock>
 */
class StockFactory extends Factory
{
    protected $model = Stock::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'book_id' => Book::factory(),
            'warehouse_id' => Warehouse::factory(),
            'quantity' => 0,
            'reserved_quantity' => 0,
        ];
    }
}
