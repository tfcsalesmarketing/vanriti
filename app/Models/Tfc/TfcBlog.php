<?php

namespace App\Models\Tfc;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class TfcBlog extends Model
{
    protected $table = 'tfc_blogs';

    protected $fillable = [
        'title',
        'slug',
        'featured_image',
        'excerpt',
        'content',
        'meta_title',
        'meta_description',
        'status',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    /**
     * Scope to published blogs.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }
}
