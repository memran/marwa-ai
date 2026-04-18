<?php

declare(strict_types=1);

namespace Marwa\AI\Support;

use Marwa\AI\Contracts\ToolDefinitionInterface;

final class ToolDefinition implements ToolDefinitionInterface
{
    public function __construct(
        private string $name,
        private string $description,
        private array $parameters,
        private mixed $callback,
        private bool $required = false
    ) {}

    public static function make(
        string $name,
        string $description,
        array $parameters,
        callable $callback
    ): self {
        return new self($name, $description, $parameters, $callback);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getCallback(): callable
    {
        return $this->callback;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function setRequired(bool $required): self
    {
        $this->required = $required;
        return $this;
    }

    public function toProviderFormat(string $provider): array
    {
        return match ($provider) {
            'openai' => [
                'type' => 'function',
                'function' => [
                    'name' => $this->name,
                    'description' => $this->description,
                    'parameters' => $this->parameters,
                ],
            ],
            'anthropic' => [
                'name' => $this->name,
                'description' => $this->description,
                'input_schema' => $this->parameters,
            ],
            'google' => [
                'name' => $this->name,
                'description' => $this->description,
                'parameters' => $this->parameters,
            ],
            default => throw new \InvalidArgumentException("Provider {$provider} not supported for tool format")
        };
    }
}
