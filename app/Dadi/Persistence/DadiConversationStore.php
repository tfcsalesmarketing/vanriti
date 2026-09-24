<?php

namespace App\Dadi\Persistence;

use App\Dadi\Enums\SafetyVerdict;
use App\Dadi\ValueObjects\ConversationState;
use App\Dadi\ValueObjects\SafetyAssessment;
use App\Models\DadiConversation;
use App\Models\DadiMessage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * The persistence seam of the Dadi domain: owns how conversations and messages
 * are created and how the authoritative ConversationState crosses the
 * domain/persistence boundary. Domain logic stays in value objects; this class
 * only translates and stores.
 */
class DadiConversationStore
{
    /**
     * Start a new conversation for an authenticated user or a guest.
     *
     * An authenticated user always owns by user_id; guest ownership is the
     * web session id. Supplying both resolves to the authenticated owner.
     */
    public function start(
        ?User $user = null,
        ?string $sessionId = null,
        string $locale = 'en',
        ?ConversationState $state = null,
    ): DadiConversation {
        $sessionId = $sessionId === null ? null : trim($sessionId);

        if ($user === null && ($sessionId === null || $sessionId === '')) {
            throw new InvalidArgumentException('A Dadi conversation requires an authenticated user or a guest session id.');
        }

        if ($user !== null) {
            $sessionId = null;
        }

        return DadiConversation::create([
            'user_id' => $user?->getKey(),
            'session_id' => $sessionId,
            'status' => DadiConversation::STATUS_ACTIVE,
            'locale' => $locale,
            'state' => $state ?? new ConversationState,
            'last_activity_at' => now(),
        ]);
    }

    public function latestActive(?User $user = null, ?string $sessionId = null): ?DadiConversation
    {
        if ($user === null && $sessionId === null) {
            return null;
        }

        return DadiConversation::query()
            ->ownedBy($user, $sessionId)
            ->active()
            ->orderByDesc('last_activity_at')
            ->first();
    }

    /**
     * Append one message to a conversation with deterministic, gap-safe
     * ordering. The conversation row is locked inside a transaction so two
     * concurrent writers can never claim the same sequence position; the
     * unique(conversation_id, sequence) index is the hard backstop.
     */
    public function appendMessage(
        DadiConversation $conversation,
        string $role,
        string $content,
        array $metadata = [],
    ): DadiMessage {
        if (! in_array($role, DadiMessage::roles(), true)) {
            throw new InvalidArgumentException("Unknown message role [{$role}].");
        }

        if (trim($content) === '') {
            throw new InvalidArgumentException('Dadi message content must not be empty.');
        }

        return DB::transaction(function () use ($conversation, $role, $content, $metadata): DadiMessage {
            $locked = DadiConversation::query()
                ->whereKey($conversation->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $nextSequence = (int) DadiMessage::query()
                ->where('conversation_id', $locked->getKey())
                ->max('sequence') + 1;

            $message = DadiMessage::create([
                'conversation_id' => $locked->getKey(),
                'role' => $role,
                'sequence' => $nextSequence,
                'content' => $content,
                'metadata' => $metadata,
            ]);

            $locked->update(['last_activity_at' => now()]);

            return $message;
        });
    }

    public function updateState(DadiConversation $conversation, ConversationState $state): DadiConversation
    {
        $conversation->update(['state' => $state]);

        return $conversation->refresh();
    }

    /**
     * Persist the Laravel-owned safety verdict onto a conversation using the
     * existing state/persistence shape (no new tables).
     *
     *   - Clear:    nothing is recorded; previously held signals stay intact.
     *   - Advisory: the signal categories are recorded into state.safetySignals
     *               so they remain authoritative for later turns.
     *   - Block:    signals are recorded AND the conversation is moved to the
     *               existing safety_hold status. A hold is never auto-cleared
     *               here or anywhere in the domain.
     */
    public function applySafetyAssessment(
        DadiConversation $conversation,
        SafetyAssessment $assessment,
    ): DadiConversation {
        if ($assessment->verdict === SafetyVerdict::Clear) {
            return $conversation->refresh();
        }

        $state = $conversation->state;

        foreach ($assessment->reasons as $code) {
            if (is_string($code) && trim($code) !== '') {
                $state = $state->withSafetySignal($code);
            }
        }

        $this->updateState($conversation, $state);

        if ($assessment->verdict === SafetyVerdict::Block) {
            $conversation->holdForSafety();
        }

        return $conversation->refresh();
    }
}
