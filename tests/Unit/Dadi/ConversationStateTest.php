<?php

namespace Tests\Unit\Dadi;

use App\Dadi\Enums\Section;
use App\Dadi\Exceptions\InvalidConversationStateException;
use App\Dadi\ValueObjects\ConversationState;
use PHPUnit\Framework\TestCase;

class ConversationStateTest extends TestCase
{
    public function test_empty_state_defaults_are_sane(): void
    {
        $state = new ConversationState;

        self::assertNull($state->section);
        self::assertSame([], $state->concerns);
        self::assertSame([], $state->attributes);
        self::assertSame([], $state->preferences);
        self::assertSame([], $state->safetySignals);
        self::assertFalse($state->readyForRecommendation);
        self::assertTrue($state->isEmpty());
    }

    public function test_round_trips_through_array(): void
    {
        $state = ConversationState::fromArray([
            'section' => 'hair',
            'concerns' => ['hair_dryness'],
            'attributes' => ['scalp_condition' => 'dry'],
            'preferences' => ['wash_frequency' => '1_2'],
            'safety_signals' => ['recent_surgery'],
            'ready_for_recommendation' => true,
        ]);

        self::assertSame(Section::Hair, $state->section);
        self::assertSame('dry', $state->attribute('scalp_condition'));
        self::assertSame('1_2', $state->preference('wash_frequency'));

        self::assertSame([
            'section' => 'hair',
            'concerns' => ['hair_dryness'],
            'attributes' => ['scalp_condition' => 'dry'],
            'preferences' => ['wash_frequency' => '1_2'],
            'safety_signals' => ['recent_surgery'],
            'ready_for_recommendation' => true,
            'memory' => [],
        ], $state->toArray());
    }

    public function test_unknown_section_is_rejected(): void
    {
        $this->expectException(InvalidConversationStateException::class);

        ConversationState::fromArray(['section' => 'face']);
    }

    public function test_with_methods_return_new_instances_and_never_mutate(): void
    {
        $state = (new ConversationState)->withSection(Section::Skin);
        $rephrased = $state->withAttribute('scalp_condition', 'oily');

        self::assertNotSame($state, $rephrased);
        self::assertSame(Section::Skin, $state->section);
        self::assertNull($state->attribute('scalp_condition'));
        self::assertSame('oily', $rephrased->attribute('scalp_condition'));
    }

    public function test_rephrased_attribute_overwrites_instead_of_appending(): void
    {
        $state = (new ConversationState)->withAttribute('scalp_condition', 'dry');
        $state = $state->withAttribute('scalp_condition', 'oily');

        self::assertSame(['scalp_condition' => 'oily'], $state->attributes);
    }

    public function test_concerns_and_safety_signals_are_deduplicated(): void
    {
        $state = (new ConversationState)
            ->withConcern('hair_dryness')
            ->withConcern('hair_dryness')
            ->withSafetySignal('recent_surgery');

        self::assertSame(['hair_dryness'], $state->concerns);
        self::assertTrue($state->hasConcern('hair_dryness'));
        self::assertFalse($state->hasConcern('hair_fall'));
    }

    public function test_merge_combines_facts_and_later_values_win(): void
    {
        $earlier = ConversationState::fromArray([
            'section' => 'hair',
            'concerns' => ['hair_dryness'],
            'attributes' => ['scalp_condition' => 'dry'],
            'safety_signals' => ['recent_surgery'],
        ]);

        $later = ConversationState::fromArray([
            'concerns' => ['hair_fall'],
            'attributes' => ['scalp_condition' => 'oily'],
        ]);

        $merged = $earlier->merge($later);

        self::assertSame(Section::Hair, $merged->section);
        self::assertSame(['hair_dryness', 'hair_fall'], $merged->concerns);
        self::assertSame(['scalp_condition' => 'oily'], $merged->attributes);
        self::assertSame(['recent_surgery'], $merged->safetySignals);
    }

    public function test_readiness_is_only_affirmed_explicitly(): void
    {
        self::assertFalse((new ConversationState)->readyForRecommendation);
        self::assertTrue((new ConversationState)->withReadyForRecommendation(true)->readyForRecommendation);
    }

    public function test_non_scalar_attribute_value_is_rejected(): void
    {
        $this->expectException(InvalidConversationStateException::class);

        (new ConversationState)->withAttribute('scalp_condition', ['dry', 'oily']);
    }

    public function test_without_concern_removes_only_that_code(): void
    {
        $state = (new ConversationState)
            ->withConcern('hair_dryness')
            ->withConcern('hair_frizz')
            ->withConcern('hair_fall');

        $trimmed = $state->withoutConcern('hair_frizz');

        self::assertSame(['hair_dryness', 'hair_fall'], $trimmed->concerns);
        self::assertTrue($trimmed->hasConcern('hair_dryness'));
        self::assertFalse($trimmed->hasConcern('hair_frizz'));
    }

    public function test_without_concern_is_a_no_op_for_an_unknown_code(): void
    {
        $state = (new ConversationState)->withConcern('hair_dryness');

        self::assertSame(['hair_dryness'], $state->withoutConcern('skin_acne')->concerns);
    }

    public function test_without_attribute_removes_only_that_key(): void
    {
        $state = (new ConversationState)
            ->withAttribute('scalp_condition', 'dry')
            ->withAttribute('hair_type', 'wavy');

        $trimmed = $state->withoutAttribute('scalp_condition');

        self::assertNull($trimmed->attribute('scalp_condition'));
        self::assertSame('wavy', $trimmed->attribute('hair_type'));
    }

    public function test_without_attribute_is_a_no_op_for_an_unknown_key(): void
    {
        $state = (new ConversationState)->withAttribute('scalp_condition', 'dry');

        self::assertSame('dry', $state->withoutAttribute('missing')->attribute('scalp_condition'));
    }

    public function test_without_preference_removes_only_that_key(): void
    {
        $state = (new ConversationState)
            ->withPreference('herbal', true)
            ->withPreference('gentle', true);

        $trimmed = $state->withoutPreference('herbal');

        self::assertNull($trimmed->preference('herbal'));
        self::assertSame(true, $trimmed->preference('gentle'));
    }

    public function test_without_preference_is_a_no_op_for_an_unknown_key(): void
    {
        $state = (new ConversationState)->withPreference('herbal', true);

        self::assertSame(true, $state->withoutPreference('missing')->preference('herbal'));
    }

    public function test_forget_if_present_deactivates_when_stored(): void
    {
        $state = (new ConversationState)
            ->remember('note', 'topic', 'discussed', 3)
            ->forgetIfPresent('note', 'topic', 6);

        self::assertFalse($state->memoryItem('note', 'topic')->active);
        self::assertSame([], $state->memoryActive());
    }

    public function test_forget_if_present_is_tolerant_when_absent(): void
    {
        $unchanged = (new ConversationState)->forgetIfPresent('note', 'missing', 1);

        self::assertNull($unchanged->memoryItem('note', 'missing'));
        self::assertTrue($unchanged->isEmpty());
    }

    public function test_preferred_language_round_trips_through_array_when_set(): void
    {
        $state = ConversationState::fromArray([
            'preferred_language' => 'hindi',
        ]);

        self::assertSame('hindi', $state->preferredLanguage);
        self::assertNull($state->preferredLanguageName);

        self::assertSame([
            'section' => null,
            'concerns' => [],
            'attributes' => [],
            'preferences' => [],
            'safety_signals' => [],
            'ready_for_recommendation' => false,
            'memory' => [],
            'preferred_language' => 'hindi',
        ], $state->toArray());
    }

    public function test_preferred_language_with_name_round_trips_through_array(): void
    {
        $state = ConversationState::fromArray([
            'preferred_language' => 'other',
            'preferred_language_name' => 'Marathi',
        ]);

        self::assertSame('other', $state->preferredLanguage);
        self::assertSame('Marathi', $state->preferredLanguageName);

        self::assertSame([
            'section' => null,
            'concerns' => [],
            'attributes' => [],
            'preferences' => [],
            'safety_signals' => [],
            'ready_for_recommendation' => false,
            'memory' => [],
            'preferred_language' => 'other',
            'preferred_language_name' => 'Marathi',
        ], $state->toArray());
    }

    public function test_preferred_language_keys_are_absent_until_set(): void
    {
        self::assertArrayNotHasKey('preferred_language', (new ConversationState)->toArray());
        self::assertArrayNotHasKey('preferred_language_name', (new ConversationState)->toArray());
    }

    public function test_with_preferred_language_is_immutable_and_trimmed(): void
    {
        $state = new ConversationState;
        $languaged = $state->withPreferredLanguage('  Hinglish  ');

        self::assertNotSame($state, $languaged);
        self::assertNull($state->preferredLanguage);
        self::assertSame('Hinglish', $languaged->preferredLanguage);
    }

    public function test_with_preferred_language_preserves_across_other_builds(): void
    {
        $state = (new ConversationState)
            ->withPreferredLanguage('hindi', null)
            ->withConcern('hair_dryness')
            ->withReadyForRecommendation(true);

        self::assertSame('hindi', $state->preferredLanguage);
        self::assertSame(['hair_dryness'], $state->concerns);
        self::assertTrue($state->readyForRecommendation);
    }

    public function test_with_preferred_language_clears_when_null(): void
    {
        $state = (new ConversationState)->withPreferredLanguage('hindi', 'Hindi');

        self::assertSame('hindi', $state->preferredLanguage);

        $cleared = $state->withPreferredLanguage(null);

        self::assertNull($cleared->preferredLanguage);
        self::assertNull($cleared->preferredLanguageName);
    }

    public function test_merge_takes_the_newer_language_preference(): void
    {
        $earlier = (new ConversationState)->withPreferredLanguage('english');
        $later = (new ConversationState)->withPreferredLanguage('hindi');

        self::assertSame('hindi', $earlier->merge($later)->preferredLanguage);
        self::assertSame('english', $earlier->merge(new ConversationState)->preferredLanguage);
    }

    public function test_is_empty_accounts_for_the_language_preference(): void
    {
        self::assertTrue((new ConversationState)->isEmpty());
        self::assertFalse((new ConversationState)->withPreferredLanguage('english')->isEmpty());
    }
}
