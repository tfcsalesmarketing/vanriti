<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsAppCampaign extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_campaigns';

    protected $fillable = [
        'name',
        'template_name',
        'template_lang',
        'target_segment',
        'parameters',
        'header_image_url',
        'status',
        'total_recipients',
        'sent_count',
        'delivered_count',
        'read_count',
        'failed_count',
        'scheduled_at',
        'completed_at',
        'created_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'parameters' => 'array',
            'total_recipients' => 'integer',
            'sent_count' => 'integer',
            'delivered_count' => 'integer',
            'read_count' => 'integer',
            'failed_count' => 'integer',
            'scheduled_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(WhatsAppCampaignRecipient::class, 'whatsapp_campaign_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function deliveryRate(): float
    {
        if ($this->sent_count === 0) {
            return 0.0;
        }

        return round(($this->delivered_count / $this->sent_count) * 100, 1);
    }

    public function readRate(): float
    {
        if ($this->delivered_count === 0) {
            return 0.0;
        }

        return round(($this->read_count / $this->delivered_count) * 100, 1);
    }
}
