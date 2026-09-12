<?php

namespace App\Dadi\Language;

use InvalidArgumentException;

/**
 * The customer's validated conversation language preference.
 *
 * A small, immutable, Laravel-owned value object: it turns validated customer
 * input into the codes stored on ConversationState (english | hindi | hinglish
 * | other, plus an optional free-text name for "other"). It is the ONLY source
 * of the prompt-side welcome title and the conversation locale hint.
 *
 * The language name is strictly DATA: it is bounded, trimmed and carries no
 * instructions. It is never interpreted, never faked into fluency ("Marathi"
 * never means Dadi actually speaks Marathi) and never rendered anywhere but the
 * trusted context line of the prompt.
 */
final readonly class DadiLanguage
{
    public const ENGLISH = 'english';

    public const HINDI = 'hindi';

    public const HINGLISH = 'hinglish';

    public const OTHER = 'other';

    public const SUPPORTED = [
        self::ENGLISH,
        self::HINDI,
        self::HINGLISH,
        self::OTHER,
    ];

    public function __construct(
        public string $preferred,
        public ?string $name = null,
    ) {}

    /**
     * Normalize validated input into an immutable language preference.
     *
     * @throws InvalidArgumentException when the code is unsupported or "other"
     *                                  is supplied without a non-empty name
     */
    public static function from(string $preferred, ?string $name = null): self
    {
        $preferred = strtolower(trim($preferred));

        if (! in_array($preferred, self::SUPPORTED, true)) {
            throw new InvalidArgumentException("Unsupported Dadi language [{$preferred}].");
        }

        $name = $name === null ? null : trim($name);

        if ($preferred === self::OTHER && ($name === null || $name === '')) {
            throw new InvalidArgumentException('The "other" language requires a name.');
        }

        if ($preferred !== self::OTHER) {
            $name = null;
        }

        return new self($preferred, $name);
    }

    public function isEnglish(): bool
    {
        return $this->preferred === self::ENGLISH;
    }

    public function isHindi(): bool
    {
        return $this->preferred === self::HINDI;
    }

    public function isHinglish(): bool
    {
        return $this->preferred === self::HINGLISH;
    }

    public function isOther(): bool
    {
        return $this->preferred === self::OTHER;
    }

    /**
     * The conversation locale hint ('hi' only for actual Hindi; everything
     * else stays 'en' because Hinglish and other scripts are customer text,
     * not a separate locale).
     */
    public function localeHint(): string
    {
        return $this->preferred === self::HINDI ? 'hi' : 'en';
    }

    /**
     * The customer-facing label shown inside the onboarding step itself.
     */
    public function displayName(): string
    {
        return match ($this->preferred) {
            self::ENGLISH => 'English',
            self::HINDI => 'हिन्दी',
            self::HINGLISH => 'Hinglish',
            default => 'Other',
        };
    }

    /**
     * The UI-only welcome title for a language preference. It is never
     * persisted as a message; it merely seeds the empty conversation state so
     * the page greets the customer in their chosen language before the first
     * real turn. Unknown codes yield null so the caller falls back to the
     * default welcome copy.
     */
    public static function welcomeTitleFor(string $preferred): ?string
    {
        return match (strtolower(trim($preferred))) {
            self::ENGLISH => "Tell me, what's bothering you?",
            self::HINDI => 'बताओ, क्या परेशानी हो रही है?',
            self::HINGLISH => 'Batao, kya pareshaan kar raha hai?',
            // Deliberately neutral: no guaranteed fluency in arbitrary
            // languages, so "Other" gets a gentle, language-neutral nudge.
            self::OTHER => "Baat karein · Let's talk",
            default => null,
        };
    }
}
