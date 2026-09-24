<?php

namespace App\Dadi\Validation;

use App\Dadi\Enums\Concern;
use App\Dadi\Enums\Section;
use App\Dadi\Exceptions\InvalidProductProfileException;

/**
 * The single deterministic gate for Dadi product intelligence content.
 *
 * Used both when a human/authority persists a profile and when the immutable
 * ProductProfile value object is built for the AI layer (defence in depth).
 * It enforces:
 *
 *   - a closed section vocabulary (Section enum) and concern vocabulary
 *     (Concern enum) — no arbitrary strings;
 *   - an allowed, fixed set of payload keys — no arbitrary/unbounded JSON;
 *   - bounded counts and character lengths;
 *   - a word-boundary scan that rejects authoritative commerce terms
 *     (sku, price, mrp, stock, selling_price, inventory, discount, coupon,
 *     cart, checkout, order), so a profile can never smuggle commerce truth
 *     into the AI-facing intelligence.
 *
 * Commerce values are read live from the existing catalogue; they are never
 * accepted as intelligence content here.
 */
final class ProductProfileContentRules
{
    public const FORBIDDEN_COMMERCE_TERMS = [
        'sku',
        'price',
        'selling_price',
        'mrp',
        'stock',
        'inventory',
        'discount',
        'coupon',
        'cart',
        'checkout',
        'order',
    ];

    public const DATA_KEYS = [
        'sections',
        'concerns',
        'positioning',
        'approved_benefits',
        'approved_usage_context',
        'approved_precautions',
        'suitability_notes',
    ];

    /**
     * @param  array<mixed>  $value
     * @return array<int,string>
     */
    public static function assertSections(mixed $value): array
    {
        $out = [];

        foreach (self::list($value, 'sections', count(Section::cases())) as $code) {
            if (Section::tryFrom($code) === null) {
                throw new InvalidProductProfileException("Unknown section '{$code}'. Allowed: hair, skin, wellness.");
            }

            $out[] = $code;
        }

        return $out;
    }

    /**
     * @param  array<mixed>  $value
     * @return array<int,string>
     */
    public static function assertConcerns(mixed $value, int $max = 12): array
    {
        $valid = array_map(
            static fn (Concern $concern): string => $concern->value,
            Concern::cases(),
        );

        $out = [];

        foreach (self::list($value, 'concerns', $max) as $code) {
            if (! in_array($code, $valid, true)) {
                throw new InvalidProductProfileException("Unknown concern '{$code}' is not in the approved vocabulary.");
            }

            $out[] = $code;
        }

        return $out;
    }

    public static function assertText(mixed $value, int $max = 1000, string $field = 'positioning'): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_string($value)) {
            throw new InvalidProductProfileException("Field '{$field}' must be text.");
        }

        $text = trim($value);

        if (mb_strlen($text) > $max) {
            throw new InvalidProductProfileException(
                "Field '{$field}' exceeds the {$max} character limit.",
            );
        }

        self::assertNoForbiddenContent($text, $field);

        return $text;
    }

    /**
     * @return array<int,string>
     */
    public static function assertTextList(mixed $value, int $maxCount, int $maxLength, string $field): array
    {
        $out = [];

        foreach (self::list($value, $field, $maxCount) as $item) {
            if (mb_strlen($item) > $maxLength) {
                throw new InvalidProductProfileException(
                    "An entry in '{$field}' exceeds the {$maxLength} character limit.",
                );
            }

            self::assertNoForbiddenContent($item, $field);

            $out[] = $item;
        }

        return $out;
    }

    /**
     * Reject authoritative commerce vocabulary inside intelligence content.
     *
     * Underscores, hyphens and whitespace are normalised to a single space on
     * BOTH sides, so 'selling_price', 'selling price' and 'selling-price' all
     * collapse to the same tokens. Matching stays word-bounded: a term must
     * never be part of a larger word ('carton' never trips 'cart').
     */
    public static function assertNoForbiddenContent(string $value, ?string $field = null): void
    {
        $normalized = self::normalize($value);

        foreach (self::forbiddenVariants() as $term) {
            if (preg_match('/\b'.preg_quote($term, '/').'\b/u', $normalized) === 1) {
                $where = $field !== null ? " in field [{$field}]" : '';

                throw new InvalidProductProfileException(
                    "Product intelligence content must not reference commerce data; forbidden term '{$term}'{$where}.",
                );
            }
        }
    }

    private static function normalize(string $value): string
    {
        return preg_replace('/[\s_-]+/u', ' ', mb_strtolower($value)) ?? mb_strtolower($value);
    }

    /**
     * @return array<int,string>
     */
    private static function forbiddenVariants(): array
    {
        static $variants = null;

        if ($variants === null) {
            $variants = [];

            foreach (self::FORBIDDEN_COMMERCE_TERMS as $term) {
                $variants[] = self::normalize($term);
            }
        }

        return $variants;
    }

    /**
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    public static function assertKnownKeys(array $data): array
    {
        foreach ($data as $key => $value) {
            if (! in_array($key, self::DATA_KEYS, true)) {
                throw new InvalidProductProfileException(
                    "Unexpected product intelligence key '{$key}'. Only ".implode(', ', self::DATA_KEYS).' are allowed.',
                );
            }
        }

        return $data;
    }

    /**
     * @return array<int,string>
     */
    private static function list(mixed $value, string $field, int $capacity): array
    {
        if (! is_array($value)) {
            throw new InvalidProductProfileException("Field '{$field}' must be a list.");
        }

        if (count($value) > $capacity) {
            throw new InvalidProductProfileException(
                "Field '{$field}' allows at most {$capacity} entries.",
            );
        }

        $out = [];

        foreach ($value as $item) {
            if (! is_string($item)) {
                throw new InvalidProductProfileException("Entries in '{$field}' must be strings.");
            }

            $trimmed = trim($item);

            if ($trimmed === '') {
                throw new InvalidProductProfileException("Entries in '{$field}' must not be empty.");
            }

            if (! in_array($trimmed, $out, true)) {
                $out[] = $trimmed;
            }
        }

        return $out;
    }
}
