<?php

namespace App\Dadi\Exceptions;

/**
 * Thrown when AI output fails deterministic Laravel-side validation: malformed
 * JSON, a missing/oversized reply, forbidden product fields (SKU, price,
 * stock...), or an otherwise unusable structured proposal.
 *
 * The engine converts this into a graceful conversational turn; nothing from
 * the model is persisted until it passes validation.
 */
class InvalidAiOutputException extends DadiException {}
