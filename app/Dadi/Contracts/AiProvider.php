<?php

namespace App\Dadi\Contracts;

use App\Dadi\ValueObjects\AiRequest;
use App\Dadi\ValueObjects\AiResponse;

/**
 * The provider abstraction of the Dadi conversation engine.
 *
 * A provider maps a generic AiRequest to a generic AiResponse. It knows nothing
 * about Dadi conversations, products or state; OpenAI is the first
 * implementation, and Gemini/Anthropic can be added later without touching the
 * engine. Providers must never expose provider-specific request/response
 * objects to the rest of the domain.
 */
interface AiProvider
{
    public function generate(AiRequest $request): AiResponse;
}
