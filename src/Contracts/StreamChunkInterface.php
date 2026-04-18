<?php

declare(strict_types=1);

namespace Marwa\AI\Contracts;

/**
 * Represents a streaming chunk from the AI
 */
interface StreamChunkInterface
{
    /**
     * Get the text delta
     */
    public function getDelta(): string;

    /**
     * Get accumulated content so far
     */
    public function getAccumulated(): string;

    /**
     * Check if this is the final chunk
     */
    public function isFinished(): bool;

    /**
     * Get usage information (on final chunk)
     */
    public function getUsage(): ?UsageInterface;

    /**
     * Get the reason generation stopped
     */
    public function getFinishReason(): ?string;

    /**
     * Check if contains tool calls
     */
    public function hasToolCalls(): bool;

    /**
     * Get tool calls (on final chunk)
     */
    public function getToolCalls(): array;
}
