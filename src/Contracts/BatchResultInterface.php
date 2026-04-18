<?php

declare(strict_types=1);

namespace Marwa\AI\Contracts;

interface BatchResultInterface
{
    /**
     * Get batch ID
     */
    public function getBatchId(): string;

    /**
     * Check if batch completed successfully
     */
    public function isSuccess(): bool;

    /**
     * Get failed jobs
     *
     * @return array<array{id: string, error: string}>
     */
    public function getFailures(): array;

    /**
     * Get successful results
     *
     * @return array<string, mixed>
     */
    public function getResults(): array;

    /**
     * Get progress percentage
     */
    public function getProgress(): float;

    /**
     * Get total jobs
     */
    public function getTotal(): int;

    /**
     * Get completed count
     */
    public function getCompleted(): int;

    /**
     * Wait for completion
     */
    public function wait(int $timeout = 300): self;

    /**
     * Retry failed jobs
     */
    public function retry(): self;
}
