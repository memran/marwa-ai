<?php

declare(strict_types=1);

namespace Marwa\AI\Support;

use Marwa\AI\Contracts\AIManagerInterface;
use Marwa\AI\Contracts\BatchProcessorInterface;
use Marwa\AI\Contracts\BatchResultInterface;
use Marwa\AI\Contracts\BatchStatus;

final class BatchProcessor implements BatchProcessorInterface
{
    /** @var array<array{id: string, type: string, payload: mixed, options: array, status: BatchStatus}> */
    private array $batches = [];

    private AIManagerInterface $manager;

    public function __construct(AIManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    public function add(string $type, array|callable $payload, array $options = []): string
    {
        $id = $type . '_' . uniqid();

        $this->batches[$id] = [
            'id' => $id,
            'type' => $type,
            'payload' => $payload,
            'options' => $options,
            'status' => BatchStatus::PENDING,
            'result' => null,
            'error' => null,
        ];

        return $id;
    }

    public function process(): BatchResultInterface
    {
        $results = [];
        $failures = [];

        foreach ($this->batches as $id => &$batch) {
            if ($batch['status']->isActive()) {
                $batch['status'] = BatchStatus::PROCESSING;
                try {
                    $result = $this->executeBatch($batch);
                    $batch['result'] = $result;
                    $batch['status'] = BatchStatus::COMPLETED;
                    $results[$id] = $result;
                } catch (\Throwable $e) {
                    $batch['error'] = $e->getMessage();
                    $batch['status'] = BatchStatus::FAILED;
                    $failures[] = ['id' => $id, 'error' => $e->getMessage()];
                }
            }
        }

        return new BatchResult(
            array_keys($this->batches),
            $results,
            $failures,
            $this->calculateProgress()
        );
    }

    public function processBatch(string $batchId): BatchResultInterface
    {
        if (!isset($this->batches[$batchId])) {
            throw new \InvalidArgumentException("Batch {$batchId} not found.");
        }

        $batch = &$this->batches[$batchId];
        $batch['status'] = BatchStatus::PROCESSING;

        try {
            $result = $this->executeBatch($batch);
            $batch['result'] = $result;
            $batch['status'] = BatchStatus::COMPLETED;
        } catch (\Throwable $e) {
            $batch['error'] = $e->getMessage();
            $batch['status'] = BatchStatus::FAILED;
        }

        return new BatchResult([$batchId], $batch['result'] ?? [], $batch['error'] ? [$batchId => $batch['error']] : [], 100);
    }

    public function status(string $batchId): BatchStatus
    {
        return $this->batches[$batchId]['status'] ?? BatchStatus::UNKNOWN;
    }

    public function cancel(string $batchId): bool
    {
        if (isset($this->batches[$batchId])) {
            $this->batches[$batchId]['status'] = BatchStatus::CANCELLED;
            return true;
        }
        return false;
    }

    public function waitFor(string $batchId, int $timeout = 300): BatchResultInterface
    {
        $start = time();
        while (isset($this->batches[$batchId]) && $this->batches[$batchId]['status']->isActive()) {
            if (time() - $start >= $timeout) {
                break;
            }
            usleep(100000);
        }
        return $this->processBatch($batchId);
    }

    public function pendingCount(): int
    {
        return count(array_filter($this->batches, fn($b) => $b['status'] === BatchStatus::PENDING));
    }

    public function flush(): void
    {
        $this->batches = [];
    }

    private function executeBatch(array &$batch): mixed
    {
        if (is_callable($batch['payload'])) {
            return call_user_func($batch['payload'], $batch['options']);
        }

        $provider = $batch['options']['provider'] ?? 'openai';

        return match ($batch['type']) {
            'completion' => $this->manager->driver($provider)->completion($batch['payload'], $batch['options']),
            'embedding' => $this->manager->driver($provider)->embed($batch['payload'], $batch['options']),
            'image' => $this->manager->driver($provider)->generateImage($batch['payload'], $batch['options']),
            default => throw new \RuntimeException("Unknown batch type: {$batch['type']}")
        };
    }

    private function calculateProgress(): float
    {
        $total = count($this->batches);
        if ($total === 0) {
            return 100.0;
        }
        $completed = count(array_filter($this->batches, fn($b) => $b['status']->isTerminal()));
        return round(($completed / $total) * 100, 2);
    }
}
