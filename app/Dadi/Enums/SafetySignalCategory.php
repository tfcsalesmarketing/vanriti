<?php

namespace App\Dadi\Enums;

/**
 * The controlled Laravel-owned vocabulary of safety signals.
 *
 * Each signal is a stable code (never free text) and maps to the strongest
 * verdict it can justify on its own. The SafetyAdjudicator decides the final,
 * binding verdict for a turn using BLOCK > ADVISORY > CLEAR precedence; weaker
 * signals never downgrade a stronger one.
 *
 *   - Block tier:    emergency indicators and medication risk.
 *   - Advisory tier: restricted, still-conversational situations.
 *
 * The vocabulary is deliberately small and practical. It does not attempt to
 * be a medical ontology: these are conversational tripwires, not diagnoses.
 */
enum SafetySignalCategory: string
{
    case EmergencyBreathing = 'emergency_breathing';

    case EmergencyChestPain = 'emergency_chest_pain';

    case EmergencySwelling = 'emergency_swelling';

    case EmergencyBleeding = 'emergency_bleeding';

    case EmergencyReaction = 'emergency_reaction';

    case EmergencyConsciousness = 'emergency_consciousness';

    case MedicationRisk = 'medication_risk';

    case SevereSymptoms = 'severe_symptoms';

    case InfectionSigns = 'infection_signs';

    case Medication = 'medication';

    case Pregnancy = 'pregnancy';

    case Breastfeeding = 'breastfeeding';

    case Child = 'child';

    case Allergy = 'allergy';

    case DiagnosisRequest = 'diagnosis_request';

    /**
     * The strongest verdict a signal can justify on its own.
     */
    public function verdict(): SafetyVerdict
    {
        return match ($this) {
            self::EmergencyBreathing,
            self::EmergencyChestPain,
            self::EmergencySwelling,
            self::EmergencyBleeding,
            self::EmergencyReaction,
            self::EmergencyConsciousness,
            self::MedicationRisk,
            self::SevereSymptoms => SafetyVerdict::Block,
            self::InfectionSigns,
            self::Medication,
            self::Pregnancy,
            self::Breastfeeding,
            self::Child,
            self::Allergy,
            self::DiagnosisRequest => SafetyVerdict::Advisory,
        };
    }
}
