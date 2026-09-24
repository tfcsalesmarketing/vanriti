<?php

namespace Tests\Unit\Dadi;

use App\Dadi\Enums\ProductProfileStatus;
use PHPUnit\Framework\TestCase;

class ProductProfileStatusTest extends TestCase
{
    public function test_controlled_lifecycle_values(): void
    {
        self::assertSame('draft', ProductProfileStatus::Draft->value);
        self::assertSame('pending_review', ProductProfileStatus::PendingReview->value);
        self::assertSame('approved', ProductProfileStatus::Approved->value);
        self::assertSame('rejected', ProductProfileStatus::Rejected->value);
    }

    public function test_only_approved_is_usable_for_dadi(): void
    {
        self::assertTrue(ProductProfileStatus::Approved->isUsable());
        self::assertFalse(ProductProfileStatus::Draft->isUsable());
        self::assertFalse(ProductProfileStatus::PendingReview->isUsable());
        self::assertFalse(ProductProfileStatus::Rejected->isUsable());
    }

    public function test_labels_are_human_readable(): void
    {
        self::assertSame('Pending Review', ProductProfileStatus::PendingReview->label());
        self::assertSame('Approved', ProductProfileStatus::Approved->label());
    }

    public function test_unknown_status_is_not_in_the_vocabulary(): void
    {
        self::assertNull(ProductProfileStatus::tryFrom('published'));
    }
}
