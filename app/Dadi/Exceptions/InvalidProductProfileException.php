<?php

namespace App\Dadi\Exceptions;

/**
 * Raised when Dadi product intelligence is structurally invalid: unknown
 * section/concern codes, over-length fields, forbidden commerce content or
 * unexpected keys. The AI layer can never recover from this — the payload is
 * rejected before persistence and before it is ever exposed to Dadi.
 */
class InvalidProductProfileException extends DadiException {}
