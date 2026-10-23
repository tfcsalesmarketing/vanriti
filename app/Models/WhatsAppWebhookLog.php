<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppWebhookLog extends Model
{
    protected $table = 'whatsapp_webhook_logs';

    protected $fillable = [
        'message_id',
        'from_phone',
        'event_type',
        'payload',
        'processed',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed' => 'boolean',
        ];
    }

    public static function isDuplicate(string $messageId): bool
    {
        if (trim($messageId) === '') {
            return false;
        }

        return static::where('message_id', $messageId)->exists();
    }
}
