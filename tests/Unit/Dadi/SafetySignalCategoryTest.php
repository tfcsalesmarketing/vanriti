<?php

namespace Tests\Unit\Dadi;

use App\Dadi\Enums\SafetySignalCategory;
use App\Dadi\Enums\SafetyVerdict;
use PHPUnit\Framework\TestCase;

class SafetySignalCategoryTest extends TestCase
{
    public function test_values_are_unique_closed_codes(): void
    {
        $values = array_column(SafetySignalCategory::cases(), 'value');

        self::assertSame($values, array_unique($values));
        self::assertCount(15, $values);

        foreach (SafetySignalCategory::cases() as $category) {
            self::assertSame($category->value, (string) $category->value);
        }
    }

    public function test_block_tier_signals_always_block(): void
    {
        $blockTier = [
            SafetySignalCategory::EmergencyBreathing,
            SafetySignalCategory::EmergencyChestPain,
            SafetySignalCategory::EmergencySwelling,
            SafetySignalCategory::EmergencyBleeding,
            SafetySignalCategory::EmergencyReaction,
            SafetySignalCategory::EmergencyConsciousness,
            SafetySignalCategory::MedicationRisk,
            SafetySignalCategory::SevereSymptoms,
        ];

        foreach ($blockTier as $category) {
            self::assertSame(SafetyVerdict::Block, $category->verdict());
        }
    }

    public function test_advisory_tier_signals_never_block(): void
    {
        $advisoryTier = [
            SafetySignalCategory::InfectionSigns,
            SafetySignalCategory::Medication,
            SafetySignalCategory::Pregnancy,
            SafetySignalCategory::Breastfeeding,
            SafetySignalCategory::Child,
            SafetySignalCategory::Allergy,
            SafetySignalCategory::DiagnosisRequest,
        ];

        foreach ($advisoryTier as $category) {
            self::assertSame(SafetyVerdict::Advisory, $category->verdict());
        }
    }

    public function test_categories_never_own_a_diagnosis_phrase(): void
    {
        foreach (SafetySignalCategory::cases() as $category) {
            self::assertNotContains('diagnosis', [$category->value]);
            self::assertStringNotContainsStringIgnoringCase('you have', $category->value);
        }
    }
}
