<?php

declare(strict_types=1);

namespace Marwa\AI\Support;

use Marwa\AI\Contracts\AIClientInterface;
use Marwa\AI\Contracts\HealthCheckInterface;
use Marwa\AI\Contracts\HealthStatus;
use Marwa\AI\Contracts\RateLimitInfo;

class HealthChecker implements HealthCheckInterface
{
    public function __construct(private AIClientInterface $client) {}

    public function check(): HealthStatus
    {
        try {
            $this->ping();
            return HealthStatus::HEALTHY;
        } catch (\Throwable) {
            return HealthStatus::DOWN;
        }
    }

    public function getLatency(): int
    {
        $start = microtime(true);
        try {
            $this->ping();
        } catch (\Throwable) {
            return -1;
        }
        return (int) ((microtime(true) - $start) * 1000);
    }

    public function getRateLimit(): RateLimitInfo
    {
        try {
            $this->client->completion(['prompt' => 'test']);
        } catch (\Throwable) {
            return new RateLimitInfo(0, 0, 0);
        }

        return new RateLimitInfo(1000, 999, 60);
    }

    public function getModels(): array
    {
        return $this->client->supports('list_models')
            ? $this->listModels()
            : [$this->client->getModel()];
    }

    public function verifyCredentials(): bool
    {
        try {
            $this->client->completion(['prompt' => 'hello']);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function ping(): void
    {
        $this->client->countTokens('ping');
    }

    private function listModels(): array
    {
        return ['default' => $this->client->getModel()];
    }
}
