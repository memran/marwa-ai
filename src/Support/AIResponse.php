<?php

declare(strict_types=1);

namespace Marwa\AI\Support;

use Marwa\AI\Contracts\AIResponseInterface;
use Marwa\AI\Contracts\ToolCallInterface;
use Marwa\AI\Contracts\UsageInterface;
use Stringable;

final class AIResponse implements AIResponseInterface, \Stringable
{
    private array $toolCalls = [];
    private ?array $parsedCache = null;

    public function __construct(
        public readonly string $content,
        public readonly UsageInterface $usage,
        public readonly string $model,
        public readonly ?string $finishReason = null,
        public readonly mixed $raw = null,
        public readonly array $options = []
    ) {}

    public function getContent(): string
    {
        return $this->content;
    }

    public function getUsage(): UsageInterface
    {
        return $this->usage;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function getFinishReason(): ?string
    {
        return $this->finishReason;
    }

    public function getRawResponse(): mixed
    {
        return $this->raw;
    }

    public function hasToolCalls(): bool
    {
        return !empty($this->toolCalls);
    }

    /**
     * Get tool calls if present
     *
     * @return array<ToolCallInterface>
     */
    public function getToolCalls(): array
    {
        return $this->toolCalls;
    }

    public function addToolCall(ToolCallInterface $toolCall): void
    {
        $this->toolCalls[] = $toolCall;
    }

    public function __toString(): string
    {
        return $this->content;
    }

    public function toArray(): array
    {
        return [
            'content' => $this->content,
            'model' => $this->model,
            'finish_reason' => $this->finishReason,
            'usage' => $this->usage->toArray(),
            'tool_calls' => array_map(fn($c) => $c instanceof ToolCallInterface ? $c->toArray() : $c, $this->toolCalls),
        ];
    }

    public function parseAs(string $format = 'json'): mixed
    {
        if ($this->parsedCache !== null) {
            return $this->parsedCache;
        }

        return match ($format) {
            'json' => $this->parsedCache = json_decode($this->content, true),
            'array' => $this->parsedCache = $this->toArray(),
            default => throw new \InvalidArgumentException("Unsupported format: {$format}")
        };
    }
}
