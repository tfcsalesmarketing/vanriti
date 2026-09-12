<?php

namespace App\Dadi\ValueObjects;

use App\Dadi\Enums\Section;
use App\Dadi\Exceptions\InvalidConversationStateException;

/**
 * The single Laravel-owned understanding of a conversation.
 *
 * Immutable by design: every "with*" method returns a new instance so no
 * layer can mutate state it does not own. Values are plain strings and scalars
 * keyed by domain codes; code/vocabulary validation against the product
 * catalog happens in a Laravel layer, not here.
 *
 * readiness_for_recommendation is only ever affirmed by Laravel business
 * rules — never by the AI.
 */
final readonly class ConversationState
{
    /**
     * @param  array<int,string>  $concerns
     * @param  array<string,mixed>  $attributes
     * @param  array<string,mixed>  $preferences
     * @param  array<int,string>  $safetySignals
     * @param  array<int,DadiMemoryItem>  $memory
     */
    public function __construct(
        public ?Section $section = null,
        public array $concerns = [],
        public array $attributes = [],
        public array $preferences = [],
        public array $safetySignals = [],
        public bool $readyForRecommendation = false,
        public array $memory = [],
        public ?string $preferredLanguage = null,
        public ?string $preferredLanguageName = null,
    ) {}

    /**
     * @param  array<string,mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            section: self::normalizeSection($data['section'] ?? null),
            concerns: self::stringList($data['concerns'] ?? []),
            attributes: self::scalarMap($data['attributes'] ?? []),
            preferences: self::scalarMap($data['preferences'] ?? []),
            safetySignals: self::stringList($data['safety_signals'] ?? []),
            readyForRecommendation: (bool) ($data['ready_for_recommendation'] ?? false),
            memory: self::memoryList($data['memory'] ?? []),
            preferredLanguage: self::nullableString($data['preferred_language'] ?? null),
            preferredLanguageName: self::nullableString($data['preferred_language_name'] ?? null),
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        $out = [
            'section' => $this->section?->value,
            'concerns' => $this->concerns,
            'attributes' => $this->attributes,
            'preferences' => $this->preferences,
            'safety_signals' => $this->safetySignals,
            'ready_for_recommendation' => $this->readyForRecommendation,
            'memory' => array_map(
                static fn (DadiMemoryItem $item): array => $item->toArray(),
                $this->memory,
            ),
        ];

        // The language preference is emitted only when actually set, so a
        // language-less state serializes exactly as it always did.
        if ($this->preferredLanguage !== null) {
            $out['preferred_language'] = $this->preferredLanguage;
        }

        if ($this->preferredLanguageName !== null) {
            $out['preferred_language_name'] = $this->preferredLanguageName;
        }

        return $out;
    }

    public function withSection(?Section $section): self
    {
        return new self(
            section: $section,
            concerns: $this->concerns,
            attributes: $this->attributes,
            preferences: $this->preferences,
            safetySignals: $this->safetySignals,
            readyForRecommendation: $this->readyForRecommendation,
            memory: $this->memory,
            preferredLanguage: $this->preferredLanguage,
            preferredLanguageName: $this->preferredLanguageName,
        );
    }

    public function withConcern(string $code): self
    {
        $code = trim($code);

        if ($code === '') {
            throw new InvalidConversationStateException('Concern codes must not be empty.');
        }

        $concerns = $this->concerns;

        if (! in_array($code, $concerns, true)) {
            $concerns[] = $code;
        }

        return new self(
            section: $this->section,
            concerns: $concerns,
            attributes: $this->attributes,
            preferences: $this->preferences,
            safetySignals: $this->safetySignals,
            readyForRecommendation: $this->readyForRecommendation,
            memory: $this->memory,
            preferredLanguage: $this->preferredLanguage,
            preferredLanguageName: $this->preferredLanguageName,
        );
    }

    /**
     * Retire one previously stored concern code. This is the removal side of
     * conversation continuity: a corrected fact leaves the understanding
     * without throwing away the rest of the conversation history. A missing
     * code is a no-op.
     */
    public function withoutConcern(string $code): self
    {
        $code = trim($code);

        if ($code === '') {
            throw new InvalidConversationStateException('Concern codes must not be empty.');
        }

        return new self(
            section: $this->section,
            concerns: array_values(array_diff($this->concerns, [$code])),
            attributes: $this->attributes,
            preferences: $this->preferences,
            safetySignals: $this->safetySignals,
            readyForRecommendation: $this->readyForRecommendation,
            memory: $this->memory,
            preferredLanguage: $this->preferredLanguage,
            preferredLanguageName: $this->preferredLanguageName,
        );
    }

    public function withAttribute(string $key, mixed $value): self
    {
        if (trim($key) === '' || ! is_scalar($value)) {
            throw new InvalidConversationStateException('Attributes require a non-empty key and a scalar value.');
        }

        $attributes = $this->attributes;
        $attributes[trim($key)] = $value;

        return new self(
            section: $this->section,
            concerns: $this->concerns,
            attributes: $attributes,
            preferences: $this->preferences,
            safetySignals: $this->safetySignals,
            readyForRecommendation: $this->readyForRecommendation,
            memory: $this->memory,
            preferredLanguage: $this->preferredLanguage,
            preferredLanguageName: $this->preferredLanguageName,
        );
    }

    /**
     * Remove one attribute key. A missing key is a no-op.
     */
    public function withoutAttribute(string $key): self
    {
        $key = trim($key);

        $attributes = $this->attributes;
        unset($attributes[$key]);

        return new self(
            section: $this->section,
            concerns: $this->concerns,
            attributes: $attributes,
            preferences: $this->preferences,
            safetySignals: $this->safetySignals,
            readyForRecommendation: $this->readyForRecommendation,
            memory: $this->memory,
            preferredLanguage: $this->preferredLanguage,
            preferredLanguageName: $this->preferredLanguageName,
        );
    }

    public function withPreference(string $key, mixed $value): self
    {
        if (trim($key) === '' || ! is_scalar($value)) {
            throw new InvalidConversationStateException('Preferences require a non-empty key and a scalar value.');
        }

        $preferences = $this->preferences;
        $preferences[trim($key)] = $value;

        return new self(
            section: $this->section,
            concerns: $this->concerns,
            attributes: $this->attributes,
            preferences: $preferences,
            safetySignals: $this->safetySignals,
            readyForRecommendation: $this->readyForRecommendation,
            memory: $this->memory,
            preferredLanguage: $this->preferredLanguage,
            preferredLanguageName: $this->preferredLanguageName,
        );
    }

    /**
     * Remove one preference key. A missing key is a no-op.
     */
    public function withoutPreference(string $key): self
    {
        $key = trim($key);

        $preferences = $this->preferences;
        unset($preferences[$key]);

        return new self(
            section: $this->section,
            concerns: $this->concerns,
            attributes: $this->attributes,
            preferences: $preferences,
            safetySignals: $this->safetySignals,
            readyForRecommendation: $this->readyForRecommendation,
            memory: $this->memory,
            preferredLanguage: $this->preferredLanguage,
            preferredLanguageName: $this->preferredLanguageName,
        );
    }

    /**
     * Set the customer's conversation language preference.
     *
     * Laravel-owned data only: the AI never writes these fields. Passing null
     * (or trimming to empty) clears the preference; the optional name is only
     * meaningful for "other" and is stored as bounded data, nothing more.
     */
    public function withPreferredLanguage(?string $language, ?string $name = null): self
    {
        $language = $language === null ? null : trim($language);
        $name = $name === null ? null : trim($name);

        if ($language === '') {
            $language = null;
        }

        if ($name === '') {
            $name = null;
        }

        return new self(
            section: $this->section,
            concerns: $this->concerns,
            attributes: $this->attributes,
            preferences: $this->preferences,
            safetySignals: $this->safetySignals,
            readyForRecommendation: $this->readyForRecommendation,
            memory: $this->memory,
            preferredLanguage: $language,
            preferredLanguageName: $name,
        );
    }

    public function withSafetySignal(string $code): self
    {
        $code = trim($code);

        if ($code === '') {
            throw new InvalidConversationStateException('Safety signal codes must not be empty.');
        }

        $signals = $this->safetySignals;

        if (! in_array($code, $signals, true)) {
            $signals[] = $code;
        }

        return new self(
            section: $this->section,
            concerns: $this->concerns,
            attributes: $this->attributes,
            preferences: $this->preferences,
            safetySignals: $signals,
            readyForRecommendation: $this->readyForRecommendation,
            memory: $this->memory,
            preferredLanguage: $this->preferredLanguage,
            preferredLanguageName: $this->preferredLanguageName,
        );
    }

    public function withReadyForRecommendation(bool $ready): self
    {
        return new self(
            section: $this->section,
            concerns: $this->concerns,
            attributes: $this->attributes,
            preferences: $this->preferences,
            safetySignals: $this->safetySignals,
            readyForRecommendation: $ready,
            memory: $this->memory,
            preferredLanguage: $this->preferredLanguage,
            preferredLanguageName: $this->preferredLanguageName,
        );
    }

    /**
     * Merge a newer snapshot into this one. Older facts are retained where the
     * newer snapshot does not contradict them; attributes/preferences follow
     * the newer value on equal keys; concerns and safety signals are unioned.
     */
    public function merge(self $newer): self
    {
        $memory = $this->memory;

        foreach ($newer->memory as $item) {
            $memory = self::upsertMemoryItem($memory, $item);
        }

        return new self(
            section: $newer->section ?? $this->section,
            concerns: array_values(array_unique(array_merge($this->concerns, $newer->concerns))),
            attributes: array_merge($this->attributes, $newer->attributes),
            preferences: array_merge($this->preferences, $newer->preferences),
            safetySignals: array_values(array_unique(array_merge($this->safetySignals, $newer->safetySignals))),
            readyForRecommendation: $this->readyForRecommendation || $newer->readyForRecommendation,
            memory: $memory,
            preferredLanguage: $newer->preferredLanguage ?? $this->preferredLanguage,
            preferredLanguageName: $newer->preferredLanguageName ?? $this->preferredLanguageName,
        );
    }

    public function hasConcern(string $code): bool
    {
        return in_array($code, $this->concerns, true);
    }

    public function attribute(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    public function preference(string $key): mixed
    {
        return $this->preferences[$key] ?? null;
    }

    /**
     * Record (or overwrite) one classified memory slot. Slot identity is
     * (category, slot): re-stating the same fact replaces its value, so a
     * correction supersedes stale information instead of accumulating next to
     * it. The first source sequence is preserved; the updated sequence always
     * points at the newest statement touching the slot.
     *
     * When a per-category capacity is given, the oldest active items of that
     * category are dropped first, keeping the stored memory bounded.
     */
    public function remember(
        string $category,
        string $slot,
        string $value,
        int $sequence,
        ?string $importance = null,
        ?int $capacity = null,
    ): self {
        $category = trim($category);
        $slot = trim($slot);
        $value = trim($value);

        if ($sequence < 1) {
            throw new InvalidConversationStateException('Memory sequences must be positive.');
        }

        $existing = $this->memoryItem($category, $slot);

        $item = new DadiMemoryItem(
            category: $category,
            slot: $slot,
            value: $value,
            active: true,
            importance: $importance ?? $existing?->importance ?? 'normal',
            sourceSequence: $existing?->sourceSequence ?? $sequence,
            updatedSequence: $sequence,
        );

        $memory = self::upsertMemoryItem($this->memory, $item);

        if ($capacity !== null) {
            $candidates = [];

            foreach ($memory as $index => $candidate) {
                if ($candidate->category === $category && $candidate->active) {
                    $candidates[] = ['index' => $index, 'item' => $candidate];
                }
            }

            usort($candidates, static fn (array $a, array $b): int => $a['item']->updatedSequence <=> $b['item']->updatedSequence);

            $excess = count($candidates) - max(1, $capacity);

            foreach (array_slice($candidates, 0, max(0, $excess)) as $candidate) {
                $stale = $candidate['item'];

                $memory[$candidate['index']] = new DadiMemoryItem(
                    category: $stale->category,
                    slot: $stale->slot,
                    value: $stale->value,
                    active: false,
                    importance: $stale->importance,
                    sourceSequence: $stale->sourceSequence,
                    updatedSequence: $stale->updatedSequence,
                );
            }
        }

        return new self(
            section: $this->section,
            concerns: $this->concerns,
            attributes: $this->attributes,
            preferences: $this->preferences,
            safetySignals: $this->safetySignals,
            readyForRecommendation: $this->readyForRecommendation,
            memory: array_values($memory),
            preferredLanguage: $this->preferredLanguage,
            preferredLanguageName: $this->preferredLanguageName,
        );
    }

    /**
     * Deactivate a memory slot without erasing history. Inactive items never
     * surface in the historical memory exposed to the AI layer. Forgetting an
     * unknown slot is an error so a mis-piped retirement call is caught.
     */
    public function forget(string $category, string $slot, int $sequence): self
    {
        return $this->deactivateMemory($category, $slot, $sequence, tolerant: false);
    }

    /**
     * The tolerant variant used by the AI-output correction channel: retiring
     * a slot that is not (or no longer) stored is simply a no-op instead of an
     * exception, because the AI's correction list may name a slot Laravel
     * never kept.
     */
    public function forgetIfPresent(string $category, string $slot, int $sequence): self
    {
        return $this->deactivateMemory($category, $slot, $sequence, tolerant: true);
    }

    private function deactivateMemory(string $category, string $slot, int $sequence, bool $tolerant): self
    {
        $memory = $this->memory;
        $changed = false;

        foreach ($memory as $index => $item) {
            if ($item->category !== $category || $item->slot !== $slot) {
                continue;
            }

            $memory[$index] = new DadiMemoryItem(
                category: $item->category,
                slot: $item->slot,
                value: $item->value,
                active: false,
                importance: $item->importance,
                sourceSequence: $item->sourceSequence,
                updatedSequence: max($item->updatedSequence, $sequence),
            );

            $changed = true;
        }

        if (! $changed && ! $tolerant) {
            throw new InvalidConversationStateException(
                "Cannot forget unknown memory slot [{$category}:{$slot}].",
            );
        }

        return new self(
            section: $this->section,
            concerns: $this->concerns,
            attributes: $this->attributes,
            preferences: $this->preferences,
            safetySignals: $this->safetySignals,
            readyForRecommendation: $this->readyForRecommendation,
            memory: array_values($memory),
            preferredLanguage: $this->preferredLanguage,
            preferredLanguageName: $this->preferredLanguageName,
        );
    }

    /**
     * @return array<int,DadiMemoryItem>
     */
    public function memoryActive(): array
    {
        return array_values(array_filter(
            $this->memory,
            static fn (DadiMemoryItem $item): bool => $item->active,
        ));
    }

    public function memoryItem(string $category, string $slot): ?DadiMemoryItem
    {
        foreach ($this->memory as $item) {
            if ($item->category === $category && $item->slot === $slot) {
                return $item;
            }
        }

        return null;
    }

    public function memoryCount(): int
    {
        return count($this->memory);
    }

    public function isEmpty(): bool
    {
        return $this->section === null
            && $this->concerns === []
            && $this->attributes === []
            && $this->preferences === []
            && $this->safetySignals === []
            && $this->memory === []
            && $this->preferredLanguage === null
            && $this->preferredLanguageName === null;
    }

    private static function normalizeSection(mixed $value): ?Section
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof Section) {
            return $value;
        }

        $section = Section::tryFrom((string) $value);

        if ($section === null) {
            throw new InvalidConversationStateException("Unknown section '{$value}'. Allowed: hair, skin, wellness.");
        }

        return $section;
    }

    /**
     * @return array<int,string>
     */
    private static function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            throw new InvalidConversationStateException('Expected a list of strings.');
        }

        $out = [];

        foreach ($value as $item) {
            if (! is_string($item) || trim($item) === '') {
                throw new InvalidConversationStateException('List items must be non-empty strings.');
            }

            $out[] = trim($item);
        }

        return $out;
    }

    /**
     * @return array<string,mixed>
     */
    private static function scalarMap(mixed $value): array
    {
        if (! is_array($value)) {
            throw new InvalidConversationStateException('Expected a map of scalar values.');
        }

        $out = [];

        foreach ($value as $key => $item) {
            if (! is_string($key) || trim($key) === '' || ! is_scalar($item)) {
                throw new InvalidConversationStateException('Map keys must be non-empty strings with scalar values.');
            }

            $out[trim($key)] = $item;
        }

        return $out;
    }

    /**
     * @return array<int,DadiMemoryItem>
     */
    private static function memoryList(mixed $value): array
    {
        if (! is_array($value)) {
            throw new InvalidConversationStateException('Expected a list of memory items.');
        }

        $out = [];

        foreach ($value as $item) {
            if (! is_array($item)) {
                throw new InvalidConversationStateException('Memory items must be arrays.');
            }

            $out[] = DadiMemoryItem::fromArray($item);
        }

        return $out;
    }

    /**
     * Persisted language fields are nullable and stringly-typed; anything that
     * is not a string is treated as absent (defensive against hand-edited JSON).
     */
    private static function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    /**
     * Upsert a memory item by (category, slot). An existing slot keeps its
     * first source sequence and its array position; its value, activeness and
     * importance are replaced by the incoming item.
     *
     * @param  array<int,DadiMemoryItem>  $memory
     * @return array<int,DadiMemoryItem>
     */
    private static function upsertMemoryItem(array $memory, DadiMemoryItem $item): array
    {
        foreach ($memory as $index => $existing) {
            if ($existing->category !== $item->category || $existing->slot !== $item->slot) {
                continue;
            }

            $memory[$index] = new DadiMemoryItem(
                category: $existing->category,
                slot: $existing->slot,
                value: $item->value,
                active: $item->active,
                importance: $item->importance,
                sourceSequence: $existing->sourceSequence,
                updatedSequence: $item->updatedSequence,
            );

            return $memory;
        }

        $memory[] = $item;

        return $memory;
    }
}
