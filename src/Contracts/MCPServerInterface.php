<?php

declare(strict_types=1);

namespace Marwa\AI\Contracts;

/**
 * Model Context Protocol server interface
 */
interface MCPServerInterface
{
    /**
     * Get server name
     */
    public function getName(): string;

    /**
     * Get server version
     */
    public function getVersion(): string;

    /**
     * Get available tools/resources
     *
     * @return array<array{name: string, description: string, inputSchema: array}>
     */
    public function listTools(): array;

    /**
     * Get server capabilities
     */
    public function getCapabilities(): MCPServerCapabilities;

    /**
     * Handle a tool call request
     */
    public function handleToolCall(string $toolName, array $arguments): MCPResponse;

    /**
     * Get server info
     */
    public function getInfo(): MCPServerInfo;

    /**
     * Start the server
     */
    public function start(): void;

    /**
     * Stop the server
     */
    public function stop(): void;
}
