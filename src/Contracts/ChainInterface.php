<?php

declare(strict_types=1);

namespace Marwa\AI\Contracts;

interface ChainInterface
{
    /**
     * Add a step to the chain
     */
    public function step(callable $step, array $options = []): self;

    /**
     * Add a parallel step
     */
    public function parallel(callable ...$steps): self;

    /**
     * Add a conditional branch
     */
    public function conditional(callable $condition, callable $then, ?callable $else = null): self;

    /**
     * Add retry logic
     */
    public function retry(int $maxAttempts = 3, ?callable $shouldRetry = null): self;

    /**
     * Set context shared across steps
     */
    public function withContext(array $context): self;

    /**
     * Execute the chain
     */
    public function execute(): mixed;

    /**
     * Execute chain with streaming output
     */
    public function stream(): \Generator;

    /**
     * Get execution history
     */
    public function getHistory(): ChainHistoryInterface;

    /**
     * Cancel running chain
     */
    public function cancel(): void;

    /**
     * Check if chain is running
     */
    public function isRunning(): bool;
}
