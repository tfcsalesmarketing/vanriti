<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'body',
        'url',
        'icon_url',
        'image_url',
        'target_audience',
        'status',
        'total_targeted',
        'total_sent',
        'total_failed',
        'total_clicked',
        'sent_by',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'total_targeted' => 'integer',
        'total_sent' => 'integer',
        'total_failed' => 'integer',
        'total_clicked' => 'integer',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'sent_by');
    }

    public function getClickThroughRateAttribute(): float
    {
        if ($this->total_sent <= 0) {
            return 0.0;
        }

        return round(($this->total_clicked / $this->total_sent) * 100, 1);
    }
}

