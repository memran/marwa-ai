<?php

declare(strict_types=1);

namespace Marwa\AI\Contracts;

interface BatchProcessorInterface
{
    /**
     * Add a batch job
     *
     * @param array<string, mixed>|callable $payload
     */
    public function add(string $type, array | callable $payload, array $options = []): string;

    /**
     * Process all pending batches
     */
    public function process(): BatchResultInterface;

    /**
     * Process a specific batch
     */
    public function processBatch(string $batchId): BatchResultInterface;

    /**
     * Get batch status
     */
    public function status(string $batchId): BatchStatus;

    /**
     * Cancel a batch
     */
    public function cancel(string $batchId): bool;

    /**
     * Wait for batch completion
     */
    public function waitFor(string $batchId, int $timeout = 300): BatchResultInterface;

    /**
     * Get pending count
     */
    public function pendingCount(): int;

    /**
     * Flush all batches
     */
    public function flush(): void;
}
