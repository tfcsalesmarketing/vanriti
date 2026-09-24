<?php

namespace Tests\Unit\Dadi;

use App\Dadi\Ai\Prompts\DadiPromptBuilder;
use Tests\TestCase;

class DadiVocabularyConfigTest extends TestCase
{
    public function test_preference_and_avoidance_lexicons_are_non_empty(): void
    {
        self::assertNotEmpty(config('dadi.recommendation.preferences'));
        self::assertNotEmpty(config('dadi.recommendation.avoidances'));
    }

    public function test_keys_are_unique_across_preferences_and_avoidances(): void
    {
        $keys = [
            ...array_keys(config('dadi.recommendation.preferences')),
            ...array_keys(config('dadi.recommendation.avoidances')),
        ];

        self::assertSame($keys, array_values(array_unique($keys)));
    }

    public function test_keys_are_lowercase_snake_case(): void
    {
        foreach ([
            ...array_keys(config('dadi.recommendation.preferences')),
            ...array_keys(config('dadi.recommendation.avoidances')),
        ] as $key) {
            self::assertMatchesRegularExpression('/^[a-z][a-z0-9_]*$/', $key, "Key '{$key}' must be lowercase snake_case.");
        }
    }

    public function test_lexicons_are_bounded(): void
    {
        self::assertLessThanOrEqual(10, count(config('dadi.recommendation.preferences')));
        self::assertLessThanOrEqual(10, count(config('dadi.recommendation.avoidances')));
    }

    public function test_every_token_list_is_a_non_empty_unique_list_of_non_empty_strings(): void
    {
        foreach ([
            config('dadi.recommendation.preferences'),
            config('dadi.recommendation.avoidances'),
        ] as $lexicon) {
            foreach ($lexicon as $key => $tokens) {
                self::assertIsArray($tokens, "Tokens for '{$key}' must be an array.");
                self::assertNotSame([], $tokens, "Tokens for '{$key}' must not be empty.");
                self::assertSame($tokens, array_values(array_unique($tokens)), "Tokens for '{$key}' must be unique.");

                foreach ($tokens as $token) {
                    $token = (string) $token;
                    self::assertSame(trim($token), $token, "Token '{$token}' for '{$key}' must not be blank.");
                    self::assertNotSame('', $token);
                }
            }
        }
    }

    public function test_every_key_appears_in_the_prompt_vocabulary(): void
    {
        $vocabulary = (new DadiPromptBuilder)->vocabulary();

        foreach ([
            ...array_keys(config('dadi.recommendation.preferences')),
            ...array_keys(config('dadi.recommendation.avoidances')),
        ] as $key) {
            self::assertStringContainsString($key, $vocabulary, "Key '{$key}' must be rendered in the prompt vocabulary.");
        }
    }

    public function test_preference_and_avoidance_hints_are_bounded_and_keyed_by_real_keys(): void
    {
        $preferenceKeys = array_keys(config('dadi.recommendation.preferences'));
        $avoidanceKeys = array_keys(config('dadi.recommendation.avoidances'));

        foreach ([
            'preference_hints' => $preferenceKeys,
            'avoidance_hints' => $avoidanceKeys,
        ] as $bucket => $expectedKeys) {
            $hints = config('dadi.ai.lexicon.'.$bucket);

            self::assertIsArray($hints, "'{$bucket}' must be a config array.");
            self::assertSame(
                $expectedKeys,
                array_keys($hints),
                "'{$bucket}' keys must mirror the exact preference/avoidance keys and order.",
            );

            foreach ($hints as $key => $phrases) {
                self::assertIsArray($phrases, "Hints for '{$key}' must be an array.");
                self::assertLessThanOrEqual(3, count($phrases), "Hints for '{$key}' must stay bounded to 3.");

                foreach ($phrases as $phrase) {
                    self::assertNotSame('', trim((string) $phrase));
                }
            }
        }
    }

    public function test_explanation_preference_labels_mirror_the_preference_lexicon_keys(): void
    {
        $explanation = config('dadi.recommendation.explanation');

        self::assertIsArray($explanation, "'dadi.recommendation.explanation' must be a config array.");
        self::assertSame(
            array_keys(config('dadi.recommendation.preferences')),
            array_keys($explanation['preference_labels']),
            'Explanation preference labels must mirror the exact preference keys and order.',
        );

        foreach ($explanation['preference_labels'] as $key => $label) {
            $label = (string) $label;
            self::assertSame(trim($label), $label, "Label '{$label}' for '{$key}' must not be blank.");
            self::assertNotSame('', $label);
            self::assertLessThanOrEqual(30, mb_strlen($label), "Label for '{$key}' must be a short trait noun.");
            self::assertDoesNotMatchRegularExpression(
                '/[<>{}]/',
                $label,
                "Label for '{$key}' must be plain text without markup.",
            );
        }
    }

    public function test_explanation_bounds_are_positive_integers(): void
    {
        $explanation = config('dadi.recommendation.explanation');

        foreach (['max_length', 'max_concern_labels', 'max_preference_labels'] as $bound) {
            self::assertGreaterThan(0, (int) $explanation[$bound], "'{$bound}' must be a positive integer.");
        }
    }
}
