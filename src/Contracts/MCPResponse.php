<?php

declare(strict_types=1);

namespace Marwa\AI\Contracts;

/**
 * MCP Response structure
 */
final class MCPResponse
{
    public function __construct(
        public readonly mixed $content,
        public readonly bool $isError = false,
        public readonly ?string $errorMessage = null,
        public readonly array $metadata = []
    ) {}

    public static function success(mixed $content, array $metadata = []): self
    {
        return new self($content, false, null, $metadata);
    }

    public static function error(string $message, array $metadata = []): self
    {
        return new self(null, true, $message, $metadata);
    }

    public function toArray(): array
    {
        if ($this->isError) {
            return [
                'isError' => true,
                'error' => $this->errorMessage,
                'metadata' => $this->metadata,
            ];
        }

        return [
            'content' => $this->content,
            'metadata' => $this->metadata,
        ];
    }
}
