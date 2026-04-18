<?php

declare(strict_types=1);

namespace Marwa\AI\Support;

use Marwa\AI\Contracts\StreamChunkInterface;
use Marwa\AI\Contracts\ToolCallInterface;

final class StreamChunk implements StreamChunkInterface
{
    private string $accumulated = '';

    public function __construct(
        private string $delta,
        private bool $isFinished = false,
        private ?\Marwa\AI\Contracts\UsageInterface $usage = null,
        private ?string $finishReason = null,
        private array $toolCalls = [],
        private array $metadata = []
    ) {}

    public function getDelta(): string
    {
        return $this->delta;
    }

    public function getAccumulated(): string
    {
        if ($this->accumulated === '') {
            $this->accumulated = $this->delta;
        } else {
            $this->accumulated .= $this->delta;
        }
        return $this->accumulated;
    }

    public function isFinished(): bool
    {
        return $this->isFinished;
    }

    public function getUsage(): ?\Marwa\AI\Contracts\UsageInterface
    {
        return $this->isFinished ? $this->usage : null;
    }

    public function getFinishReason(): ?string
    {
        return $this->isFinished ? $this->finishReason : null;
    }

    public function hasToolCalls(): bool
    {
        return !empty($this->toolCalls);
    }

    public function getToolCalls(): array
    {
        return $this->toolCalls;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
