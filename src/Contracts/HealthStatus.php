<?php

declare(strict_types=1);

namespace Marwa\AI\Contracts;

enum HealthStatus: string
{
    case HEALTHY = 'healthy';
    case DEGRADED = 'degraded';
    case DOWN = 'down';
    case UNKNOWN = 'unknown';

    public function isOperational(): bool
    {
        return $this === self::HEALTHY;
    }
}
