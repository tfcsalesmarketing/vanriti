<?php

namespace App\Dadi\Persistence;

use App\Dadi\Enums\Concern;
use App\Dadi\Enums\ProductProfileStatus;
use App\Dadi\Enums\Section;
use App\Dadi\Exceptions\InvalidProductProfileException;
use App\Dadi\Exceptions\ProductProfileReviewException;
use App\Dadi\Exceptions\ProductProfileUnavailableException;
use App\Dadi\Validation\ProductProfileContentRules;
use App\Dadi\ValueObjects\ProductProfile;
use App\Models\Admin;
use App\Models\DadiProductProfile;
use App\Models\Product;
use App\Services\ActivityLogger;

/**
 * The Laravel-owned seam for Dadi product intelligence.
 *
 * This store is the ONLY door between the catalogue + human review and the
 * future RecommendationEngine:
 *
 *   - it persists and transitions the approval lifecycle (draft →
 *     pending_review → approved / rejected);
 *   - every mutation requires an Admin actor who holds the review permission,
 *     so AI/provider code structurally cannot approve or alter intelligence;
 *   - it retrieves ONLY human-approved profiles whose underlying product is
 *     active (approval and commercial availability stay separate);
 *   - it refuses to build an AI-facing representation that is not approved or
 *     whose product is not live.
 *
 * It never chooses products, never touches cart/commerce state and never calls
 * the AI. Commerce values are always read live from the existing Product.
 */
class DadiProductProfileStore
{
    /**
     * The project-convention permission slug (see RolesAndPermissionsSeeder).
     * Super-admins satisfy it implicitly through Admin::hasPermission.
     */
    public const REVIEW_PERMISSION = 'review-dadi-product-profiles';

    public function __construct(
        private readonly ActivityLogger $logger,
        private readonly array $limits = [],
    ) {}

    /**
     * Author an initial draft profile for a product.
     *
     * @param  array<string,mixed>  $data
     */
    public function create(Product $product, array $data, Admin $actor): DadiProductProfile
    {
        $this->assertCanReview($actor);

        if ($this->forProduct($product) !== null) {
            throw new InvalidProductProfileException('This product already has a Dadi profile.');
        }

        $profile = DadiProductProfile::create([
            'product_id' => $product->getKey(),
            'status' => ProductProfileStatus::Draft,
            'created_by' => $actor->getKey(),
            ...$this->validateContent($data),
        ]);

        $this->logger->log(
            'dadi_product_profile_created',
            $profile,
            'Dadi product profile created for "'.$product->name.'".',
            null,
            ['product_id' => $product->getKey()],
            $actor,
        );

        return $profile;
    }

    /**
     * Update the Dadi intelligence content. Editing approved content
     * invalidates the human approval; editing a rejected profile returns it
     * to draft so it must be explicitly re-submitted for review.
     *
     * @param  array<string,mixed>  $data
     */
    public function updateIntelligence(DadiProductProfile $profile, array $data, Admin $actor): DadiProductProfile
    {
        $this->assertCanReview($actor);

        $current = $profile->status;
        $next = $current;
        $reviewedBy = $profile->reviewed_by;
        $reviewedAt = $profile->reviewed_at;

        if ($current === ProductProfileStatus::Approved) {
            $next = ProductProfileStatus::PendingReview;
            $reviewedBy = null;
            $reviewedAt = null;
        } elseif ($current === ProductProfileStatus::Rejected) {
            $next = ProductProfileStatus::Draft;
            $reviewedBy = null;
            $reviewedAt = null;
        }

        $profile->update([
            'status' => $next,
            'updated_by' => $actor->getKey(),
            'reviewed_by' => $reviewedBy,
            'reviewed_at' => $reviewedAt,
            ...$this->validateContent($data),
        ]);

        $profile->refresh();

        $this->logger->log(
            'dadi_product_profile_updated',
            $profile,
            'Dadi product intelligence updated. Status: '.$next->value.'.',
            ['status' => $current->value],
            ['status' => $next->value],
            $actor,
        );

        return $profile;
    }

    /**
     * Move a draft/rejected profile into the review queue.
     */
    public function submitForReview(DadiProductProfile $profile, Admin $actor): DadiProductProfile
    {
        $this->assertCanReview($actor);

        $this->assertStatusIn(
            $profile,
            [ProductProfileStatus::Draft, ProductProfileStatus::Rejected],
            'Only draft or rejected profiles can be submitted for review.',
        );

        $profile->update([
            'status' => ProductProfileStatus::PendingReview,
            'updated_by' => $actor->getKey(),
            'reviewed_by' => null,
            'reviewed_at' => null,
        ]);

        $profile->refresh();

        $this->logger->log(
            'dadi_product_profile_submitted',
            $profile,
            'Dadi product profile submitted for review.',
            null,
            ['status' => ProductProfileStatus::PendingReview->value],
            $actor,
        );

        return $profile;
    }

    /**
     * Human approval. Requires the pending_review state — a draft is never
     * approved directly — and an authorized admin reviewer.
     */
    public function approve(DadiProductProfile $profile, Admin $actor, ?string $notes = null): DadiProductProfile
    {
        $this->assertCanReview($actor);

        $this->assertStatusIn(
            $profile,
            [ProductProfileStatus::PendingReview],
            'Only pending-review profiles can be approved.',
        );

        $profile->update([
            'status' => ProductProfileStatus::Approved,
            'updated_by' => $actor->getKey(),
            'reviewed_by' => $actor->getKey(),
            'reviewed_at' => now(),
        ]);

        $profile->refresh();

        $this->logger->log(
            'dadi_product_profile_approved',
            $profile,
            'Dadi product profile approved by '.$actor->name.'.',
            null,
            ['reviewed_by' => $actor->getKey(), 'reviewed_at' => $profile->reviewed_at?->toDateTimeString(), 'notes' => $notes],
            $actor,
        );

        return $profile;
    }

    /**
     * Human rejection. Also requires the pending_review state.
     */
    public function reject(DadiProductProfile $profile, Admin $actor, ?string $notes = null): DadiProductProfile
    {
        $this->assertCanReview($actor);

        $this->assertStatusIn(
            $profile,
            [ProductProfileStatus::PendingReview],
            'Only pending-review profiles can be rejected.',
        );

        $profile->update([
            'status' => ProductProfileStatus::Rejected,
            'updated_by' => $actor->getKey(),
            'reviewed_by' => $actor->getKey(),
            'reviewed_at' => now(),
        ]);

        $profile->refresh();

        $this->logger->log(
            'dadi_product_profile_rejected',
            $profile,
            'Dadi product profile rejected.',
            null,
            ['reviewed_by' => $actor->getKey(), 'reviewed_at' => $profile->reviewed_at?->toDateTimeString(), 'notes' => $notes],
            $actor,
        );

        return $profile;
    }

    /**
     * The single profile for a catalogue product, if one exists.
     */
    public function forProduct(Product $product): ?DadiProductProfile
    {
        return DadiProductProfile::query()
            ->where('product_id', $product->getKey())
            ->first();
    }

    public function isApproved(DadiProductProfile $profile): bool
    {
        return $profile->status === ProductProfileStatus::Approved;
    }

    /**
     * Whether the referenced catalogue product is currently active. Trashed or
     * missing products are never active; product status wins over any profile.
     */
    public function isProductActive(DadiProductProfile $profile): bool
    {
        $product = $profile->product;

        return $product !== null && $product->status === 'active';
    }

    /**
     * Approval of the intelligence AND live commercial availability are both
     * required for Dadi use, but they stay fully separate concerns: approval
     * lives here, availability lives on the Product.
     */
    public function usableForRecommendation(DadiProductProfile $profile): bool
    {
        return $this->isApproved($profile) && $this->isProductActive($profile);
    }

    /**
     * Build the immutable, controlled Dadi representation of an approved,
     * active product profile. Throws when the profile is not human-approved
     * or its product is not live — an unapproved product must never leak in.
     */
    public function buildProfile(DadiProductProfile $profile): ProductProfile
    {
        if (! $this->isApproved($profile)) {
            throw new ProductProfileUnavailableException(
                'Only human-approved product profiles can be exposed to Dadi.',
            );
        }

        $product = $profile->product;

        if ($product === null || $product->status !== 'active') {
            throw new ProductProfileUnavailableException(
                'The underlying product is not active; this profile cannot be used.',
            );
        }

        return new ProductProfile(
            productReference: (int) $product->getKey(),
            name: $product->name,
            sections: $profile->sections ?? [],
            concerns: $profile->concerns ?? [],
            positioning: $profile->positioning,
            approvedBenefits: $profile->approved_benefits ?? [],
            approvedUsageContext: $profile->approved_usage_context ?? [],
            approvedPrecautions: $profile->approved_precautions ?? [],
            suitabilityNotes: $profile->suitability_notes ?? [],
        );
    }

    /**
     * The retrieval seam for the future RecommendationEngine.
     *
     * Returns only human-approved profiles whose underlying product is active,
     * optionally narrowed by section or approved concern, in deterministic
     * product id order. It never ranks, scores or chooses — that is Stage 7.
     *
     * @return array<int,ProductProfile>
     */
    public function retrieveApproved(?Section $section = null, ?Concern $concern = null): array
    {
        $profiles = DadiProductProfile::query()
            ->where('status', ProductProfileStatus::Approved->value)
            ->with('product')
            ->orderBy('product_id')
            ->get();

        $result = [];

        foreach ($profiles as $profile) {
            if (! $this->usableForRecommendation($profile)) {
                continue;
            }

            if ($section !== null && ! in_array($section->value, $profile->sections ?? [], true)) {
                continue;
            }

            if ($concern !== null && ! in_array($concern->value, $profile->concerns ?? [], true)) {
                continue;
            }

            $result[] = $this->buildProfile($profile);
        }

        return $result;
    }

    /**
     * @return array<int,ProductProfile>
     */
    public function retrieveApprovedBySection(Section $section): array
    {
        return $this->retrieveApproved(section: $section);
    }

    /**
     * @return array<int,ProductProfile>
     */
    public function retrieveApprovedByConcern(Concern $concern): array
    {
        return $this->retrieveApproved(concern: $concern);
    }

    /**
     * Every lifecycle mutation goes through this gate. Only an Admin holding
     * the review permission (or a super-admin) may proceed — customer-facing
     * and AI/provider code has no path here.
     */
    private function assertCanReview(Admin $actor): void
    {
        if (! $actor->hasPermission(self::REVIEW_PERMISSION)) {
            throw new ProductProfileReviewException(
                'Unauthorized: this account cannot review Dadi product profiles.',
            );
        }
    }

    /**
     * @param  array<ProductProfileStatus>  $allowed
     */
    private function assertStatusIn(DadiProductProfile $profile, array $allowed, string $message): void
    {
        foreach ($allowed as $status) {
            if ($profile->status === $status) {
                return;
            }
        }

        throw new ProductProfileReviewException($message);
    }

    /**
     * Normalize and validate every accepted intelligence key. Unknown keys and
     * any commerce vocabulary are rejected before they can reach the row.
     *
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    private function validateContent(array $data): array
    {
        $data = ProductProfileContentRules::assertKnownKeys($data);

        return [
            'sections' => ProductProfileContentRules::assertSections($data['sections'] ?? []),
            'concerns' => ProductProfileContentRules::assertConcerns(
                $data['concerns'] ?? [],
                (int) $this->limit('max_concerns', ProductProfile::DEFAULT_MAX_CONCERNS),
            ),
            'positioning' => ProductProfileContentRules::assertText(
                $data['positioning'] ?? null,
                (int) $this->limit('max_positioning_length', ProductProfile::DEFAULT_MAX_POSITIONING),
            ),
            'approved_benefits' => ProductProfileContentRules::assertTextList(
                $data['approved_benefits'] ?? [],
                (int) $this->limit('max_benefits', ProductProfile::DEFAULT_MAX_BENEFITS),
                (int) $this->limit('max_benefit_length', ProductProfile::DEFAULT_BENEFIT_LENGTH),
                'approved_benefits',
            ),
            'approved_usage_context' => ProductProfileContentRules::assertTextList(
                $data['approved_usage_context'] ?? [],
                (int) $this->limit('max_usage_context', ProductProfile::DEFAULT_MAX_USAGE),
                (int) $this->limit('max_usage_length', ProductProfile::DEFAULT_USAGE_LENGTH),
                'approved_usage_context',
            ),
            'approved_precautions' => ProductProfileContentRules::assertTextList(
                $data['approved_precautions'] ?? [],
                (int) $this->limit('max_precautions', ProductProfile::DEFAULT_MAX_PRECAUTIONS),
                (int) $this->limit('max_precaution_length', ProductProfile::DEFAULT_PRECAUTION_LENGTH),
                'approved_precautions',
            ),
            'suitability_notes' => ProductProfileContentRules::assertTextList(
                $data['suitability_notes'] ?? [],
                (int) $this->limit('max_suitability_notes', ProductProfile::DEFAULT_MAX_SUITABILITY),
                (int) $this->limit('max_suitability_length', ProductProfile::DEFAULT_SUITABILITY_LENGTH),
                'suitability_notes',
            ),
        ];
    }

    private function limit(string $key, mixed $default): mixed
    {
        return $this->limits[$key] ?? $default;
    }
}
