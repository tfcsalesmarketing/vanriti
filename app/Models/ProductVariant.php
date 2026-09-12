<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'name',
        'sku',
        'mrp',
        'selling_price',
        'discount_percent',
        'stock',
        'low_stock_threshold',
        'weight',
        'dimensions',
        'image',
        'barcode',
        'hsn_code',
        'gst_rate',
        'status',
        'is_default',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'mrp' => 'decimal:2',
            'selling_price' => 'decimal:2',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function images(): MorphMany
    {
        return $this->morphMany(ProductImage::class, 'owner');
    }

    public function discountPercent(): float
    {
        if (! $this->mrp || $this->mrp <= 0) {
            return 0;
        }

        return round((($this->mrp - $this->selling_price) / $this->mrp) * 100, 1);
    }
}