<?php

declare(strict_types=1);

namespace Marwa\AI\Contracts;

/**
 * MCP Server Capabilities
 */
final class MCPServerCapabilities
{
    public function __construct(
        public readonly bool $tools = true,
        public readonly bool $resources = false,
        public readonly bool $prompts = false,
        public readonly bool $logging = false,
        public readonly bool $experimental = false,
        public readonly ?int $maxBatchSize = null,
    ) {}

    public function toArray(): array
    {
        $capabilities = [];

        if ($this->tools) {
            $capabilities['tools'] = [];
        }
        if ($this->resources) {
            $capabilities['resources'] = [];
        }
        if ($this->prompts) {
            $capabilities['prompts'] = [];
        }

        if ($this->logging) {
            $capabilities['logging'] = [];
        }

        if ($this->experimental) {
            $capabilities['experimental'] = [];
        }

        if ($this->maxBatchSize !== null) {
            $capabilities['maxBatchSize'] = $this->maxBatchSize;
        }

        return $capabilities;
    }
}
