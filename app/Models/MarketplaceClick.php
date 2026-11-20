<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceClick extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'sku',
        'marketplace',
        'session_id',
        'ip_address',
        'user_agent',
        'referrer',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeAmazon(Builder $query): Builder
    {
        return $query->where('marketplace', 'amazon');
    }

    public function scopeFlipkart(Builder $query): Builder
    {
        return $query->where('marketplace', 'flipkart');
    }

    public function scopeForMarketplace(Builder $query, string $marketplace): Builder
    {
        return $query->where('marketplace', strtolower($marketplace));
    }

    public function scopeDateRange(Builder $query, ?string $from, ?string $to): Builder
    {
        if ($from) {
            $query->where('created_at', '>=', $from);
        }
        if ($to) {
            $query->where('created_at', '<=', $to);
        }

        return $query;
    }
}

