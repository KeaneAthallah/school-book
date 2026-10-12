<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\BookCategory;
use App\Models\Product;
use App\Models\Publisher;
use Illuminate\Database\Eloquent\Factories\Factory;

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
            'product_id' => Product::factory(),
            'isbn' => null,
            'publisher_id' => Publisher::factory(),
            'category_id' => BookCategory::factory(),
            'grade' => (string) fake()->numberBetween(1, 12),
            'subject' => fake()->word(),
        ];
    }
}
