<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Review extends Model
{
    protected $fillable = [
        'product_id',
        'user_id',
        'order_item_id',
        'rating',
        'title',
        'comment',
        'status',
        'is_verified_purchase',
    ];

    protected function casts(): array
    {
        return [
            'is_verified_purchase' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Review images live on the storage disk, not in the database, so the
        // foreign key cascade would silently leave orphaned files behind.
        // Remove them on the way out. Note this only fires for deletes that
        // pass through Eloquent: a product or user deletion cascades at the
        // database level and still orphans files.
        static::deleting(function (Review $review) {
            foreach ($review->images()->get() as $image) {
                if (blank($image->path) || str_starts_with($image->path, 'http')) {
                    continue;
                }

                try {
                    Storage::disk($image->disk ?? 's3')->delete($image->path);
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ReviewImage::class)->orderBy('sort_order');
    }
}
