<?php

namespace App\Dadi\Exceptions;

/**
 * Thrown when a conversation is requested by an identity that does not own it,
 * or when no identity is supplied at all. Prevents cross-conversation context
 * leakage between users or between guest sessions.
 */
class DadiOwnershipException extends DadiException {}
