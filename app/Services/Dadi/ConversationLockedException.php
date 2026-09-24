<?php

namespace App\Services\Dadi;

use RuntimeException;

/**
 * Thrown when the customer tries to continue a conversation that cannot
 * accept more messages (for example a conversation placed on a safety hold).
 * The customer-facing flow must never silently start a fresh conversation
 * around a held one.
 */
final class ConversationLockedException extends RuntimeException {}
