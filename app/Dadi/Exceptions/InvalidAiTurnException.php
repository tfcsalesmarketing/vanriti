<?php

namespace App\Dadi\Exceptions;

/**
 * Thrown when AI turn output is structurally unusable (for example an empty
 * reply). Structural validity of AI output is enforced by a Laravel layer.
 */
class InvalidAiTurnException extends DadiException {}
