<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only history of consent decisions. Written only by the ConsentService;
 * never updated or deleted during normal consent changes.
 */
class ConsentChoice extends Model
{
    protected $fillable = [
        'user_id',
        'necessary',
        'analytics',
        'advertising',
        'marketing_communications',
        'consent_version',
        'policy_version',
        'source',
    ];

    /**
     * History is append-only: normal consent changes must never update or
     * delete an existing record.
     */
    protected $guarded = ['id', 'user_id'];

    protected function casts(): array
    {
        return [
            'necessary' => 'boolean',
            'analytics' => 'boolean',
            'advertising' => 'boolean',
            'marketing_communications' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
