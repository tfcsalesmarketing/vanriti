<?php

namespace App\Dadi\Exceptions;

/**
 * Raised when a Dadi product profile cannot be exposed to the AI layer: it is
 * not human-approved, or its underlying product is not active/available.
 *
 * Human approval gates the intelligence; the live product system gates
 * commercial availability. Either failure makes the profile unusable.
 */
class ProductProfileUnavailableException extends DadiException {}
