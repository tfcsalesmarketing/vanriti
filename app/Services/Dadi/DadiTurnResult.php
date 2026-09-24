<?php

namespace App\Services\Dadi;

use App\Models\DadiConversation;
use App\Models\DadiMessage;

/**
 * The UI-safe outcome of one completed Dadi turn.
 *
 * This is the only response a controller may turn into JSON for the customer
 * UI. It deliberately carries no scores, matched concerns, exclusion reasons,
 * safety codes, prompts or provider payloads — only what the front-end needs
 * to render the reply and any Laravel-owned product cards.
 */
final readonly class DadiTurnResult
{
    /**
     * @param  array<int,array<string,mixed>>  $recommendations
     */
    public function __construct(
        public DadiConversation $conversation,
        public DadiMessage $assistantMessage,
        public array $recommendations = [],
        public bool $restricted = false,
    ) {}
}
