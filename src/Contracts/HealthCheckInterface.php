<?php

declare(strict_types=1);

namespace Marwa\AI\Contracts;

interface HealthCheckInterface
{
    /**
     * Check if provider is available
     */
    public function check(): HealthStatus;

    /**
     * Get latency
     */
    public function getLatency(): int; // milliseconds

    /**
     * Get rate limit status
     */
    public function getRateLimit(): RateLimitInfo;

    /**
     * Get model list
     */
    public function getModels(): array;

    /**
     * Verify credentials
     */
    public function verifyCredentials(): bool;
}
