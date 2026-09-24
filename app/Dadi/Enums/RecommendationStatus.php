<?php

namespace App\Dadi\Enums;

/**
 * The controlled outcome of a Laravel-owned recommendation pass.
 *
 * The vocabulary stays as small as possible — every case maps to a real,
 * currently reachable state of the engine:
 *
 *   - available:          at least one eligible product is currently in stock.
 *   - no_match:           no approved, active profile matched the customer's
 *                         current understanding at all.
 *   - unavailable:        suitable approved+active intelligence matched, but
 *                         every matched product is currently out of stock.
 *   - safety_blocked:     the authoritative safety assessment is a hard block;
 *                         nothing may be recommended.
 *   - advisory_restricted:the authoritative safety assessment is advisory;
 *                         recommendations are restricted until the assessment
 *                         explicitly permits them again.
 */
enum RecommendationStatus: string
{
    case Available = 'available';

    case NoMatch = 'no_match';

    case Unavailable = 'unavailable';

    case SafetyBlocked = 'safety_blocked';

    case AdvisoryRestricted = 'advisory_restricted';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Available',
            self::NoMatch => 'No match found',
            self::Unavailable => 'Currently unavailable',
            self::SafetyBlocked => 'Blocked by safety assessment',
            self::AdvisoryRestricted => 'Restricted by advisory assessment',
        };
    }

    /**
     * Whether the result is safe to feed a recommendation conversation. Only
     * available candidates may be surfaced; every other status carries none.
     */
    public function hasCandidates(): bool
    {
        return $this === self::Available;
    }
}
