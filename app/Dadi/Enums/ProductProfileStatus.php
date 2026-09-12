<?php

namespace App\Dadi\Enums;

/**
 * The human-governed approval lifecycle of a Dadi product profile.
 *
 * A profile is Dadi-specific product intelligence (positioning, suitable
 * sections/concerns, approved benefits/precautions) that references the
 * existing VANRITI product. Nothing here is commerce truth: price, stock,
 * SKU and status always come from the catalogue.
 *
 *   draft  →  pending_review  →  approved
 *                             ↘  rejected
 *
 * Only the approved state is usable by the future RecommendationEngine. The
 * AI cannot move a profile through this lifecycle: transition methods live on
 * the Laravel-owned DadiProductProfileStore and require an authorized Admin.
 */
enum ProductProfileStatus: string
{
    case Draft = 'draft';

    case PendingReview = 'pending_review';

    case Approved = 'approved';

    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::PendingReview => 'Pending Review',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
        };
    }

    /**
     * Whether this profile's intelligence is currently cleared for Dadi use.
     * Purely about human approval of the intelligence content — commercial
     * availability is decided separately by the existing product system.
     */
    public function isUsable(): bool
    {
        return $this === self::Approved;
    }
}
