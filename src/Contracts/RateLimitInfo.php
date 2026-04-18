<?php

declare(strict_types=1);

namespace Marwa\AI\Contracts;

/**
 * Rate limit information
 */
final class RateLimitInfo
{
    public function __construct(
        public readonly int $limit,
        public readonly int $remaining,
        public readonly int $resetAfter // seconds
    ) {}

    public function isExhausted(): bool
    {
        return $this->remaining <= 0;
    }

    public function getResetAfter(): \DateTimeImmutable
    {
        return (new \DateTimeImmutable())->modify("+{$this->resetAfter} seconds");
    }

    public function toArray(): array
    {
        return [
            'limit' => $this->limit,
            'remaining' => $this->remaining,
            'reset_after' => $this->resetAfter,
        ];
    }
}
