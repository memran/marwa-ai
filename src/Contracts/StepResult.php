<?php

declare(strict_types=1);

namespace Marwa\AI\Contracts;

/**
 * Value object representing a step result in a chain
 */
final class StepResult
{
    public function __construct(
        public readonly string $name,
        public readonly mixed $input,
        public readonly mixed $output,
        public readonly float $duration,
        public readonly ?\Throwable $error = null
    ) {}

    public function isSuccess(): bool
    {
        return $this->error === null;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'input' => $this->input,
            'output' => $this->output,
            'duration' => $this->duration,
            'error' => $this->error?->getMessage(),
        ];
    }
}
