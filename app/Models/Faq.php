<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Faq extends Model
{
    protected $fillable = [
        'question',
        'answer',
        'category',
        'sort_order',
        'status',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 'active')->orderBy('sort_order');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'faq_category');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'faq_product');
    }
}
