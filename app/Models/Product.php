<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Previous slug stored during an update so a 301 redirect can be written after save.
     */
    public ?string $slugRedirectFrom = null;

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (Product $product): void {
            if (is_string($product->sku)) {
                $product->sku = strtoupper(trim($product->sku));
                if ($product->sku === '') {
                    $product->sku = null;
                }
            }
        });

        static::creating(function (Product $product): void {
            $product->slug = static::uniqueSlugFromName((string) $product->name);
        });

        static::updating(function (Product $product): void {
            if ($product->isDirty('name')) {
                $product->slug = static::uniqueSlugFromName((string) $product->name, $product->id);
            }

            $from = $product->getOriginal('slug');
            if (is_string($from) && $from !== '' && $product->isDirty('slug') && $from !== $product->slug) {
                $product->slugRedirectFrom = $from;
            }
        });

        static::updated(function (Product $product): void {
            if (! is_string($product->slugRedirectFrom) || $product->slugRedirectFrom === '') {
                return;
            }

            ProductSlugRedirect::query()->updateOrCreate(
                ['slug' => $product->slugRedirectFrom],
                ['product_id' => $product->id]
            );
            $product->slugRedirectFrom = null;
        });
    }

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

    public function faqs(): BelongsToMany
    {
        return $this->belongsToMany(Faq::class, 'faq_product');
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

    public function slugRedirects(): HasMany
    {
        return $this->hasMany(ProductSlugRedirect::class);
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

    public function resolveRouteBinding($value, $field = null)
    {
        $field ??= $this->getRouteKeyName();

        $product = $this->where($field, $value)->first();
        if ($product) {
            return $product;
        }

        if ($field === 'slug') {
            $productId = ProductSlugRedirect::query()->where('slug', $value)->value('product_id');
            if ($productId) {
                return $this->whereKey($productId)->first();
            }
        }

        return null;
    }

    public static function uniqueSlugFromName(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = 'product';
        }

        $base = substr($base, 0, 240);
        $slug = $base;
        $suffix = 2;

        while (static::slugIsTaken($slug, $ignoreId)) {
            $slug = substr($base, 0, 230).'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    public static function rewriteSlugFromName(self $product): bool
    {
        $old = (string) $product->slug;
        $new = static::uniqueSlugFromName((string) $product->name, $product->id);

        if ($old === $new) {
            return false;
        }

        $product->slug = $new;
        $product->saveQuietly();

        if ($old !== '') {
            ProductSlugRedirect::query()->updateOrCreate(
                ['slug' => $old],
                ['product_id' => $product->id]
            );
        }

        return true;
    }

    public static function rewriteAllSlugsFromNames(): int
    {
        $updated = 0;

        foreach ([1, 2] as $_) {
            static::query()->orderBy('id')->each(function (self $product) use (&$updated): void {
                if (static::rewriteSlugFromName($product)) {
                    $updated++;
                }
            });
        }

        return $updated;
    }

    protected static function slugIsTaken(string $slug, ?int $ignoreId = null): bool
    {
        $productTaken = static::withTrashed()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();

        if ($productTaken) {
            return true;
        }

        return ProductSlugRedirect::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('product_id', '!=', $ignoreId))
            ->exists();
    }
}
