<?php

declare(strict_types=1);

namespace Marwa\AI\Contracts;

interface UsageInterface
{
    /**
     * Get total tokens used
     */
    public function getTotalTokens(): int;

    /**
     * Get prompt tokens
     */
    public function getPromptTokens(): int;

    /**
     * Get completion tokens
     */
    public function getCompletionTokens(): int;

    /**
     * Calculate cost in USD
     */
    public function getCost(): float;

    /**
     * Get detailed breakdown
     */
    public function toArray(): array;
}
