<?php

declare(strict_types=1);

namespace Marwa\AI\Contracts;

enum BatchStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
    case PARTIAL = 'partial';
    case UNKNOWN = 'unknown';

    public function isTerminal(): bool
    {
        return in_array($this, [self::COMPLETED, self::FAILED, self::CANCELLED], true);
    }

    public function isActive(): bool
    {
        return in_array($this, [self::PENDING, self::PROCESSING], true);
    }
}
