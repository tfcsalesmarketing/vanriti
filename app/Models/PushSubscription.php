<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'endpoint',
        'endpoint_hash',
        'public_key',
        'auth_token',
        'content_encoding',
        'device_type',
        'browser',
        'status',
        'last_active_at',
    ];

    protected $casts = [
        'last_active_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeAnonymous($query)
    {
        return $query->whereNull('user_id');
    }

    public function scopeForAudience($query, string $audience)
    {
        return match ($audience) {
            'customers' => $query->whereNotNull('user_id'),
            'abandoned_cart' => $query->where(function ($q) {
                $q->whereHas('user.activeCart.items')
                    ->orWhere(function ($sub) {
                        $sub->whereNull('user_id')
                            ->whereNotNull('session_id')
                            ->whereExists(function ($cartQuery) {
                                $cartQuery->selectRaw(1)
                                    ->from('carts')
                                    ->whereColumn('carts.session_id', 'push_subscriptions.session_id')
                                    ->whereExists(function ($itemQuery) {
                                        $itemQuery->selectRaw(1)
                                            ->from('cart_items')
                                            ->whereColumn('cart_items.cart_id', 'carts.id');
                                    });
                            });
                    });
            }),
            default => $query,
        };
    }
}

