<?php

namespace App\Models;

use App\Services\GstService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'user_id',
        'coupon_id',
        'billing_name',
        'billing_mobile',
        'billing_address_line1',
        'billing_address_line2',
        'billing_landmark',
        'billing_city',
        'billing_state',
        'billing_pincode',
        'billing_country',
        'shipping_name',
        'shipping_mobile',
        'shipping_address_line1',
        'shipping_address_line2',
        'shipping_landmark',
        'shipping_city',
        'shipping_state',
        'shipping_pincode',
        'shipping_country',
        'is_billing_same',
        'subtotal',
        'taxable_amount',
        'discount_amount',
        'coupon_discount',
        'coupon_code',
        'coupon_type',
        'coupon_value',
        'shipping_charge',
        'tax_amount',
        'cgst_amount',
        'sgst_amount',
        'igst_amount',
        'grand_total',
        'amount_due',
        'amount_paid',
        'payment_method',
        'payment_status',
        'order_status',
        'cancellation_reason',
        'internal_notes',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'is_billing_same' => 'boolean',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderByDesc('created_at');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function returnRequests(): HasMany
    {
        return $this->hasMany(ReturnRequest::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeWithStatus($query, array $statuses)
    {
        return $query->whereIn('order_status', $statuses);
    }

    public function getBillingAddressLinesAttribute(): string
    {
        return implode(', ', array_filter([
            $this->billing_address_line1,
            $this->billing_address_line2,
            $this->billing_landmark,
            $this->billing_city,
            $this->billing_state,
            $this->billing_pincode,
            $this->billing_country,
        ]));
    }

    public function getShippingAddressLinesAttribute(): string
    {
        return implode(', ', array_filter([
            $this->shipping_address_line1,
            $this->shipping_address_line2,
            $this->shipping_landmark,
            $this->shipping_city,
            $this->shipping_state,
            $this->shipping_pincode,
            $this->shipping_country,
        ]));
    }

    public function isCancellable(): bool
    {
        return in_array($this->order_status, ['pending', 'confirmed', 'processing']);
    }

    public function isReturnable(): bool
    {
        return $this->order_status === 'delivered';
    }

    /**
     * Taxable (base) value for the invoice. Falls back to deriving from line items
     * for orders placed before the GST-inclusive fields were added.
     */
    public function getTaxableValueAttribute(): float
    {
        if ((float) ($this->attributes['taxable_amount'] ?? 0) > 0) {
            return (float) $this->attributes['taxable_amount'];
        }

        return round($this->items->sum(fn (OrderItem $item) => $item->taxable()), 2);
    }

    /**
     * Whether this order is an intra-state (CGST + SGST) shipment.
     */
    public function isIntraState(): bool
    {
        return app(GstService::class)->isIntraState((string) $this->shipping_state);
    }

    /**
     * Single GST rate when every line shares it (used for rate labels), else null.
     */
    public function uniformGstRate(): ?float
    {
        $rates = $this->items->pluck('gst_rate')->unique()->values();

        return $rates->count() === 1 ? (float) $rates->first() : null;
    }
}