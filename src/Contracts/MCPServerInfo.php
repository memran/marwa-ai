<?php

declare(strict_types=1);

namespace Marwa\AI\Contracts;

/**
 * MCP Server Info
 */
final class MCPServerInfo
{
    public function __construct(
        public readonly string $name,
        public readonly string $version,
        public readonly string $description,
        public readonly array $tools = [],
        public readonly array $resources = [],
        public readonly array $prompts = []
    ) {}

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'version' => $this->version,
            'description' => $this->description,
            'tools' => $this->tools,
            'resources' => $this->resources,
            'prompts' => $this->prompts,
        ];
    }
}
