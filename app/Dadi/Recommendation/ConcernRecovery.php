<?php

namespace App\Dadi\Recommendation;

use App\Dadi\Enums\Concern;
use App\Dadi\ValueObjects\ConversationState;

/**
 * Deterministic, Laravel-owned recovery of canonical concern codes.
 *
 * This is NOT an AI system, a diagnostic system, or a second recommendation
 * engine. It sits between AI-output validation and the authoritative state
 * commit, and has exactly two responsibilities:
 *
 *   1. Validate AI-proposed concerns — only Concern enum values survive, so a
 *      fabricated token such as "something_dry" stays inert and never reaches
 *      the authoritative state.
 *   2. Recover clearly expressed concerns from the customer message — only
 *      when the untrusted customer language contains a supported, word-bounded,
 *      negation-aware trigger from configuration.
 *
 * Anti-fabrication is the core rule: false positives are worse than missed
 * matches. Vague statements, instruction-like text, HTML/JSON, product or SKU
 * strings and unrelated conversation recover nothing. Negation cancels a
 * trigger locally (single clause scope, never the whole sentence), so
 * "baal dry nahi hain lekin scalp oily hai" recovers oily_scalp but never
 * hair_dryness.
 *
 * Determinism: the same message, proposed concerns and configuration always
 * produce the same result. There is no randomness, no external API, no AI call
 * and no database access; the returned list is bounded by the configured limit.
 */
final class ConcernRecovery
{
    public const DEFAULT_MAX_RESULTS = 12;

    public const DEFAULT_TRIGGER_GAP = 2;

    private const LOOKBACK_TOKENS = 3;

    /**
     * Negation words that can stand before a trigger and cancel it.
     */
    private const NEGATION_LEADS = [
        'no',
        'not',
        'never',
        'none',
        'non',
        'without',
        'dont',
        'doesnt',
        'didnt',
        'wasnt',
        'isnt',
        'wont',
        'nahi',
        'nhi',
        'nai',
        'nahin',
        'na',
        'mat',
        'bina',
    ];

    /**
     * Negation markers that cancel a trigger when they directly follow it
     * (Hinglish word order places them after the trait: "dry nahi",
     * "dandruff nahi hai", plus English trailing "free" as in "stress-free").
     */
    private const TRAILING_NEGATION_LEADS = [
        'nahi',
        'nhi',
        'nai',
        'nahin',
        'na',
        'free',
        'none',
    ];

    /**
     * Function words a negation may reach through before the trigger. Content
     * words and clause connectors are walls, so a negation in a previous clause
     * never leaks onto an unrelated trait ("not dry, but oily" keeps "oily").
     */
    private const BRIDGE_WORDS = [
        'a',
        'an',
        'the',
        'any',
        'too',
        'so',
        'very',
        'quite',
        'at',
        'of',
        'from',
        'to',
        'for',
        'with',
        'by',
        'and',
        'also',
        'is',
        'are',
        'was',
        'were',
        'be',
        'been',
        'being',
        'have',
        'has',
        'had',
        'do',
        'does',
        'did',
        'my',
        'your',
        'our',
        'his',
        'her',
        'their',
        'hai',
        'hain',
        'ho',
        'tha',
        'thi',
        'the',
        'se',
        'ko',
        'mein',
        'me',
        'ka',
        'ki',
        'ke',
        'hota',
        'hoti',
        'raha',
        'rahi',
    ];

    /**
     * Clause connectors that start a fresh clause; a multi-word trigger can
     * never span one, and a previous negation cannot leak across it.
     */
    private const CLAUSE_WALLS = [
        'but',
        'and',
        'or',
        'so',
        'yet',
        'lekin',
        'magar',
        'aur',
        'ya',
    ];

    private readonly bool $enabled;

    private readonly int $maxResults;

    private readonly int $triggerGap;

    /**
     * @var array<string,array<int,string>>
     */
    private readonly array $triggers;

    /**
     * @param  array<string,mixed>  $settings  dadi.recovery config slice
     */
    public function __construct(array $settings = [])
    {
        $this->enabled = (bool) ($settings['enabled'] ?? true);
        $this->maxResults = max(1, (int) ($settings['max_results'] ?? self::DEFAULT_MAX_RESULTS));
        $this->triggerGap = max(0, (int) ($settings['trigger_gap'] ?? self::DEFAULT_TRIGGER_GAP));

        $raw = $settings['concern_triggers'] ?? [];
        $triggers = [];

        if (is_array($raw)) {
            foreach ($raw as $code => $phrases) {
                if (! is_string($code) || Concern::tryFrom($code) === null || ! is_array($phrases)) {
                    continue;
                }

                foreach ($phrases as $phrase) {
                    if (! is_string($phrase) || trim($phrase) === '') {
                        continue;
                    }

                    $triggers[$code][] = trim($phrase);
                }
            }
        }

        $this->triggers = $triggers;
    }

    /**
     * The canonical concern codes that survive recovery: the AI-proposed
     * concerns that are real enum values, plus any clearly expressed concerns
     * recovered from the customer message — deduplicated, deterministic and
     * bounded.
     *
     * @param  array<int,string>  $proposedConcerns
     * @return array<int,string>
     */
    public function recover(string $message, array $proposedConcerns): array
    {
        $canonical = $this->canonicalProposals($proposedConcerns);

        if (! $this->enabled) {
            return array_slice($canonical, 0, $this->maxResults);
        }

        $text = $this->normalize($message);

        if ($text === '') {
            return array_slice($canonical, 0, $this->maxResults);
        }

        $recovered = [];

        foreach (Concern::cases() as $concern) {
            if (count($canonical) + count($recovered) >= $this->maxResults) {
                break;
            }

            if (in_array($concern->value, $canonical, true)) {
                continue;
            }

            if ($this->textMentionsConcern($text, $concern)) {
                $recovered[] = $concern->value;
            }
        }

        return array_slice(array_merge($canonical, $recovered), 0, $this->maxResults);
    }

    /**
     * Rebuild an immutable ConversationState with the recovered canonical
     * concerns, preserving every other field exactly. Does not touch the
     * database; the existing state-commit flow remains responsible for
     * persistence.
     */
    public function apply(ConversationState $proposedState, string $message): ConversationState
    {
        return new ConversationState(
            section: $proposedState->section,
            concerns: $this->recover($message, $proposedState->concerns),
            attributes: $proposedState->attributes,
            preferences: $proposedState->preferences,
            safetySignals: $proposedState->safetySignals,
            readyForRecommendation: $proposedState->readyForRecommendation,
            memory: $proposedState->memory,
            preferredLanguage: $proposedState->preferredLanguage,
            preferredLanguageName: $proposedState->preferredLanguageName,
        );
    }

    /**
     * @param  array<int,string>  $proposed
     * @return array<int,string>
     */
    private function canonicalProposals(array $proposed): array
    {
        $canonical = [];

        foreach ($proposed as $code) {
            if (! is_string($code) || trim($code) === '' || in_array($code, $canonical, true)) {
                continue;
            }

            $code = trim($code);

            if (Concern::tryFrom($code) !== null) {
                $canonical[] = $code;
            }
        }

        return $canonical;
    }

    private function textMentionsConcern(string $text, Concern $concern): bool
    {
        foreach ($this->triggers[$concern->value] ?? [] as $trigger) {
            $anchor = $this->matchTrigger($text, $trigger);

            if ($anchor === null) {
                continue;
            }

            $firstStart = $anchor['first_start'];
            $lastStart = $anchor['last_start'];
            $lastEnd = $anchor['last_end'];

            if ($this->isNegatedBefore($text, $firstStart)) {
                continue;
            }

            if ($firstStart !== $lastStart && $this->isNegatedBefore($text, $lastStart)) {
                continue;
            }

            if ($this->isNegatedAfter($text, $lastEnd)) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * Word-bounded, order-preserving match of a trigger's tokens inside the
     * normalized message. Multi-word triggers allow at most $triggerGap filler
     * tokens between consecutive tokens and never span a clause wall (so
     * "baal bahut rough" matches "baal rough" but "baal, aur baad mein rough"
     * does not).
     *
     * Returns the char offsets of the first and last matched token — the
     * negiation anchors — or null.
     *
     * @return array{first_start:int,last_start:int,last_end:int}|null
     */
    private function matchTrigger(string $text, string $trigger): ?array
    {
        $tokens = preg_split('/\s+/u', trim($trigger));

        if ($tokens === false || $tokens === []) {
            return null;
        }

        $positionSets = [];

        foreach ($tokens as $token) {
            $matches = [];
            preg_match_all('/\b'.preg_quote($token, '/').'\b/u', $text, $matches, PREG_OFFSET_CAPTURE);

            $positions = [];

            foreach ($matches[0] ?? [] as [$word, $offset]) {
                $offset = (int) $offset;
                $positions[] = [$offset, $offset + strlen($word)];
            }

            if ($positions === []) {
                return null;
            }

            $positionSets[] = $positions;
        }

        if (count($positionSets) === 1) {
            return [
                'first_start' => $positionSets[0][0][0],
                'last_start' => $positionSets[0][0][0],
                'last_end' => $positionSets[0][0][1],
            ];
        }

        foreach ($positionSets[0] as $first) {
            $chain = [$first];
            $complete = true;

            for ($i = 1, $count = count($positionSets); $i < $count; $i++) {
                $previous = $chain[$i - 1];
                $next = null;

                foreach ($positionSets[$i] as $candidate) {
                    if ($candidate[0] <= $previous[1]) {
                        continue;
                    }

                    if ($this->gapAllowed($text, $previous[1], $candidate[0])) {
                        $next = $candidate;
                        break;
                    }
                }

                if ($next === null) {
                    $complete = false;
                    break;
                }

                $chain[] = $next;
            }

            if ($complete) {
                $last = $chain[count($chain) - 1];

                return [
                    'first_start' => $chain[0][0],
                    'last_start' => $last[0],
                    'last_end' => $last[1],
                ];
            }
        }

        return null;
    }

    /**
     * The slice between two matched tokens may contain at most $triggerGap
     * filler tokens and no clause wall.
     */
    private function gapAllowed(string $text, int $from, int $to): bool
    {
        if ($to <= $from) {
            return false;
        }

        $slice = trim(substr($text, $from, $to - $from));

        if ($slice === '') {
            return true;
        }

        $tokens = preg_split('/\s+/u', $slice);

        if ($tokens === false || count($tokens) > $this->triggerGap) {
            return false;
        }

        foreach ($tokens as $token) {
            if (in_array(mb_strtolower($token), self::CLAUSE_WALLS, true)) {
                return false;
            }
        }

        return true;
    }

    private function isNegatedBefore(string $text, int $offset): bool
    {
        $before = trim(substr($text, 0, $offset));

        if ($before === '') {
            return false;
        }

        $tokens = preg_split('/\s+/u', $before);

        if ($tokens === false) {
            return false;
        }

        // Scan towards the trigger (right-to-left): the closest word decides
        // first. A negation cancels; function words are bridges the negation
        // may have reached through; anything else (content or clause wall)
        // stops the scan so earlier negations never leak in.
        foreach (array_reverse(array_slice($tokens, -self::LOOKBACK_TOKENS)) as $token) {
            $token = mb_strtolower($token);

            if (preg_match('/[.,;:!?()]$/u', $token)) {
                return false;
            }

            if (in_array($token, self::NEGATION_LEADS, true)) {
                return true;
            }

            if (! in_array($token, self::BRIDGE_WORDS, true)) {
                return false;
            }
        }

        return false;
    }

    private function isNegatedAfter(string $text, int $end): bool
    {
        $after = trim(substr($text, $end));

        if ($after === '') {
            return false;
        }

        $tokens = preg_split('/\s+/u', $after);

        if ($tokens === false || $tokens === []) {
            return false;
        }

        return in_array(mb_strtolower($tokens[0]), self::TRAILING_NEGATION_LEADS, true);
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower($value);

        // Keep contractions and hyphenated words as single tokens so negation
        // is preserved ("don't" -> "dont", "non-sticky" -> "nonsticky") instead
        // of being split into separate words.
        $value = preg_replace("/['\x{2018}\x{2019}\x{201B}-]+/u", '', $value) ?? $value;

        $value = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value) ?? $value;

        return trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
    }
}
