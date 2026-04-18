<?php

declare(strict_types=1);

namespace Marwa\AI\Contracts;

interface ChainHistoryInterface
{
    /**
     * Get all steps
     *
     * @return array<array{name: string, input: mixed, output: mixed, duration: float, error?: \Throwable}>
     */
    public function getSteps(): array;

    /**
     * Get step by index
     */
    public function getStep(int $index): ?StepResult;

    /**
     * Get total execution time
     */
    public function getTotalDuration(): float;

    /**
     * Check if chain succeeded
     */
    public function isSuccess(): bool;

    /**
     * Get the first error if any
     */
    public function getFirstError(): ?\Throwable;

    /**
     * Serialize history
     */
    public function toArray(): array;

    /**
     * Replay the chain
     */
    public function replay(array $overrides = []): mixed;
}
