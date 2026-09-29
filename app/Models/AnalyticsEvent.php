<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * First-party behavioral event log for the analytics dashboard.
 *
 * This is a measurement log only: it never carries customer PII and is never
 * authoritative for orders, revenue or payments (those remain in orders /
 * order_items / payments). The session_id is an HMAC-SHA256 digest of the
 * underlying session identifier.
 *
 * visitor_id is a separate, longer-lived concept: a random anonymous UUID held
 * in a first-party cookie, used only to count unique/new/returning visitors. It
 * is deliberately not derived from session_id, and is never sent to Meta,
 * GA4 or Google Ads.
 */
class AnalyticsEvent extends Model
{
    public const PAGE_VIEW = 'page_view';

    public const VIEW_ITEM = 'view_item';

    public const ADD_TO_CART = 'add_to_cart';

    public const BEGIN_CHECKOUT = 'begin_checkout';

    public const ADD_PAYMENT_INFO = 'add_payment_info';

    protected $fillable = [
        'event_type',
        'session_id',
        'visitor_id',
        'user_id',
        'product_id',
        'sku',
        'quantity',
        'source',
        'medium',
        'campaign',
        'device_type',
        'landing_path',
        'payload',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'user_id' => 'integer',
            'product_id' => 'integer',
            'quantity' => 'integer',
            'occurred_at' => 'datetime',
        ];
    }

    public function scopeBetween(Builder $query, $from, $to): Builder
    {
        return $query->whereBetween('occurred_at', [$from, $to]);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('event_type', $type);
    }
}
