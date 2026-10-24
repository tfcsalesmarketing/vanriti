<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class WhatsAppMessage extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_messages';

    protected $fillable = [
        'whatsapp_conversation_id',
        'direction',
        'message_type',
        'body',
        'media_id',
        'media_url',
        'media_mime_type',
        'media_filename',
        'meta_message_id',
        'status',
        'error_message',
        'metadata',
        'sender_admin_id',
        'sent_at',
        'delivered_at',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'read_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(WhatsAppConversation::class, 'whatsapp_conversation_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'sender_admin_id');
    }

    public function isInbound(): bool
    {
        return $this->direction === 'inbound';
    }

    public function isOutbound(): bool
    {
        return $this->direction === 'outbound';
    }

    public function isInternalNote(): bool
    {
        return $this->direction === 'internal' || $this->message_type === 'note';
    }

    public function isMedia(): bool
    {
        return in_array($this->message_type, ['image', 'document', 'audio', 'video'], true);
    }

    public function getResolvedMediaUrl(): ?string
    {
        if (empty($this->media_url)) {
            if (! empty($this->media_id)) {
                return route('admin.whatsapp.media.download', ['message' => $this->id]);
            }
            return null;
        }

        if (str_starts_with($this->media_url, 'http://') || str_starts_with($this->media_url, 'https://')) {
            return $this->media_url;
        }

        $disk = config('filesystems.whatsapp_disk', 's3');
        if (config('filesystems.disks.s3.key') || $disk === 's3') {
            return Storage::disk('s3')->url($this->media_url);
        }

        return Storage::disk($disk)->url($this->media_url);
    }
}
