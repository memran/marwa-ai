<?php

declare(strict_types=1);

namespace Marwa\AI\Support;

use Marwa\AI\Contracts\ToolCallInterface;

final class ToolCall implements ToolCallInterface
{
    public function __construct(
        private string $id,
        private string $toolName,
        private array $arguments,
        private ?string $rawArguments = null,
        private mixed $callback = null
    ) {}

    public static function fromArray(array $data, ?callable $callback = null): self
    {
        return new self(
            $data['id'] ?? uniqid('tool_'),
            $data['function']['name'],
            json_decode($data['function']['arguments'], true) ?: [],
            $data['function']['arguments'],
            $callback
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getToolName(): string
    {
        return $this->toolName;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function getRawArguments(): string
    {
        return $this->rawArguments ?? json_encode($this->arguments);
    }

    public function execute(): mixed
    {
        if ($this->callback === null) {
            throw new \RuntimeException("No callback registered for tool '{$this->toolName}'");
        }

        return call_user_func_array($this->callback, [$this->arguments, $this]);
    }

    public function setCallback(callable $callback): void
    {
        $this->callback = $callback;
    }

    public function __toString(): string
    {
        return $this->toolName;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tool' => $this->toolName,
            'arguments' => $this->arguments,
        ];
    }
}
