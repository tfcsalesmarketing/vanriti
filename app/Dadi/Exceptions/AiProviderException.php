<?php

namespace App\Dadi\Exceptions;

/**
 * Thrown when an AI provider fails to produce a usable response: timeouts,
 * connection failures, authentication/configuration problems, rate limiting,
 * provider unavailability or malformed provider payloads.
 *
 * The category lets the conversation engine decide a graceful, non-technical
 * path for the customer without ever leaking raw API errors.
 */
class AiProviderException extends DadiException
{
    public const CATEGORY_TIMEOUT = 'timeout';

    public const CATEGORY_CONNECTION = 'connection';

    public const CATEGORY_AUTHENTICATION = 'authentication';

    public const CATEGORY_RATE_LIMIT = 'rate_limit';

    public const CATEGORY_UNAVAILABLE = 'unavailable';

    public const CATEGORY_INVALID_RESPONSE = 'invalid_response';

    public const CATEGORY_CONFIGURATION = 'configuration';

    public function __construct(
        public readonly string $category,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message !== '' ? $message : "AI provider failure [{$category}].", $code, $previous);
    }
}
