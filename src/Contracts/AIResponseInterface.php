<?php

declare(strict_types=1);

namespace Marwa\AI\Contracts;

use Stringable;

interface AIResponseInterface extends \Stringable
{
    /**
     * Get the generated text content
     */
    public function getContent(): string;

    /**
     * Get token usage statistics
     */
    public function getUsage(): UsageInterface;

    /**
     * Get the model that generated this response
     */
    public function getModel(): string;

    /**
     * Get the finish reason
     */
    public function getFinishReason(): ?string;

    /**
     * Get raw provider response
     */
    public function getRawResponse(): mixed;

    /**
     * Check if response contains tool calls
     */
    public function hasToolCalls(): bool;

    /**
     * Get tool calls if present
     *
     * @return array<ToolCallInterface>
     */
    public function getToolCalls(): array;

    /**
     * Parse response as structured data (JSON/XML)
     */
    public function parseAs(string $format = 'json'): mixed;

    /**
     * Convert to array
     */
    public function toArray(): array;
}
