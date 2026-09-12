<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Inventory extends Model
{
    protected $fillable = [
        'stockable_type',
        'stockable_id',
        'stock_on_hand',
        'reserved',
        'low_stock_threshold',
    ];

    public function stockable(): MorphTo
    {
        return $this->morphTo('stockable');
    }

    public function availableStock(): int
    {
        return (int) $this->stock_on_hand - (int) $this->reserved;
    }
}