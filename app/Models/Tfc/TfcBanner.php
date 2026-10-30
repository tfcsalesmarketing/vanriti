<?php

namespace App\Models\Tfc;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class TfcBanner extends Model
{
    protected $table = 'tfc_banners';

    protected $fillable = [
        'title',
        'subtitle',
        'image',
        'mobile_image',
        'link',
        'link_text',
        'position',
        'sort_order',
        'status',
    ];

    /**
     * Scope to active banners.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')->orderBy('sort_order', 'asc');
    }
}
