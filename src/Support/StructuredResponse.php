<?php

declare(strict_types=1);

namespace Marwa\AI\Support;

use Marwa\AI\Contracts\StructuredResponseInterface;

final class StructuredResponse implements StructuredResponseInterface
{
    public function __construct(
        private mixed $data,
        private array $errors = [],
        private string $rawText = '',
        private array $metadata = []
    ) {}

    public function getData(): mixed
    {
        return $this->data;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function isValid(): bool
    {
        return empty($this->errors);
    }

    public function getRawText(): string
    {
        return $this->rawText;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function __get(string $name): mixed
    {
        return is_array($this->data) && isset($this->data[$name])
            ? $this->data[$name]
            : null;
    }
}
