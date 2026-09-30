<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    /**
     * The only placements a hero banner may occupy.
     */
    public const HERO_POSITIONS = [
        'home_top' => 'Home top - hero carousel',
    ];

    /**
     * Placements that render as a promotional strip directly after a homepage
     * product section. Each slot shows one banner: the lowest sort_order.
     */
    public const SECTION_POSITIONS = [
        'after_featured' => 'After Featured section',
        'after_bestsellers' => 'After Bestsellers section',
        'after_new_arrivals' => 'After New Arrivals section',
    ];

    /**
     * Banner types that are eligible for the section placements. The historical
     * `section` value behaves identically to `promotional`.
     */
    public const PROMOTIONAL_TYPES = ['promotional', 'section'];

    protected $fillable = [
        'title',
        'image',
        'mobile_image',
        'link',
        'type',
        'position',
        'sort_order',
        'status',
        'starts_at',
        'expires_at',
    ];

    /**
     * Placements selectable for a given banner type. Hero banners can only sit
     * in the carousel; every other type renders after a homepage section.
     */
    public static function positionsForType(string $type): array
    {
        return $type === 'hero' ? self::HERO_POSITIONS : self::SECTION_POSITIONS;
    }

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })
            ->orderBy('sort_order');
    }
}
