<?php

declare(strict_types=1);

namespace Marwa\AI\Support;

use Marwa\AI\Contracts\BatchResultInterface;
use Marwa\AI\Contracts\BatchStatus;

final class BatchResult implements BatchResultInterface
{
    public function __construct(
        private array $batchIds,
        private array $results,
        private array $failures = [],
        private float $progress = 0.0
    ) {}

    public function getBatchId(): string
    {
        return implode(',', $this->batchIds);
    }

    public function isSuccess(): bool
    {
        return empty($this->failures);
    }

    public function getFailures(): array
    {
        return $this->failures;
    }

    public function getResults(): array
    {
        return $this->results;
    }

    public function getProgress(): float
    {
        return $this->progress;
    }

    public function getTotal(): int
    {
        return count($this->batchIds);
    }

    public function getCompleted(): int
    {
        return count($this->results) + count($this->failures);
    }

    public function wait(int $timeout = 300): self
    {
        return $this;
    }

    public function retry(): self
    {
        return $this;
    }
}
