<?php

namespace App\Dadi\Context;

use App\Dadi\Exceptions\DadiOwnershipException;
use App\Dadi\ValueObjects\ConversationState;
use App\Dadi\ValueObjects\DadiContext;
use App\Dadi\ValueObjects\DadiContextMessage;
use App\Dadi\ValueObjects\DadiMemoryItem;
use App\Models\DadiConversation;
use App\Models\DadiMessage;
use App\Models\User;

/**
 * Assembles the immutable conversational context for the future AI layer.
 *
 * This seam is read-only: it loads the owned conversation, the bounded recent
 * messages (Layer A), the authoritative persisted ConversationState (Layer B)
 * and the bounded active historical memory (Layer C), then hands everything
 * over as plain domain data. It never calls an AI provider, never generates a
 * response, never selects a product and never evaluates recommendations.
 *
 * Ownership is enforced here: asking for a conversation this identity does not
 * own fails identically to asking for one that does not exist.
 */
class DadiContextBuilder
{
    /**
     * @param  int  $recentMessagesLimit  Layer A window (config: dadi.context.recent_messages_limit)
     * @param  int  $historicalMemoryLimit  Layer C bound (config: dadi.context.historical_memory_limit)
     */
    public function __construct(
        private readonly int $recentMessagesLimit,
        private readonly int $historicalMemoryLimit,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            recentMessagesLimit: (int) config('dadi.context.recent_messages_limit', 20),
            historicalMemoryLimit: (int) config('dadi.context.historical_memory_limit', 15),
        );
    }

    public function forConversation(int $conversationId, ?User $user = null, ?string $sessionId = null): DadiContext
    {
        if ($user === null && $sessionId === null) {
            throw new DadiOwnershipException(
                'A Dadi context requires an authenticated user or a guest session id.',
            );
        }

        $conversation = DadiConversation::query()
            ->ownedBy($user, $sessionId)
            ->whereKey($conversationId)
            ->first();

        if ($conversation === null) {
            throw new DadiOwnershipException(
                "Conversation [{$conversationId}] is not owned by the given identity.",
            );
        }

        return new DadiContext(
            conversationId: (int) $conversation->getKey(),
            locale: (string) $conversation->locale,
            recentMessages: $this->recentMessages($conversation),
            state: $conversation->state,
            historicalMemory: $this->historicalMemory($conversation->state),
        );
    }

    /**
     * @return array<int,DadiContextMessage>
     */
    private function recentMessages(DadiConversation $conversation): array
    {
        return DadiMessage::query()
            ->where('conversation_id', $conversation->getKey())
            ->orderByDesc('sequence')
            ->limit($this->recentMessagesLimit)
            ->get()
            ->reverse()
            ->values()
            ->map(static fn (DadiMessage $message): DadiContextMessage => new DadiContextMessage(
                role: (string) $message->role,
                content: (string) $message->content,
                sequence: (int) $message->sequence,
            ))
            ->all();
    }

    /**
     * @return array<int,DadiMemoryItem>
     */
    private function historicalMemory(ConversationState $state): array
    {
        $memory = $state->memoryActive();

        usort($memory, static fn (DadiMemoryItem $a, DadiMemoryItem $b): int => $b->updatedSequence <=> $a->updatedSequence);

        return array_slice($memory, 0, $this->historicalMemoryLimit);
    }
}
