<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'discount_type',
        'discount_value',
        'min_cart_value',
        'max_discount',
        'starts_at',
        'expires_at',
        'usage_limit',
        'per_customer_limit',
        'first_order_only',
        'is_active',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'first_order_only' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'coupon_products');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'coupon_categories');
    }

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', Carbon::now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', Carbon::now());
            });
    }

    public function isUsable(Carbon $at = null): bool
    {
        $at = $at ?? Carbon::now();

        if (! $this->is_active) {
            return false;
        }
        if ($this->starts_at && $this->starts_at->gt($at)) {
            return false;
        }
        if ($this->expires_at && $this->expires_at->lt($at)) {
            return false;
        }
        if ($this->usage_limit && $this->usages()->count() >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    public function usesByUser(int $userId): int
    {
        return (int) $this->usages()->where('user_id', $userId)->count();
    }
}