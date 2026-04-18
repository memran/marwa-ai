<?php

declare(strict_types=1);

namespace Marwa\AI\Contracts;

/**
 * Represents a tool/function definition for function calling
 */
interface ToolDefinitionInterface
{
    /**
     * Get tool name
     */
    public function getName(): string;

    /**
     * Get tool description
     */
    public function getDescription(): string;

    /**
     * Get JSON Schema for parameters
     */
    public function getParameters(): array;

    /**
     * Get the callable to execute
     */
    public function getCallback(): callable;

    /**
     * Convert to provider format
     */
    public function toProviderFormat(string $provider): array;

    /**
     * Check if tool is required
     */
    public function isRequired(): bool;
}
