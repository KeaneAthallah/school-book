<?php

namespace Database\Factories;

use App\Models\BookCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<BookCategory>
 */
class BookCategoryFactory extends Factory
{
    protected $model = BookCategory::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = 'Kategori '.fake()->unique()->word();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'description' => null,
        ];
    }
}
