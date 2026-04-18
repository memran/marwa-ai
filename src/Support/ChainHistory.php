<?php

declare(strict_types=1);

namespace Marwa\AI\Support;

use Marwa\AI\Contracts\ChainHistoryInterface;
use Marwa\AI\Contracts\StepResult;

final class ChainHistory implements ChainHistoryInterface
{
    /** @var array<StepResult> */
    private array $steps = [];

    public function __construct(array $steps = [])
    {
        $this->steps = $steps;
    }

    public function getSteps(): array
    {
        return array_map(fn($s) => $s instanceof StepResult ? $s->toArray() : $s, $this->steps);
    }

    public function getStep(int $index): ?StepResult
    {
        return $this->steps[$index] ?? null;
    }

    public function getTotalDuration(): float
    {
        return array_sum(array_map(fn($s) => $s instanceof StepResult ? $s->duration : 0, $this->steps));
    }

    public function isSuccess(): bool
    {
        foreach ($this->steps as $step) {
            if ($step instanceof StepResult && $step->error !== null) {
                return false;
            }
        }
        return true;
    }

    public function getFirstError(): ?\Throwable
    {
        foreach ($this->steps as $step) {
            if ($step instanceof StepResult && $step->error !== null) {
                return $step->error;
            }
        }
        return null;
    }

    public function toArray(): array
    {
        return $this->getSteps();
    }

    public function replay(array $overrides = []): mixed
    {
        $lastOutput = null;
        foreach ($this->steps as $step) {
            if ($step instanceof StepResult) {
                $lastOutput = $step->output;
            }
        }
        return $lastOutput;
    }
}
