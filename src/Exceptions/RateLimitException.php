<?php

declare(strict_types=1);

namespace Marwa\AI\Exceptions;

class RateLimitException extends AIException
{
    public function __construct(
        public readonly int $retryAfter,
        public readonly string $provider,
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message ?: "Rate limit exceeded for {$provider}. Retry after {$retryAfter}s.", $code, $previous);
    }
}
