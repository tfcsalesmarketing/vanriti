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

    protected $casts = [
        'metadata' => 'array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    protected $appends = [
        'display_body',
    ];

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
        if ($disk === 's3' && config('filesystems.disks.s3.key')) {
            return Storage::disk('s3')->url($this->media_url);
        }

        return Storage::disk($disk)->url($this->media_url);
    }

    /**
     * Resolve human-friendly, clean body text for chat bubbles and inbox timeline.
     * Prevents raw tags like [Message: button] or [Message: unsupported] and
     * formats actions like "✅ Confirmed Order", "❌ Cancelled Order", etc.
     */
    public function getDisplayBodyAttribute(): string
    {
        $raw = (string) ($this->body ?? '');
        $metadata = is_array($this->metadata) ? $this->metadata : [];
        $type = (string) ($this->message_type ?? '');

        // 1. Inbound button or interactive reply
        if (! $this->isOutbound() && (in_array($type, ['button', 'interactive'], true) || str_contains($raw, '[Message: button]'))) {
            $payload = (string) ($metadata['raw_payload']
                ?? ($metadata['payload']
                ?? ($metadata['button']['payload']
                ?? ($metadata['button_reply']['id']
                ?? ($metadata['id'] ?? '')))));

            $text = (string) ($metadata['raw_text']
                ?? ($metadata['raw_title']
                ?? ($metadata['text']
                ?? ($metadata['button']['text']
                ?? ($metadata['button_reply']['title']
                ?? ($metadata['title'] ?? ''))))));

            // If raw body itself is already formatted with an action emoji, return it directly
            if ($raw !== '' && ! in_array($raw, ['[Message: button]', '[Button reply]', '[Interactive selection]', '🔘 Button Reply'], true)) {
                if (preg_match('/^[\x{2705}\x{274C}\x{1F69A}\x{1F4CD}\x{1F514}\x{1F518}]/u', $raw)) {
                    return $raw;
                }
                // If payload is present, resolve it
                if ($payload !== '') {
                    return \App\Services\WhatsAppChatService::parseButtonAction($payload, $raw);
                }
                // If raw has text, parse it
                return \App\Services\WhatsAppChatService::parseButtonAction('', $raw);
            }

            // Raw was placeholder, parse from metadata
            return \App\Services\WhatsAppChatService::parseButtonAction($payload, $text);
        }

        // 2. Unsupported messages
        if ($type === 'unsupported' || str_contains($raw, '[Message: unsupported]') || str_contains($raw, '[Unsupported message')) {
            if ($raw !== '' && ! in_array($raw, ['[Message: unsupported]', '⚠️ [Unsupported message format (e.g. Poll, Live Location, or Payment)]'], true)) {
                return $raw;
            }
            return 'ℹ️ [Unsupported message format (e.g. Poll, Live Location, or Flow Reply)]';
        }

        // 3. Generic [Message: xyz] placeholders
        if (preg_match('/^\[Message:\s*(\w+)\]$/i', $raw, $matches)) {
            $typeName = ucfirst($matches[1]);
            return "ℹ️ [Unsupported message format: {$typeName}]";
        }

        // 4. Outbound template message with raw "📋 Template: ..."
        if ($this->isOutbound() && str_starts_with($raw, '📋 Template:')) {
            $event = $metadata['event'] ?? null;
            $orderNo = $metadata['order_number'] ?? null;
            $courier = $metadata['courier'] ?? null;
            $awb = $metadata['awb'] ?? null;
            $invoiceUrl = $metadata['invoice_url'] ?? null;

            if ($event === 'shipped') {
                return "📦 Order Dispatched" . ($orderNo ? " (#{$orderNo})" : '') . "\n"
                    . ($courier ? "Dispatched via {$courier}. " : '')
                    . ($awb ? "AWB / Tracking: {$awb}" : '');
            }

            if ($event === 'out_for_delivery') {
                return "🚚 Out for Delivery" . ($orderNo ? " (#{$orderNo})" : '') . "\n"
                    . ($courier ? "Out for delivery with {$courier}." : '');
            }

            if ($event === 'delivered') {
                return "🎉 Order Delivered" . ($orderNo ? " (#{$orderNo})" : '') . "\n"
                    . ($courier ? "Delivered via {$courier}." : '')
                    . ($invoiceUrl ? "\nTax Invoice: {$invoiceUrl}" : '');
            }

            if (! empty($metadata['is_order_confirmation'])) {
                return "🛍️ Order Confirmation" . ($orderNo ? " (#{$orderNo})" : '') . "\nOrder confirmed.";
            }
        }

        return $raw;
    }
}
