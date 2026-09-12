<?php

namespace Tests\Unit\Dadi;

use App\Dadi\Ai\Validation\AiOutputValidator;
use App\Dadi\Enums\ConversationIntent;
use App\Dadi\Enums\Section;
use App\Dadi\Exceptions\InvalidAiOutputException;
use App\Dadi\ValueObjects\AiResponse;
use App\Dadi\ValueObjects\ConversationState;
use PHPUnit\Framework\TestCase;

class AiOutputValidatorTest extends TestCase
{
    private function validator(array $overrides = []): AiOutputValidator
    {
        return new AiOutputValidator(
            maxReplyLength: $overrides['max_reply_length'] ?? 600,
            maxConcerns: $overrides['max_concerns'] ?? 12,
            maxAttributes: $overrides['max_attributes'] ?? 25,
            maxPreferences: $overrides['max_preferences'] ?? 25,
            maxMemoryItems: $overrides['max_memory_items'] ?? 20,
        );
    }

    private function response(array $data): AiResponse
    {
        return new AiResponse(
            content: json_encode($data, JSON_THROW_ON_ERROR),
            data: $data,
        );
    }

    public function test_valid_output_produces_a_structured_turn(): void
    {
        $result = $this->validator()->validate(
            $this->response([
                'reply' => 'Arre beta, samajh gayi. Baal ki baat karte hain.',
                'intent' => 'concern',
                'safety_review' => false,
                'understanding' => [
                    'section' => 'hair',
                    'concerns' => ['hair_dryness', 'hair_frizz'],
                    'attributes' => ['scalp_condition' => 'dry'],
                    'preferences' => ['wash_frequency' => '1_2'],
                    'memory' => [
                        [
                            'category' => 'avoidance',
                            'slot' => 'sulphate',
                            'value' => 'avoid',
                            'importance' => 'high',
                        ],
                    ],
                ],
            ]),
            new ConversationState,
            sequence: 7,
        );

        self::assertSame('Arre beta, samajh gayi. Baal ki baat karte hain.', $result->reply);
        self::assertSame(ConversationIntent::Concern, $result->intent);
        self::assertFalse($result->requiresSafetyReview);
        self::assertNotNull($result->proposedState);
        self::assertSame(Section::Hair, $result->proposedState->section);
        self::assertSame(['hair_dryness', 'hair_frizz'], $result->proposedState->concerns);
        self::assertSame('dry', $result->proposedState->attribute('scalp_condition'));
        self::assertSame('1_2', $result->proposedState->preference('wash_frequency'));

        $memory = $result->proposedState->memoryItem('avoidance', 'sulphate');
        self::assertNotNull($memory);
        self::assertSame('avoid', $memory->value);
        self::assertSame('high', $memory->importance);
        self::assertSame(7, $memory->updatedSequence);
    }

    public function test_existing_state_is_preserved_and_corrections_supersede(): void
    {
        $current = (new ConversationState)
            ->withSection(Section::Skin)
            ->withConcern('hair_fall')
            ->withAttribute('scalp_condition', 'dry')
            ->remember('topic', 'skin', 'discussed', 3);

        $result = $this->validator()->validate(
            $this->response([
                'reply' => 'Theek hai.',
                'understanding' => [
                    'section' => 'hair',
                    'concerns' => ['hair_dryness'],
                    'attributes' => ['scalp_condition' => 'oily'],
                    'memory' => [['category' => 'topic', 'slot' => 'hair', 'value' => 'discussed']],
                ],
            ]),
            $current,
            sequence: 9,
        );

        $proposed = $result->proposedState;

        self::assertSame(Section::Hair, $proposed->section);
        self::assertSame(['hair_fall', 'hair_dryness'], $proposed->concerns);
        self::assertSame('oily', $proposed->attribute('scalp_condition'));
        self::assertSame('discussed', $proposed->memoryItem('topic', 'skin')->value);
        self::assertSame('discussed', $proposed->memoryItem('topic', 'hair')->value);
        self::assertSame(3, $proposed->memoryItem('topic', 'skin')->sourceSequence);
    }

    public function test_preference_keys_pass_through_validation_unchanged(): void
    {
        $result = $this->validator()->validate(
            $this->response([
                'reply' => 'Samajh gayi.',
                'understanding' => [
                    'preferences' => ['herbal' => true, 'gentle' => true],
                ],
            ]),
            new ConversationState,
        );

        self::assertSame(['herbal' => true, 'gentle' => true], $result->proposedState->preferences);
    }

    public function test_invalid_enum_values_fall_back_without_crashing(): void
    {
        $result = $this->validator()->validate(
            $this->response([
                'reply' => 'Samajh gayi.',
                'intent' => 'banana',
                'understanding' => [
                    'section' => 'face',
                    'concerns' => 'not-a-list',
                ],
            ]),
            new ConversationState,
        );

        self::assertSame(ConversationIntent::Unclear, $result->intent);
        self::assertNull($result->proposedState->section);
        self::assertSame([], $result->proposedState->concerns);
    }

    public function test_malformed_json_is_rejected(): void
    {
        $this->expectException(InvalidAiOutputException::class);

        $this->validator()->validate(
            new AiResponse(content: 'not json at all', data: null),
            new ConversationState,
        );
    }

    public function test_missing_reply_is_rejected(): void
    {
        $this->expectException(InvalidAiOutputException::class);

        $this->validator()->validate(
            $this->response(['understanding' => ['section' => 'hair']]),
            new ConversationState,
        );
    }

    public function test_oversized_reply_is_rejected(): void
    {
        $this->expectException(InvalidAiOutputException::class);

        $this->validator()->validate(
            $this->response(['reply' => str_repeat('a', 601)]),
            new ConversationState,
        );
    }

    public function test_forbidden_product_field_is_rejected(): void
    {
        foreach ([
            ['sku' => 'VANRITI-XYZ'],
            ['understanding' => ['product_id' => 17]],
            ['understanding' => ['attributes' => ['price' => 99]]],
            ['understanding' => ['memory' => [['category' => 'note', 'slot' => 's', 'value' => 'x', 'recommended_sku' => 'Z']]]],
        ] as $payload) {
            try {
                $this->validator()->validate(
                    $this->response($payload + ['reply' => 'ok']),
                    new ConversationState,
                );
                self::fail('Expected InvalidAiOutputException for '.json_encode($payload, JSON_THROW_ON_ERROR).'.');
            } catch (InvalidAiOutputException) {
                self::assertTrue(true);
            }
        }
    }

    public function test_unknown_memory_category_is_skipped(): void
    {
        $result = $this->validator()->validate(
            $this->response([
                'reply' => 'Shukriya.',
                'understanding' => [
                    'memory' => [
                        ['category' => 'shopping', 'slot' => 'budget', 'value' => 'low'],
                        ['category' => 'note', 'slot' => 'hello', 'value' => 'namaste'],
                    ],
                ],
            ]),
            new ConversationState,
            sequence: 2,
        );

        self::assertNull($result->proposedState->memoryItem('shopping', 'budget'));
        self::assertSame('namaste', $result->proposedState->memoryItem('note', 'hello')->value);
    }

    public function test_memory_is_bounded_by_configuration(): void
    {
        $validator = $this->validator(['max_memory_items' => 2]);

        $result = $validator->validate(
            $this->response([
                'reply' => 'Hmm.',
                'understanding' => [
                    'memory' => [
                        ['category' => 'note', 'slot' => 'a', 'value' => '1'],
                        ['category' => 'note', 'slot' => 'b', 'value' => '2'],
                        ['category' => 'note', 'slot' => 'c', 'value' => '3'],
                    ],
                ],
            ]),
            new ConversationState,
            sequence: 4,
        );

        self::assertSame(2, $result->proposedState->memoryCount());
        self::assertNull($result->proposedState->memoryItem('note', 'c'));
    }

    public function test_safety_flag_is_carried_as_a_proposal(): void
    {
        $result = $this->validator()->validate(
            $this->response([
                'reply' => 'Beta, yeh serious lag raha hai.',
                'safety_review' => true,
            ]),
            new ConversationState,
        );

        self::assertTrue($result->requiresSafetyReview);
        self::assertSame(ConversationIntent::Unclear, $result->intent);
    }

    public function test_corrected_concerns_are_removed_from_the_proposed_state(): void
    {
        $current = (new ConversationState)->withConcern('hair_dryness')->withConcern('hair_frizz');

        $result = $this->validator()->validate(
            $this->response([
                'reply' => 'Theek hai.',
                'understanding' => [
                    'concerns' => ['hair_frizz'],
                    'corrected' => ['concerns' => ['hair_dryness']],
                ],
            ]),
            $current,
            sequence: 5,
        );

        $state = $result->proposedState;

        self::assertSame(['hair_frizz'], $state->concerns);
        self::assertFalse($state->hasConcern('hair_dryness'));
    }

    public function test_corrected_preferences_and_attributes_are_removed(): void
    {
        $current = (new ConversationState)
            ->withAttribute('scalp_condition', 'dry')
            ->withPreference('herbal', true)
            ->withPreference('gentle', true);

        $result = $this->validator()->validate(
            $this->response([
                'reply' => 'OK.',
                'understanding' => [
                    'corrected' => [
                        'preferences' => ['herbal'],
                        'attributes' => ['scalp_condition'],
                    ],
                ],
            ]),
            $current,
            sequence: 6,
        );

        $state = $result->proposedState;

        self::assertNull($state->attribute('scalp_condition'));
        self::assertNull($state->preference('herbal'));
        self::assertSame(true, $state->preference('gentle'));
    }

    public function test_corrected_memory_retires_active_memory_slots(): void
    {
        $current = (new ConversationState)
            ->remember('preference', 'fragrance_free', 'yes', 2)
            ->remember('concern', 'hair_dryness', 'severe', 3);

        $result = $this->validator()->validate(
            $this->response([
                'reply' => 'OK.',
                'understanding' => [
                    'corrected' => [
                        'memory' => [
                            ['category' => 'preference', 'slot' => 'fragrance_free'],
                            ['category' => 'concern', 'slot' => 'hair_dryness'],
                        ],
                    ],
                ],
            ]),
            $current,
            sequence: 8,
        );

        $state = $result->proposedState;

        $preference = $state->memoryItem('preference', 'fragrance_free');
        $concern = $state->memoryItem('concern', 'hair_dryness');

        self::assertNotNull($preference);
        self::assertFalse($preference->active);
        self::assertNotNull($concern);
        self::assertFalse($concern->active);
        self::assertSame([], $state->memoryActive());
    }

    public function test_re_asserted_concern_in_the_same_turn_loses_to_the_explicit_correction(): void
    {
        $current = (new ConversationState)->withConcern('hair_dryness');

        $result = $this->validator()->validate(
            $this->response([
                'reply' => 'OK.',
                'understanding' => [
                    'concerns' => ['hair_dryness', 'hair_frizz'],
                    'corrected' => ['concerns' => ['hair_dryness']],
                ],
            ]),
            $current,
            sequence: 4,
        );

        self::assertSame(['hair_frizz'], $result->proposedState->concerns);
        self::assertFalse($result->proposedState->hasConcern('hair_dryness'));
    }

    public function test_corrected_non_canonical_concerns_are_ignored_without_crashing(): void
    {
        $result = $this->validator()->validate(
            $this->response([
                'reply' => 'Theek hai.',
                'understanding' => [
                    'corrected' => ['concerns' => ['not_real_concern', 'hair_dryness']],
                ],
            ]),
            (new ConversationState)->withConcern('hair_dryness'),
            sequence: 3,
        );

        self::assertSame([], $result->proposedState->concerns);
    }

    public function test_corrected_memory_with_unknown_category_is_ignored(): void
    {
        $result = $this->validator()->validate(
            $this->response([
                'reply' => 'OK.',
                'understanding' => [
                    'corrected' => [
                        'memory' => [
                            ['category' => 'shopping', 'slot' => 'budget'],
                            ['category' => 'topic', 'slot' => 'hair'],
                        ],
                    ],
                ],
            ]),
            (new ConversationState)->remember('topic', 'hair', 'discussed', 2),
            sequence: 7,
        );

        $topic = $result->proposedState->memoryItem('topic', 'hair');

        self::assertNotNull($topic);
        self::assertFalse($topic->active);
        self::assertSame([], $result->proposedState->memoryActive());
    }

    public function test_commerce_terms_in_memory_slots_are_dropped(): void
    {
        foreach (['sku', 'product_id', 'cart', 'checkout', 'order', 'payment', 'price', 'stock'] as $term) {
            $result = $this->validator()->validate(
                $this->response([
                    'reply' => 'OK.',
                    'understanding' => [
                        'memory' => [['category' => 'note', 'slot' => $term, 'value' => 'x']],
                    ],
                ]),
                new ConversationState,
                sequence: 2,
            );

            self::assertNull($result->proposedState->memoryItem('note', $term), "Memory slot '{$term}' must not be stored.");
        }
    }

    public function test_commerce_terms_in_memory_values_are_dropped(): void
    {
        foreach (['price ₹1', 'SKU ABC123', 'cart checkout', 'order placed', 'payment done'] as $value) {
            $result = $this->validator()->validate(
                $this->response([
                    'reply' => 'OK.',
                    'understanding' => [
                        'memory' => [['category' => 'note', 'slot' => 'notes', 'value' => $value]],
                    ],
                ]),
                new ConversationState,
                sequence: 3,
            );

            self::assertNull($result->proposedState->memoryItem('note', 'notes'), "Memory value '{$value}' must not be stored.");
        }
    }

    public function test_legitimate_conversational_memory_still_survives_the_commerce_gate(): void
    {
        $result = $this->validator()->validate(
            $this->response([
                'reply' => 'Samajh gayi.',
                'understanding' => [
                    'memory' => [
                        ['category' => 'avoidance', 'slot' => 'sulphate', 'value' => 'avoid'],
                        ['category' => 'preference', 'slot' => 'herbal', 'value' => 'yes'],
                        ['category' => 'topic', 'slot' => 'hair', 'value' => 'discussed'],
                    ],
                ],
            ]),
            new ConversationState,
            sequence: 2,
        );

        self::assertSame(3, $result->proposedState->memoryCount());
        self::assertSame('avoid', $result->proposedState->memoryItem('avoidance', 'sulphate')->value);
        self::assertSame('yes', $result->proposedState->memoryItem('preference', 'herbal')->value);
    }

    public function test_ai_cannot_overwrite_the_preferred_language_via_attributes(): void
    {
        $current = (new ConversationState)->withPreferredLanguage('hindi');

        $result = $this->validator()->validate(
            $this->response([
                'reply' => 'OK.',
                'understanding' => [
                    'attributes' => ['preferred_language' => 'french', 'scalp_condition' => 'dry'],
                ],
            ]),
            $current,
            sequence: 2,
        );

        $state = $result->proposedState;

        self::assertSame('hindi', $state->preferredLanguage);
        self::assertNull($state->preferredLanguageName);
        self::assertArrayNotHasKey('preferred_language', $state->attributes);
        self::assertSame('dry', $state->attribute('scalp_condition'));
    }

    public function test_ai_cannot_overwrite_the_preferred_language_via_preferences(): void
    {
        $current = (new ConversationState)->withPreferredLanguage('other', 'Marathi');

        $result = $this->validator()->validate(
            $this->response([
                'reply' => 'OK.',
                'understanding' => [
                    'preferences' => ['preferred_language' => 'english', 'preferred_language_name' => 'x', 'herbal' => true],
                ],
            ]),
            $current,
            sequence: 3,
        );

        $state = $result->proposedState;

        self::assertSame('other', $state->preferredLanguage);
        self::assertSame('Marathi', $state->preferredLanguageName);
        self::assertArrayNotHasKey('preferred_language', $state->preferences);
        self::assertSame(true, $state->preference('herbal'));
    }

    public function test_corrected_attributes_cannot_retire_the_preferred_language(): void
    {
        $current = (new ConversationState)->withPreferredLanguage('hinglish');

        $result = $this->validator()->validate(
            $this->response([
                'reply' => 'OK.',
                'understanding' => [
                    'corrected' => ['attributes' => ['preferred_language', 'preferred_language_name']],
                ],
            ]),
            $current,
            sequence: 4,
        );

        $state = $result->proposedState;

        self::assertSame('hinglish', $state->preferredLanguage);
    }

    public function test_reserved_state_key_guard_is_case_insensitive(): void
    {
        $result = $this->validator()->validate(
            $this->response([
                'reply' => 'OK.',
                'understanding' => [
                    'attributes' => ['PREFERRED_LANGUAGE' => 'french'],
                ],
            ]),
            (new ConversationState)->withPreferredLanguage('hindi'),
            sequence: 5,
        );

        self::assertSame('hindi', $result->proposedState->preferredLanguage);
        self::assertSame([], $result->proposedState->attributes);
    }
}
