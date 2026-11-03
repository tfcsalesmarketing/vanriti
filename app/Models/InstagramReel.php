<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstagramReel extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'caption',
        'instagram_url',
        'instagram_shortcode',
        'video_url',
        'thumbnail_url',
        'product_id',
        'views_count',
        'likes_count',
        'shares_count',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'likes_count' => 'integer',
        'shares_count' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderByDesc('id');
    }

    public static function extractShortcode(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        if (preg_match('/(?:reel|p|tv)\/([A-Za-z0-9_-]+)/', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }


    public function getPosterAttribute(): string
    {
        if ($this->thumbnail_url) {
            return image_url($this->thumbnail_url);
        }

        if ($this->product && $this->product->getPrimaryImage()) {
            return image_url($this->product->getPrimaryImage()->image_path);
        }

        return asset('images/placeholder.png');
    }

    public function getVideoSourceAttribute(): ?string
    {
        if (blank($this->video_url)) {
            return null;
        }

        return image_url($this->video_url);
    }
}

