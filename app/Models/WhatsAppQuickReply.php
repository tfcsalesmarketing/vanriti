<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsAppQuickReply extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_quick_replies';

    protected $fillable = [
        'shortcut',
        'title',
        'category',
        'content',
        'usage_count',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'usage_count' => 'integer',
        ];
    }
}
