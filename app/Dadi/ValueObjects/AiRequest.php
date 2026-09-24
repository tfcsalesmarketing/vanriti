<?php

namespace App\Dadi\ValueObjects;

use App\Dadi\Exceptions\InvalidAiTurnException;

/**
 * A provider-agnostic request for one AI generation.
 *
 * The conversation engine assembles the layered prompt content and hands it
 * over through this object, so no AI provider ever sees Dadi internals and no
 * provider-specific request object leaks back into the domain. Structured
 * understanding is expressed through the system instructions (the JSON schema),
 * never through provider types.
 */
final readonly class AiRequest
{
    public function __construct(
        public string $systemInstructions,
        public string $userMessage,
        public string $locale = 'en',
        public bool $jsonMode = true,
    ) {
        if (trim($systemInstructions) === '' || trim($userMessage) === '') {
            throw new InvalidAiTurnException('AI requests require system instructions and a user message.');
        }
    }
}
