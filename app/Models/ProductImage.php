<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ProductImage extends Model
{
    protected $fillable = [
        'product_id',
        'owner_type',
        'owner_id',
        'image_path',
        'alt_text',
        'type',
        'sort_order',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function owner(): MorphTo
    {
        return $this->morphTo('owner');
    }
}