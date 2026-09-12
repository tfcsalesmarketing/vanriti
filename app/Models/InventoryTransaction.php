<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InventoryTransaction extends Model
{
    protected $fillable = [
        'stockable_type',
        'stockable_id',
        'reference_type',
        'reference_id',
        'type',
        'quantity_change',
        'stock_before',
        'stock_after',
        'reason',
        'admin_id',
    ];

    public function stockable(): MorphTo
    {
        return $this->morphTo('stockable');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo('reference');
    }
}