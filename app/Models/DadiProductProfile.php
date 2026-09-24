<?php

namespace App\Models;

use App\Dadi\Enums\ProductProfileStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Dadi-specific product intelligence that has been deliberately approved for
 * Dadi use by a human authority.
 *
 * This is NOT the catalogue. The referenced Product owns identity, SKU, price,
 * stock, variants, categories, images and commercial status; this model holds
 * only the controlled, human-reviewed intelligence content and the approval
 * lifecycle around it.
 */
class DadiProductProfile extends Model
{
    protected $fillable = [
        'product_id',
        'status',
        'sections',
        'concerns',
        'positioning',
        'approved_benefits',
        'approved_usage_context',
        'approved_precautions',
        'suitability_notes',
        'created_by',
        'updated_by',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProductProfileStatus::class,
            'sections' => 'array',
            'concerns' => 'array',
            'approved_benefits' => 'array',
            'approved_usage_context' => 'array',
            'approved_precautions' => 'array',
            'suitability_notes' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'updated_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'reviewed_by');
    }
}
