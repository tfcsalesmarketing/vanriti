<?php

namespace App\Dadi\Exceptions;

/**
 * Thrown when a ConversationState is constructed or merged from invalid data
 * (unknown section, malformed lists/maps, non-scalar attribute values).
 */
class InvalidConversationStateException extends DadiException {}
