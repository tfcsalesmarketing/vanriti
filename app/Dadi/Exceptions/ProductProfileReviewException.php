<?php

namespace App\Dadi\Exceptions;

/**
 * Raised when a product profile lifecycle action is attempted without the
 * required admin authority, or when an illegal transition is requested (for
 * example approving a draft that was never submitted for review).
 *
 * This is the review boundary: AI/provider code cannot approve or modify
 * authoritative intelligence because every transition here requires an
 * authorized Admin actor.
 */
class ProductProfileReviewException extends DadiException {}
