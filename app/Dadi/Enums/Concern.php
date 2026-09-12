<?php

namespace App\Dadi\Enums;

/**
 * The closed, human-maintained vocabulary of suitable-concern codes that a
 * Dadi product profile may be tagged with.
 *
 * Concern codes are deliberately validated against this enum: free-form text
 * is not accepted as an approved concern, so profiles cannot smuggle arbitrary
 * claims into the retrieval layer. Each concern maps to its primary business
 * section for admin/UI grouping; the profile itself stores the sections and
 * concerns it needs, keeping the underlying product/category relationship
 * authoritative.
 */
enum Concern: string
{
    /* Hair */
    case HairDryness = 'hair_dryness';
    case HairFrizz = 'hair_frizz';
    case HairRoughness = 'hair_roughness';
    case HairFall = 'hair_fall';
    case HairBreakage = 'hair_breakage';
    case HairDullness = 'hair_dullness';
    case Dandruff = 'dandruff';
    case OilyScalp = 'oily_scalp';
    case HairThinning = 'hair_thinning';

    /* Skin */
    case SkinDryness = 'skin_dryness';
    case SkinRoughness = 'skin_roughness';
    case SkinDullness = 'skin_dullness';
    case Oiliness = 'oiliness';
    case Acne = 'acne';
    case Pigmentation = 'pigmentation';
    case Tanning = 'tanning';
    case SignsOfAging = 'signs_of_aging';
    case SkinSensitivity = 'skin_sensitivity';
    case UnevenTone = 'uneven_tone';
    case OpenPores = 'open_pores';

    /* Wellness */
    case StressSupport = 'stress_support';
    case SleepSupport = 'sleep_support';
    case ImmunitySupport = 'immunity_support';
    case EnergySupport = 'energy_support';
    case JointComfort = 'joint_comfort';
    case DigestionComfort = 'digestion_comfort';
    case Relaxation = 'relaxation';

    public function label(): string
    {
        return match ($this) {
            self::HairDryness => 'Dry hair',
            self::HairFrizz => 'Frizzy hair',
            self::HairRoughness => 'Rough hair',
            self::HairFall => 'Hair fall',
            self::HairBreakage => 'Hair breakage',
            self::HairDullness => 'Dull hair',
            self::Dandruff => 'Dandruff',
            self::OilyScalp => 'Oily scalp',
            self::HairThinning => 'Thinning hair',
            self::SkinDryness => 'Dry skin',
            self::SkinRoughness => 'Rough skin',
            self::SkinDullness => 'Dull skin',
            self::Oiliness => 'Oily skin',
            self::Acne => 'Acne / blemishes',
            self::Pigmentation => 'Pigmentation',
            self::Tanning => 'Tanning',
            self::SignsOfAging => 'Signs of aging',
            self::SkinSensitivity => 'Sensitive skin',
            self::UnevenTone => 'Uneven tone',
            self::OpenPores => 'Open pores',
            self::StressSupport => 'Stress support',
            self::SleepSupport => 'Sleep support',
            self::ImmunitySupport => 'Immunity support',
            self::EnergySupport => 'Energy support',
            self::JointComfort => 'Joint comfort',
            self::DigestionComfort => 'Digestion comfort',
            self::Relaxation => 'Relaxation',
        };
    }

    /**
     * Primary business section this concern is grouped under. Optionally
     * conflicts are avoided by making a concern belong to one section only —
     * a profile decides its own sections independently of this default.
     */
    public function section(): Section
    {
        return match ($this) {
            self::HairDryness,
            self::HairFrizz,
            self::HairRoughness,
            self::HairFall,
            self::HairBreakage,
            self::HairDullness,
            self::Dandruff,
            self::OilyScalp,
            self::HairThinning => Section::Hair,
            self::SkinDryness,
            self::SkinRoughness,
            self::SkinDullness,
            self::Oiliness,
            self::Acne,
            self::Pigmentation,
            self::Tanning,
            self::SignsOfAging,
            self::SkinSensitivity,
            self::UnevenTone,
            self::OpenPores => Section::Skin,
            self::StressSupport,
            self::SleepSupport,
            self::ImmunitySupport,
            self::EnergySupport,
            self::JointComfort,
            self::DigestionComfort,
            self::Relaxation => Section::Wellness,
        };
    }
}
