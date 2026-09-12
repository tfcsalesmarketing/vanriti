<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticsConversion extends Model
{
    protected $fillable = [
        'event_type',
        'order_number',
        'channel',
        'payload',
        'meta_state',
        'meta_sent_at',
        'meta_attempts',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'meta_sent_at' => 'datetime',
            'meta_attempts' => 'integer',
        ];
    }
}