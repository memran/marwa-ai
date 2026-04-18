<?php

declare(strict_types=1);

namespace Marwa\AI\Support;

use Marwa\AI\Contracts\ChainHistoryInterface;
use Marwa\AI\Contracts\ChainInterface;
use Marwa\AI\Contracts\StepResult;
use Marwa\AI\Support\StreamChunk;

class Chain implements ChainInterface
{
    /** @var array<array{name: string, step: callable, options: array}> */
    private array $steps = [];

    private ?array $context = null;
    private int $maxRetries = 0;
    /** @var ?callable */
    private $shouldRetryCallback = null;
    private array $history = [];
    private bool $running = false;
    private bool $cancelled = false;

    public function __construct(array $steps = [], array $context = [])
    {
        $this->context = $context;
        foreach ($steps as $name => $step) {
            $this->step($step, ['name' => is_string($name) ? $name : null]);
        }
    }

    public function step(callable $step, array $options = []): self
    {
        $this->steps[] = [
            'name' => $options['name'] ?? uniqid('step_'),
            'step' => $step,
            'options' => $options,
        ];
        return $this;
    }

    public function parallel(callable ...$steps): self
    {
        foreach ($steps as $i => $step) {
            $this->steps[] = [
                'name' => 'parallel_' . ($i + 1),
                'step' => $step,
                'options' => ['parallel' => true],
            ];
        }
        return $this;
    }

    public function conditional(callable $condition, callable $then, callable $else = null): self
    {
        return $this->step(fn($input) => $condition($input) ? $then($input) : ($else ? $else($input) : $input), [
            'name' => 'conditional'
        ]);
    }

    public function retry(int $maxAttempts = 3, ?callable $shouldRetry = null): self
    {
        $this->maxRetries = max(0, $maxAttempts - 1);
        $this->shouldRetryCallback = $shouldRetry;
        return $this;
    }

    public function withContext(array $context): self
    {
        $this->context = array_merge($this->context ?? [], $context);
        return $this;
    }

    public function execute(): mixed
    {
        $this->running = true;
        $this->cancelled = false;
        $this->history = [];
        $input = $this->context;
        $maxRetries = $this->maxRetries;

        foreach ($this->steps as $stepInfo) {
            if ($this->cancelled) {
                break;
            }

            $attempt = 0;
            $success = false;

            while (!$success && !$this->cancelled) {
                $start = microtime(true);
                try {
                    $result = ($stepInfo['step'])($input, $this->getStepContext($stepInfo));
                    $success = true;

                    $this->history[] = new StepResult(
                        $stepInfo['name'],
                        $input,
                        $result,
                        microtime(true) - $start
                    );

                    $input = $result;
                } catch (\Throwable $e) {
                    $this->history[] = new StepResult(
                        $stepInfo['name'],
                        $input,
                        null,
                        microtime(true) - $start,
                        $e
                    );

                    $shouldRetry = $this->shouldRetryCallback;

                    if ($attempt < $maxRetries && $shouldRetry !== null && $shouldRetry($e, $attempt)) {
                        $attempt++;
                        usleep(100000 * $attempt);
                        continue;
                    }
                    break;
                }
            }
        }

        $this->running = false;
        return $input;
    }

    public function stream(): \Generator
    {
        foreach ($this->steps as $i => $stepInfo) {
            if ($this->cancelled) {
                yield new StreamChunk('', true, null, 'cancelled');
                break;
            }

            $result = ($stepInfo['step'])($this->context, ['stream' => true, 'step' => $i]);
            yield new StreamChunk(is_string($result) ? $result : json_encode($result));
        }
    }

    public function getHistory(): ChainHistoryInterface
    {
        return new ChainHistory($this->history);
    }

    public function cancel(): void
    {
        $this->cancelled = true;
    }

    public function isRunning(): bool
    {
        return $this->running;
    }

    private function getStepContext(array $stepInfo): array
    {
        return ['step' => $stepInfo['name'], 'options' => $stepInfo['options']];
    }
}
