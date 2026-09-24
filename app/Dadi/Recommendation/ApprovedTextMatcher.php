<?php

namespace App\Dadi\Recommendation;

/**
 * Deterministic, negation-aware trait scanner over human-approved product
 * intelligence text.
 *
 * The engine is only allowed to act on traits the approved intelligence
 * positively asserts — it may never guess an absence. This matcher therefore
 * performs an exact, word-bounded token scan of the approved free-text fields
 * (positioning, benefits, usage, precautions, suitability) and treats a trait
 * as asserted ONLY when it appears and is not negated by a leading "no",
 * "not", "never", "non", "without", "free" / "free of"/"free from", a
 * contraction ("doesn't feel greasy"), a trailing "free"/"none", or the
 * postposed Hinglish negator "nahi" ("paraben nahi", "paraben se problem nahi").
 *
 * Examples:
 *   "fragrance free formula"      -> "fragrance" NOT asserted (trailing "free")
 *   "with fragrance"              -> "fragrance" asserted
 *   "very lightweight feel"       -> "lightweight" asserted
 *   "that is not a sticky finish" -> "sticky" NOT asserted (leading "not")
 *   "paraben nahi added"          -> "paraben" NOT asserted (postposed "nahi")
 */
final class ApprovedTextMatcher
{
    /**
     * Single-word negation leads that can precede a trait phrase. Contraction
     * forms keep their apostrophe after normalization ("doesn't feel greasy").
     */
    private const NEGATION_LEADS = [
        'no',
        'not',
        'never',
        'non',
        'none',
        'without',
        'free',
        "don't",
        "doesn't",
        "didn't",
    ];

    /**
     * Words that negate a trait when they immediately follow it, plus the
     * postposed Hinglish negator ("paraben nahi", "paraben nahi chahiye").
     */
    private const TRAILING_NEGATION_LEADS = [
        'free',
        'none',
        'nahi',
    ];

    /**
     * Function words a negation may reach through after the trait in Hinglish
     * postposed order ("fragrance se problem nahi", "chikna bilkul nahi").
     */
    private const TRAILING_BRIDGE_WORDS = [
        'hai',
        'hi',
        'bhi',
        'chahiye',
        'hota',
        'hoti',
        'honge',
        'se',
        'ko',
        'mein',
        'me',
        'ka',
        'ki',
        'ke',
        'problem',
        'bilkul',
    ];

    /**
     * How far backwards we scan for a negation lead (covers "not a sticky").
     */
    private const LOOKBACK_TOKENS = 3;

    /**
     * Function words a negation may reach through before the trait ("not A
     * sticky", "no added fragrance"). Content words and punctuation are walls:
     * a negation in a previous clause ("this is non greasy, lightweight") must
     * never leak onto an unrelated trait. Sensory perception verbs let a
     * contraction reach the trait ("doesn't feel greasy", "never get sticky").
     */
    private const BRIDGE_WORDS = [
        'a',
        'an',
        'the',
        'at',
        'of',
        'from',
        'any',
        'too',
        'very',
        'so',
        'with',
        'and',
        'also',
        'feel',
        'smell',
        'look',
        'get',
        'become',
        'becomes',
        'turn',
        'turns',
        'leave',
        'leaves',
        'use',
    ];

    public function asserts(string $text, string $trait): bool
    {
        $needle = $this->normalize($trait);

        if ($needle === '') {
            return false;
        }

        $haystack = $this->normalize($text);

        if ($haystack === '') {
            return false;
        }

        $matches = [];

        preg_match_all(
            '/\b'.preg_quote($needle, '/').'\b/u',
            $haystack,
            $matches,
            PREG_OFFSET_CAPTURE,
        );

        foreach ($matches[0] ?? [] as $occurrence) {
            [$word, $offset] = $occurrence;
            $offset = (int) $offset;
            $end = $offset + strlen($word);

            if ($this->isNegatedBefore($haystack, $offset)) {
                continue;
            }

            if ($this->isNegatedAfter($haystack, $end)) {
                continue;
            }

            return true;
        }

        return false;
    }

    private function isNegatedBefore(string $text, int $offset): bool
    {
        $before = trim(substr($text, 0, $offset));

        if ($before === '') {
            return false;
        }

        $tokens = array_slice(preg_split('/\s+/u', $before) ?: [], -self::LOOKBACK_TOKENS);

        if ($this->reachesFreeOfFrom($tokens)) {
            return true;
        }

        foreach (array_reverse($tokens) as $token) {
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

    /**
     * "free of X" / "free from X" reaches through a single content word (e.g.
     * "free of artificial fragrance") even though the content word would
     * normally wall off the scan.
     *
     * @param  array<int,string>  $tokens
     */
    private function reachesFreeOfFrom(array $tokens): bool
    {
        $lower = array_map('mb_strtolower', $tokens);

        if (! in_array('free', $lower, true)) {
            return false;
        }

        return in_array('of', $lower, true) || in_array('from', $lower, true);
    }

    private function isNegatedAfter(string $text, int $end): bool
    {
        $after = trim(substr($text, $end));

        if ($after === '') {
            return false;
        }

        $tokens = array_slice(preg_split('/\s+/u', $after) ?: [], 0, self::LOOKBACK_TOKENS);

        foreach ($tokens as $raw) {
            $token = rtrim(mb_strtolower($raw), '.,;:!?()');

            if ($token === '') {
                return false;
            }

            if (in_array($token, self::TRAILING_NEGATION_LEADS, true)) {
                return true;
            }

            if (! in_array($token, self::TRAILING_BRIDGE_WORDS, true)) {
                return false;
            }
        }

        return false;
    }

    private function normalize(string $value): string
    {
        return trim(preg_replace('/[\s_-]+/u', ' ', mb_strtolower($value)) ?? mb_strtolower($value));
    }
}
