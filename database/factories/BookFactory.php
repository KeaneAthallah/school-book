<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\BookCategory;
use App\Models\Publisher;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Book>
 */
class BookFactory extends Factory
{
    protected $model = Book::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'BK-'.Str::upper(Str::random(8)),
            'isbn' => null,
            'title' => 'Buku '.fake()->unique()->sentence(3),
            'publisher_id' => Publisher::factory(),
            'category_id' => BookCategory::factory(),
            'grade' => (string) fake()->numberBetween(1, 12),
            'subject' => fake()->word(),
            'price' => '100000.00',
            'cost_price' => '70000.00',
            'min_stock' => 0,
            'rack_location' => null,
            'status' => 'active',
        ];
    }
}
