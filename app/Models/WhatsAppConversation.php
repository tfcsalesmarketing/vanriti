<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WhatsAppConversation extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_conversations';

    protected $fillable = [
        'phone',
        'wa_id',
        'contact_name',
        'user_id',
        'channel',
        'tfc_inquiry_id',
        'tfc_grievance_id',
        'unread_count',
        'last_message_at',
        'last_message_preview',
        'window_expires_at',
        'status',
        'assigned_to',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'unread_count' => 'integer',
            'last_message_at' => 'datetime',
            'window_expires_at' => 'datetime',
        ];
    }

    public function messages(): HasMany
    {
        return $this->hasMany(WhatsAppMessage::class, 'whatsapp_conversation_id')->orderBy('created_at', 'asc');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(WhatsAppMessage::class, 'whatsapp_conversation_id')->latestOfMany();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tfcInquiry(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tfc\TfcInquiry::class, 'tfc_inquiry_id');
    }

    public function tfcGrievance(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tfc\TfcGrievanceTicket::class, 'tfc_grievance_id');
    }

    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'assigned_to');
    }

    public function checkoutSessions(): HasMany
    {
        return $this->hasMany(WhatsAppCheckoutSession::class, 'phone', 'phone')->latest();
    }

    public function activeCheckoutSession(): HasOne
    {
        return $this->hasOne(WhatsAppCheckoutSession::class, 'phone', 'phone')
            ->whereNotIn('status', [
                WhatsAppCheckoutSession::STATUS_ORDER_CONFIRMED,
                WhatsAppCheckoutSession::STATUS_CANCELLED,
                WhatsAppCheckoutSession::STATUS_EXPIRED,
            ])
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->latestOfMany();
    }

    public function isWindowOpen(): bool
    {
        return $this->window_expires_at && $this->window_expires_at->isFuture();
    }

    public function windowRemainingFormatted(): string
    {
        if (! $this->isWindowOpen()) {
            return 'Window Closed';
        }

        $now = now();
        $diffHours = (int) $now->diffInHours($this->window_expires_at);
        $diffMinutes = (int) ($now->diffInMinutes($this->window_expires_at) % 60);

        if ($diffHours > 0) {
            return "{$diffHours}h {$diffMinutes}m left";
        }

        return "{$diffMinutes}m left";
    }

    public function markAsRead(): void
    {
        $this->update(['unread_count' => 0]);
        $this->messages()
            ->where('direction', 'inbound')
            ->where('status', '!=', 'read')
            ->update(['status' => 'read', 'read_at' => now()]);
    }

    public static function findOrCreateForPhone(
        string $phone,
        ?string $contactName = null,
        ?string $waId = null,
        string $channel = 'vanriti',
        ?int $tfcInquiryId = null,
        ?int $tfcGrievanceId = null
    ): self {
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($cleanPhone) === 10) {
            $cleanPhone = '91' . $cleanPhone;
        }

        $conversation = static::where('phone', $cleanPhone)
            ->where('channel', $channel)
            ->first();

        if ($channel === 'tfc' && ! $tfcInquiryId) {
            $inq = \App\Models\Tfc\TfcInquiry::where('phone', $cleanPhone)
                ->orWhere('phone', substr($cleanPhone, 2))
                ->latest()
                ->first();
            if ($inq) {
                $tfcInquiryId = $inq->id;
                if (! $contactName) {
                    $contactName = $inq->applicant_name;
                }
            }
        }
        if ($channel === 'tfc' && ! $tfcGrievanceId) {
            $grievance = \App\Models\Tfc\TfcGrievanceTicket::where('phone', $cleanPhone)
                ->orWhere('phone', substr($cleanPhone, 2))
                ->latest()
                ->first();
            if ($grievance) {
                $tfcGrievanceId = $grievance->id;
                if (! $contactName) {
                    $contactName = $grievance->consumer_name;
                }
            }
        }

        if (! $conversation) {
            // Check if there is an existing User with this phone
            $user = User::where('phone', $cleanPhone)->orWhere('phone', substr($cleanPhone, 2))->first();

            $conversation = static::create([
                'phone' => $cleanPhone,
                'wa_id' => $waId ?: $cleanPhone,
                'contact_name' => $contactName ?: ($user?->name ?: 'Customer ' . substr($cleanPhone, -4)),
                'user_id' => $user?->id,
                'channel' => $channel,
                'tfc_inquiry_id' => $tfcInquiryId,
                'tfc_grievance_id' => $tfcGrievanceId,
                'status' => 'active',
                'unread_count' => 0,
            ]);
        } else {
            // Update contact name if provided and not already set
            $updates = [];
            if ($contactName && (empty($conversation->contact_name) || str_starts_with($conversation->contact_name, 'Customer '))) {
                $updates['contact_name'] = $contactName;
            }
            if ($waId && empty($conversation->wa_id)) {
                $updates['wa_id'] = $waId;
            }
            if ($tfcInquiryId && empty($conversation->tfc_inquiry_id)) {
                $updates['tfc_inquiry_id'] = $tfcInquiryId;
            }
            if ($tfcGrievanceId && empty($conversation->tfc_grievance_id)) {
                $updates['tfc_grievance_id'] = $tfcGrievanceId;
            }
            if (! $conversation->user_id) {
                $user = User::where('phone', $cleanPhone)->orWhere('phone', substr($cleanPhone, 2))->first();
                if ($user) {
                    $updates['user_id'] = $user->id;
                    if (empty($conversation->contact_name)) {
                        $updates['contact_name'] = $user->name;
                    }
                }
            }
            if (! empty($updates)) {
                $conversation->update($updates);
            }
        }

        return $conversation;
    }
}
