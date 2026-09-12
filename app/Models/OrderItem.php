<?php

namespace App\Models;

use App\Services\GstService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id',
        'product_name',
        'variant_name',
        'sku',
        'image',
        'category_name',
        'quantity',
        'mrp',
        'unit_price',
        'discount',
        'gst_rate',
        'tax_amount',
        'total_price',
        'taxable_amount',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function hasReviewBy(User $user): bool
    {
        return Review::where('order_item_id', $this->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Taxable (base) value for this line. Falls back to reverse-calculating from
     * the stored inclusive total for orders placed before the field was added.
     */
    public function taxable(): float
    {
        if ((float) ($this->attributes['taxable_amount'] ?? 0) > 0) {
            return (float) $this->attributes['taxable_amount'];
        }

        $gst = app(GstService::class);

        return $gst->taxableAmount((float) $this->total_price, (float) $this->gst_rate);
    }

    /**
     * GST embedded in this line's inclusive total.
     */
    public function tax(): float
    {
        return round((float) $this->total_price - $this->taxable(), 2);
    }
}