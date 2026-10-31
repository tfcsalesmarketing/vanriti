<?php

namespace App\Services\Analytics\Exceptions;

class OpenAiTimestampException extends \InvalidArgumentException
{
    public function __construct(
        string $message,
        protected string $reason = 'expired',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function isExpired(): bool
    {
        return $this->reason === 'expired';
    }

    public function isFuture(): bool
    {
        return $this->reason === 'future';
    }
}

