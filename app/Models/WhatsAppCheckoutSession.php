<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class WhatsAppCheckoutSession extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_checkout_sessions';

    public const STATUS_CART_RECEIVED = 'cart_received';
    public const STATUS_COLLECTING_NAME = 'collecting_name';
    public const STATUS_COLLECTING_PINCODE = 'collecting_pincode';
    public const STATUS_COLLECTING_ADDRESS = 'collecting_address';
    public const STATUS_AWAITING_ORDER_CONFIRMATION = 'awaiting_order_confirmation';
    public const STATUS_AWAITING_PAYMENT_METHOD = 'awaiting_payment_method';
    public const STATUS_PAYMENT_PENDING = 'payment_pending';
    public const STATUS_ORDER_CONFIRMED = 'order_confirmed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'phone',
        'wa_id',
        'customer_name',
        'session_token',
        'status',
        'cart_data',
        'address_data',
        'pincode',
        'subtotal',
        'shipping_charge',
        'discount_amount',
        'grand_total',
        'currency',
        'payment_method',
        'order_id',
        'razorpay_order_id',
        'last_message_id',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'cart_data' => 'array',
            'address_data' => 'array',
            'subtotal' => 'decimal:2',
            'shipping_charge' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'expires_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public static function createForCustomer(string $phone, ?string $waId = null, array $cartData = []): self
    {
        // Cancel/expire any active unconfirmed sessions for this phone to avoid mixing checkouts
        static::query()
            ->where('phone', $phone)
            ->whereIn('status', [
                self::STATUS_CART_RECEIVED,
                self::STATUS_COLLECTING_NAME,
                self::STATUS_COLLECTING_PINCODE,
                self::STATUS_COLLECTING_ADDRESS,
                self::STATUS_AWAITING_ORDER_CONFIRMATION,
                self::STATUS_AWAITING_PAYMENT_METHOD,
                self::STATUS_PAYMENT_PENDING,
            ])
            ->update(['status' => self::STATUS_EXPIRED]);

        return static::create([
            'phone' => $phone,
            'wa_id' => $waId,
            'session_token' => Str::random(48),
            'status' => self::STATUS_CART_RECEIVED,
            'cart_data' => $cartData,
            'currency' => 'INR',
            'expires_at' => now()->addHours(24),
        ]);
    }

    public static function findActiveForPhone(string $phone): ?self
    {
        return static::query()
            ->where('phone', $phone)
            ->whereNotIn('status', [self::STATUS_ORDER_CONFIRMED, self::STATUS_CANCELLED, self::STATUS_EXPIRED])
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->latest()
            ->first();
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED
            || ($this->expires_at && $this->expires_at->isPast());
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [self::STATUS_ORDER_CONFIRMED, self::STATUS_CANCELLED, self::STATUS_EXPIRED], true);
    }

    public function transitionTo(string $newStatus, array $extraAttributes = []): void
    {
        $this->update(array_merge([
            'status' => $newStatus,
            'expires_at' => now()->addHours(24),
        ], $extraAttributes));
    }
}
