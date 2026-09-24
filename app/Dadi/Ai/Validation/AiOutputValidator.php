<?php

namespace App\Dadi\Ai\Validation;

use App\Dadi\Enums\Concern;
use App\Dadi\Enums\ConversationIntent;
use App\Dadi\Enums\Section;
use App\Dadi\Exceptions\InvalidAiOutputException;
use App\Dadi\ValueObjects\AiResponse;
use App\Dadi\ValueObjects\AiTurnResult;
use App\Dadi\ValueObjects\ConversationState;
use App\Dadi\ValueObjects\DadiMemoryItem;

/**
 * Deterministic Laravel-side validation of AI output.
 *
 * The provider response is untrusted until it passes through here. This layer:
 *   - enforces the closed JSON shape and enum vocabularies;
 *   - rejects forbidden product fields (sku, product_id, price, stock, ...);
 *   - drops any memory item whose slot or value names commerce terms, so
 *     product/order/payment facts can never be stored as conversational memory;
 *   - caps pathological lengths/counts;
 *   - rebuilds the proposed ConversationState using ONLY the existing
 *     immutable semantics (withSection / withConcern / withAttribute /
 *     withPreference / remember / withoutConcern / withoutPreference /
 *     withoutAttribute / forgetIfPresent), so corrections supersede stale
 *     values, explicitly corrected facts are retired, and existing facts
 *     are preserved.
 *
 * Nothing reaches persistence until it has passed this gateway. On structurally
 * unusable output it throws InvalidAiOutputException and the engine falls back
 * gracefully with the state untouched.
 */
final class AiOutputValidator
{
    public const FORBIDDEN_FIELDS = [
        'sku',
        'product_id',
        'product_ids',
        'product',
        'price',
        'stock',
        'recommended_sku',
        'recommended',
        'catalogue',
    ];

    /**
     * Laravel-owned state keys the AI is read-only for. The customer's language
     * preference lives on ConversationState and is created only by validated
     * onboarding input; it is injected into the prompt as trusted context and
     * is therefore never rewritten from AI output. Any attempt to sneak it in
     * through attributes, preferences or corrections is deterministically
     * dropped, so no provider response can overwrite the stored preference.
     */
    public const RESERVED_STATE_KEYS = [
        'preferred_language',
        'preferred_language_name',
    ];

    /**
     * Commerce vocabulary that may never appear in a memory slot or value.
     * Memory is conversational context (concern/preference/avoidance/topic/
     * note) only; product identity and commercial lifecycle facts belong to
     * the Laravel catalogue, so any item containing one of these terms is
     * deterministically dropped instead of persisted.
     */
    public const MEMORY_COMMERCE_TERMS = [
        'sku',
        'product_id',
        'product_ids',
        'product',
        'price',
        'mrp',
        'stock',
        'cart',
        'checkout',
        'order',
        'orders',
        'payment',
        'payments',
        'coupon',
        'discount',
        'invoice',
        'billing',
        'inventory',
        'barcode',
    ];

    public function __construct(
        private readonly int $maxReplyLength = 600,
        private readonly int $maxConcerns = 12,
        private readonly int $maxAttributes = 25,
        private readonly int $maxPreferences = 25,
        private readonly int $maxMemoryItems = 20,
    ) {}

    public function validate(AiResponse $response, ConversationState $current, int $sequence = 1): AiTurnResult
    {
        $data = $response->data ?? $this->decode($response->content);

        if (! is_array($data)) {
            throw new InvalidAiOutputException('AI output is not a valid JSON object.');
        }

        $this->assertNoForbiddenFields($data);

        $reply = $this->reply((string) ($data['reply'] ?? ''));
        $intent = ConversationIntent::tryFrom((string) ($data['intent'] ?? '')) ?? ConversationIntent::Unclear;
        $requiresSafetyReview = (bool) ($data['safety_review'] ?? false);

        $understanding = $data['understanding'] ?? [];
        $understanding = is_array($understanding) ? $understanding : [];

        $state = $current;

        $section = Section::tryFrom((string) ($understanding['section'] ?? ''));

        if ($section !== null) {
            $state = $state->withSection($section);
        }

        foreach ($this->stringList($understanding['concerns'] ?? [], $this->maxConcerns) as $concern) {
            $state = $state->withConcern($concern);
        }

        foreach ($this->scalarMap($understanding['attributes'] ?? [], $this->maxAttributes) as $key => $value) {
            if ($this->isReservedStateKey($key)) {
                continue;
            }

            $state = $state->withAttribute($key, $value);
        }

        foreach ($this->scalarMap($understanding['preferences'] ?? [], $this->maxPreferences) as $key => $value) {
            if ($this->isReservedStateKey($key)) {
                continue;
            }

            $state = $state->withPreference($key, $value);
        }

        foreach ($this->memoryItems($understanding['memory'] ?? [], $sequence) as $item) {
            $state = $state->remember(
                category: $item['category'],
                slot: $item['slot'],
                value: $item['value'],
                sequence: $item['sequence'],
                importance: $item['importance'],
            );
        }

        $state = $this->applyCorrections($state, $understanding['corrected'] ?? [], $sequence);

        return new AiTurnResult(
            reply: $reply,
            intent: $intent,
            proposedState: $state,
            requiresSafetyReview: $requiresSafetyReview,
        );
    }

    /**
     * Retire explicitly corrected facts from the proposed state. This is the
     * removal side of conversation continuity: additions are applied first,
     * then this runs so the latest, explicit correction wins even inside one
     * turn. Corrected concerns are filtered to canonical codes; preferences,
     * attributes and memory retire by their exact stored keys/slots and are
     * tolerant when the state does not actually hold them.
     */
    private function applyCorrections(ConversationState $state, mixed $corrected, int $sequence): ConversationState
    {
        if (! is_array($corrected)) {
            return $state;
        }

        foreach ($this->stringList($corrected['concerns'] ?? [], $this->maxConcerns) as $code) {
            if (Concern::tryFrom($code) === null) {
                continue;
            }

            $state = $state->withoutConcern($code);
            $state = $state->forgetIfPresent('concern', $code, $sequence);
        }

        foreach ($this->stringList($corrected['preferences'] ?? [], $this->maxPreferences) as $key) {
            $state = $state->withoutPreference($key);
            $state = $state->forgetIfPresent('preference', $key, $sequence);
            $state = $state->forgetIfPresent('avoidance', $key, $sequence);
        }

        foreach ($this->stringList($corrected['attributes'] ?? [], $this->maxAttributes) as $key) {
            if ($this->isReservedStateKey($key)) {
                continue;
            }

            $state = $state->withoutAttribute($key);
        }

        foreach ($this->correctedMemory($corrected['memory'] ?? []) as $item) {
            $state = $state->forgetIfPresent($item['category'], $item['slot'], $sequence);
        }

        return $state;
    }

    /**
     * Normalize the explicit memory-retirement list. Only closed categories
     * with a non-empty slot are accepted, and duplicates are collapsed.
     *
     * @return array<int,array{category:string,slot:string}>
     */
    private function correctedMemory(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $out = [];

        foreach ($value as $item) {
            if (! is_array($item)) {
                continue;
            }

            $category = trim((string) ($item['category'] ?? ''));
            $slot = trim((string) ($item['slot'] ?? ''));

            if (! in_array($category, DadiMemoryItem::CATEGORIES, true) || $slot === '') {
                continue;
            }

            $key = $category.':'.$slot;

            if (isset($out[$key])) {
                continue;
            }

            $out[$key] = ['category' => $category, 'slot' => $slot];
        }

        return array_values($out);
    }

    /**
     * True when a memory slot or value names a commerce term. Word-bounded so
     * ordinary conversational words are untouched, but any product / price /
     * order / payment vocabulary in a memory item keeps that item out of
     * persistence (the AI must not store commercial facts in memory).
     */
    private function containsCommerceTerm(string $text): bool
    {
        $text = mb_strtolower(trim($text));

        if (str_contains($text, '₹') || str_contains($text, 'rs.') || str_contains($text, ' rs ')) {
            return true;
        }

        foreach (self::MEMORY_COMMERCE_TERMS as $term) {
            if (preg_match('/\b'.preg_quote($term, '/').'\b/u', $text) === 1) {
                return true;
            }
        }

        return false;
    }

    private function isReservedStateKey(string $key): bool
    {
        return in_array(strtolower(trim($key)), self::RESERVED_STATE_KEYS, true);
    }

    private function reply(string $candidate): string
    {
        $reply = trim($candidate);

        if ($reply === '') {
            throw new InvalidAiOutputException('AI reply is missing.');
        }

        if (mb_strlen($reply) > $this->maxReplyLength) {
            throw new InvalidAiOutputException(
                "AI reply exceeds the {$this->maxReplyLength} character limit.",
            );
        }

        return $reply;
    }

    /**
     * @param  array<string,mixed>  $data
     */
    private function assertNoForbiddenFields(array $data): void
    {
        $found = false;

        $scan = function (array $node) use (&$scan, &$found): void {
            foreach ($node as $key => $value) {
                if (in_array(strtolower((string) $key), self::FORBIDDEN_FIELDS, true)) {
                    $found = true;

                    return;
                }

                if (is_array($value)) {
                    $scan($value);
                }
            }
        };

        $scan($data);

        if ($found) {
            throw new InvalidAiOutputException('AI output contains a forbidden product field.');
        }
    }

    /**
     * @return array<int,string>
     */
    private function stringList(mixed $value, int $capacity): array
    {
        if (! is_array($value)) {
            return [];
        }

        $out = [];

        foreach ($value as $item) {
            if (count($out) >= $capacity) {
                break;
            }

            if (! is_string($item)) {
                continue;
            }

            $trimmed = trim($item);

            if ($trimmed === '' || in_array($trimmed, $out, true)) {
                continue;
            }

            $out[] = $trimmed;
        }

        return $out;
    }

    /**
     * @return array<string,mixed>
     */
    private function scalarMap(mixed $value, int $capacity): array
    {
        if (! is_array($value)) {
            return [];
        }

        $out = [];

        foreach ($value as $key => $item) {
            if (count($out) >= $capacity) {
                break;
            }

            if (! is_string($key) || trim($key) === '' || ! is_scalar($item)) {
                continue;
            }

            $out[trim($key)] = $item;
        }

        return $out;
    }

    /**
     * @return array<int,array{category:string,slot:string,value:string,sequence:int,importance:string}>
     */
    private function memoryItems(mixed $value, int $sequence): array
    {
        if (! is_array($value)) {
            return [];
        }

        $out = [];

        foreach ($value as $item) {
            if (count($out) >= $this->maxMemoryItems || ! is_array($item)) {
                continue;
            }

            $category = trim((string) ($item['category'] ?? ''));
            $slot = trim((string) ($item['slot'] ?? ''));
            $memoryValue = trim((string) ($item['value'] ?? ''));

            if ($slot === '' || $memoryValue === '') {
                continue;
            }

            if (! in_array($category, DadiMemoryItem::CATEGORIES, true)) {
                continue;
            }

            if ($this->containsCommerceTerm($slot) || $this->containsCommerceTerm($memoryValue)) {
                continue;
            }

            $importance = (string) ($item['importance'] ?? 'normal');

            if (! in_array($importance, DadiMemoryItem::IMPORTANCES, true)) {
                $importance = 'normal';
            }

            $out[] = [
                'category' => $category,
                'slot' => $slot,
                'value' => $memoryValue,
                'sequence' => $sequence,
                'importance' => $importance,
            ];
        }

        return $out;
    }

    /**
     * @return array<string,mixed>|null
     */
    private function decode(string $content): ?array
    {
        $candidate = $content;

        if (str_starts_with($candidate, '```')) {
            $candidate = preg_replace('/^```(?:json)?\s*/i', '', $candidate) ?? $candidate;
            $candidate = preg_replace('/\s*```$/', '', $candidate) ?? $candidate;
        }

        $decoded = json_decode($candidate, true);

        return is_array($decoded) ? $decoded : null;
    }
}
