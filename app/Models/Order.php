<?php

namespace App\Models;

use App\Services\GstService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\URL;

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
        'checkout_source',
        'whatsapp_confirmation_sent_at',
        'whatsapp_recovery_sent_at',
        'whatsapp_shipped_sent_at',
        'whatsapp_delivered_sent_at',
        'invoice_number',
        'invoice_url',
        'invoice_pdf_path',
        'invoice_generated_at',
        'order_status',
        'cancellation_reason',
        'tracking_url',
        'internal_notes',
        'cancelled_at',
    ];

    public function whatsAppCheckoutSession(): HasOne
    {
        return $this->hasOne(WhatsAppCheckoutSession::class);
    }

    protected function casts(): array
    {
        return [
            'is_billing_same' => 'boolean',
            'cancelled_at' => 'datetime',
            'whatsapp_confirmation_sent_at' => 'datetime',
            'whatsapp_recovery_sent_at' => 'datetime',
            'whatsapp_shipped_sent_at' => 'datetime',
            'whatsapp_delivered_sent_at' => 'datetime',
            'invoice_generated_at' => 'datetime',
        ];
    }

    public function invoiceNumber(): string
    {
        return $this->invoice_number ?: 'INV-' . $this->order_number;
    }

    public function invoiceUrl(): ?string
    {
        if (empty($this->invoice_url)) {
            return null;
        }

        $v = $this->invoice_generated_at?->timestamp ?? $this->updated_at?->timestamp ?? time();

        return str_contains($this->invoice_url, '?')
            ? $this->invoice_url . '&v=' . $v
            : $this->invoice_url . '?v=' . $v;
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

    public function getBillingAddressAttribute(): array
    {
        return [
            'full_name' => $this->billing_name,
            'mobile' => $this->billing_mobile,
            'address_line1' => $this->billing_address_line1,
            'address_line2' => $this->billing_address_line2,
            'landmark' => $this->billing_landmark,
            'city' => $this->billing_city,
            'state' => $this->billing_state,
            'pincode' => $this->billing_pincode,
            'country' => $this->billing_country,
        ];
    }

    public function getShippingAddressAttribute(): array
    {
        return [
            'full_name' => $this->shipping_name,
            'mobile' => $this->shipping_mobile,
            'address_line1' => $this->shipping_address_line1,
            'address_line2' => $this->shipping_address_line2,
            'landmark' => $this->shipping_landmark,
            'city' => $this->shipping_city,
            'state' => $this->shipping_state,
            'pincode' => $this->shipping_pincode,
            'country' => $this->shipping_country,
        ];
    }

    public function isCancellable(): bool
    {
        return in_array($this->order_status, ['pending', 'confirmed', 'processing']);
    }

    public function isReturnable(): bool
    {
        if ($this->order_status !== 'delivered') {
            return false;
        }

        // The advertised "N-day returns" window is enforced server-side, not
        // just displayed: an old delivery can no longer be returned even if the
        // storefront button is reachable.
        $windowDays = (int) setting('return_window_days', 7);

        if ($windowDays <= 0) {
            return true;
        }

        // The delivered status-history event is the anchor (there is no
        // delivered_at column on orders). Falling back to created_at, never
        // updated_at, keeps the window honest: a later payment or label update
        // must not silently extend it.
        $deliveredAt = $this->statusHistories->firstWhere('status', 'delivered')?->created_at
            ?? $this->created_at;

        return $deliveredAt !== null && $deliveredAt->gt(now()->subDays($windowDays));
    }

    /**
     * Guest-friendly signed tracking link (no login required). Safe to share via
     * email / WhatsApp; expires after the given number of days.
     */
    public function guestTrackingUrl(int $expiresInDays = 30): string
    {
        $mobile = $this->shipping_mobile ?: $this->billing_mobile;

        return URL::temporarySignedRoute('track.order', now()->addDays($expiresInDays), [
            'order' => $this->order_number,
            'mobile' => $mobile,
        ]);
    }

    /**
     * Branded on-site tracking page for the customer (signed URL).
     */
    public function trackingUrl(): string
    {
        return $this->guestTrackingUrl();
    }

    /**
     * External carrier / ShipMojo tracking URL fallback (e.g. for direct carrier tracking).
     */
    public function externalTrackingUrl(): ?string
    {
        if (! empty($this->tracking_url)) {
            return $this->tracking_url;
        }

        return $this->shipments->first()?->tracking_url;
    }

    /**
     * Guest-friendly signed review URL for the order or a specific line item.
     */
    public function guestReviewUrl(?OrderItem $item = null, int $expiresInDays = 30): string
    {
        $mobile = $this->shipping_mobile ?: ($this->billing_mobile ?: ($this->guest_mobile ?: $this->user?->phone));

        return URL::temporarySignedRoute('order.review', now()->addDays($expiresInDays), [
            'order' => $this->order_number,
            'mobile' => $mobile,
            'item' => $item?->id,
        ]);
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
