<?php

namespace App\Models;

use Database\Factories\BookCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 */
#[Fillable(['name', 'slug', 'description'])]
class BookCategory extends Model
{
    /** @use HasFactory<BookCategoryFactory> */
    use HasFactory;

    /**
     * @return HasMany<Book, $this>
     */
    public function books(): HasMany
    {
        return $this->hasMany(Book::class, 'category_id');
    }
}
