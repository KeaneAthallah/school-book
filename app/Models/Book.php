<?php

namespace App\Models;

use Database\Factories\BookFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Specialty side of a product of type "book".
 *
 * @property int $id
 * @property int $product_id
 * @property string|null $isbn
 * @property int|null $publisher_id
 * @property int|null $category_id
 * @property string|null $grade
 * @property string|null $subject
 */
#[Fillable(['product_id', 'isbn', 'publisher_id', 'category_id', 'grade', 'subject'])]
class Book extends Model
{
    /** @use HasFactory<BookFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<Publisher, $this>
     */
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(Publisher::class);
    }

    /**
     * @return BelongsTo<BookCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(BookCategory::class, 'category_id');
    }

    protected static function booted(): void
    {
        static::deleted(function (Book $book): void {
            $book->product()->delete();
        });
    }
}
