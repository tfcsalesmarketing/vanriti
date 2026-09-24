<?php

namespace App\Models;

use App\Dadi\Persistence\Casts\ConversationStateCast;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

class DadiConversation extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_ABANDONED = 'abandoned';

    public const STATUS_SAFETY_HOLD = 'safety_hold';

    protected $fillable = [
        'user_id',
        'session_id',
        'status',
        'locale',
        'state',
        'last_activity_at',
    ];

    protected function casts(): array
    {
        return [
            'state' => ConversationStateCast::class,
            'last_activity_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(DadiMessage::class, 'conversation_id')->orderBy('sequence');
    }

    public function lastMessage(): ?DadiMessage
    {
        return DadiMessage::query()
            ->where('conversation_id', $this->getKey())
            ->orderByDesc('sequence')
            ->first();
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function complete(): self
    {
        return $this->transition(self::STATUS_COMPLETED);
    }

    public function markAbandoned(): self
    {
        return $this->transition(self::STATUS_ABANDONED);
    }

    public function holdForSafety(): self
    {
        return $this->transition(self::STATUS_SAFETY_HOLD);
    }

    public function touchActivity(): self
    {
        $this->update(['last_activity_at' => now()]);

        return $this;
    }

    /**
     * Conversations are owned either by an authenticated user (user_id) or by
     * a guest anonymously (user_id null + session_id). Authenticated identity
     * always wins and is never mixed with guest ownership.
     */
    public function scopeOwnedBy(Builder $query, ?User $user, ?string $sessionId): Builder
    {
        if ($user instanceof User) {
            return $query->where('user_id', $user->getKey());
        }

        return $query
            ->whereNull('user_id')
            ->where('session_id', $sessionId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * @return array<int,string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_ACTIVE,
            self::STATUS_COMPLETED,
            self::STATUS_ABANDONED,
            self::STATUS_SAFETY_HOLD,
        ];
    }

    protected function transition(string $status): self
    {
        if (! in_array($status, self::statuses(), true)) {
            throw new InvalidArgumentException("Unknown conversation status [{$status}].");
        }

        $this->update(['status' => $status]);

        return $this;
    }
}
