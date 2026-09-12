<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'sku',
        'barcode',
        'short_description',
        'description',
        'highlights',
        'ingredients',
        'benefits',
        'how_to_use',
        'directions',
        'warnings',
        'precautions',
        'disclaimer',
        'net_quantity',
        'unit',
        'mrp',
        'selling_price',
        'discount_percent',
        'gst_rate',
        'stock',
        'low_stock_threshold',
        'hsn_code',
        'manufacturer',
        'manufacturer_address',
        'country_of_origin',
        'shelf_life',
        'expiry_info',
        'video_url',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'search_keywords',
        'status',
        'is_featured',
        'is_bestseller',
        'is_new_arrival',
        'total_sold',
        'review_count',
        'review_rating',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'is_bestseller' => 'boolean',
            'is_new_arrival' => 'boolean',
            'mrp' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'gst_rate' => 'decimal:2',
        ];
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'product_category');
    }

    public function primaryCategory()
    {
        return $this->categories()->wherePivot('is_primary', true)->first();
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order');
    }

    public function dadiProductProfile(): HasOne
    {
        return $this->hasOne(DadiProductProfile::class);
    }

    public function activeVariants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->where('status', 'active')->orderBy('sort_order');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function mainImage(): HasMany
    {
        return $this->hasMany(ProductImage::class)->where('type', 'main')->orderBy('sort_order')->limit(1);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('status', 'approved')->orderByDesc('created_at');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeBestsellers(Builder $query): Builder
    {
        return $query->where('is_bestseller', true);
    }

    public function scopeNewArrivals(Builder $query): Builder
    {
        return $query->where('is_new_arrival', true);
    }

    public function scopeInStock(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereHas('variants', fn ($v) => $v->where('stock', '>', 0))
                ->orWhere(function ($noVariant) {
                    $noVariant->doesntHave('variants')->where('stock', '>', 0);
                });
        });
    }

    public function getReviewCountAttribute(): int
    {
        $reviews = $this->relationLoaded('approvedReviews') ? $this->approvedReviews : $this->approvedReviews()->get();

        return $reviews->count();
    }

    public function getReviewRatingAttribute(): float
    {
        $reviews = $this->relationLoaded('approvedReviews') ? $this->approvedReviews : $this->approvedReviews()->get();

        return $reviews->count() ? round((float) $reviews->avg('rating'), 1) : 0.0;
    }

    public function discountPercent(): float
    {
        if (! $this->mrp || $this->mrp <= 0) {
            return 0;
        }

        return round((($this->mrp - $this->selling_price) / $this->mrp) * 100, 1);
    }

    public function getAvailableStock(): int
    {
        if ($this->variants()->exists()) {
            return (int) $this->variants()->sum('stock');
        }

        return (int) $this->stock;
    }

    public function isLowStock(): bool
    {
        $threshold = $this->low_stock_threshold ?: 0;

        return $this->getAvailableStock() > 0 && $this->getAvailableStock() <= $threshold;
    }

    public function isOutOfStock(): bool
    {
        return $this->getAvailableStock() <= 0;
    }

    public function getPrimaryImage()
    {
        return $this->images()->where('type', 'main')->first()
            ?? $this->images()->first();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
