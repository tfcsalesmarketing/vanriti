<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per authenticated user carrying the current consent state.
 * This row (not the browser cookie) is the authoritative consent source for
 * authenticated resolution.
 */
class ConsentPreference extends Model
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
        'consented_at',
    ];

    protected function casts(): array
    {
        return [
            'necessary' => 'boolean',
            'analytics' => 'boolean',
            'advertising' => 'boolean',
            'marketing_communications' => 'boolean',
            'consented_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
