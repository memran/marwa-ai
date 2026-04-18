<?php

declare(strict_types=1);

namespace Marwa\AI\Exceptions;

use Throwable;

class AIException extends \RuntimeException
{
    public static function providerNotFound(string $provider): self
    {
        return new self("AI provider '{$provider}' is not registered.");
    }

    public static function invalidConfiguration(string $provider): self
    {
        return new self("Invalid configuration for provider '{$provider}'.");
    }

    public static function apiError(string $provider, string $message, int $code = 0): self
    {
        return new self("{$provider} API error: {$message}", $code);
    }

    public static function rateLimitExceeded(string $provider, int $retryAfter = 0): self
    {
        $msg = "Rate limit exceeded for {$provider}";
        if ($retryAfter > 0) {
            $msg .= ". Retry after {$retryAfter} seconds.";
        }
        return new self($msg);
    }

    public static function streamingFailed(string $reason): self
    {
        return new self("Streaming failed: {$reason}");
    }

    public static function toolExecutionFailed(string $tool, string $error): self
    {
        return new self("Tool '{$tool}' execution failed: {$error}");
    }

    public static function validationFailed(array $errors): self
    {
        return new self('Validation failed: ' . json_encode($errors));
    }
}
