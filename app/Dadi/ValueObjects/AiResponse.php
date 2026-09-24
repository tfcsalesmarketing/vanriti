<?php

namespace App\Dadi\ValueObjects;

/**
 * A provider-agnostic response from one AI generation.
 *
 * Carries only what Dadi needs: the generated text, a best-effort decoded JSON
 * object (when the provider was asked for structured output) and minimal,
 * safe telemetry metadata. It deliberately has no product fields — the AI is
 * never allowed to select products.
 *
 * The decoded `data` is UNTRUSTED until a Laravel validation layer sanitizes
 * it into domain value objects.
 */
final readonly class AiResponse
{
    /**
     * @param  array<string,mixed>|null  $data
     * @param  array<string,int>  $usage  token usage (prompt/completion/total when available)
     */
    public function __construct(
        public string $content,
        public ?array $data,
        public string $provider = 'openai',
        public string $model = '',
        public ?string $finishReason = null,
        public array $usage = [],
        public ?float $latencyMs = null,
        public ?string $requestId = null,
    ) {}

    public function promptTokens(): int
    {
        return (int) ($this->usage['prompt_tokens'] ?? 0);
    }

    public function completionTokens(): int
    {
        return (int) ($this->usage['completion_tokens'] ?? 0);
    }

    public function totalTokens(): int
    {
        return (int) ($this->usage['total_tokens'] ?? 0);
    }
}
