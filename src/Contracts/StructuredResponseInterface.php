<?php

declare(strict_types=1);

namespace Marwa\AI\Contracts;

interface StructuredResponseInterface
{
    /**
     * Get the structured data
     */
    public function getData(): mixed;

    /**
     * Get validation errors if any
     */
    public function getErrors(): array;

    /**
     * Check if data is valid
     */
    public function isValid(): bool;

    /**
     * Get the original text response
     */
    public function getRawText(): string;

    /**
     * Get extraction metadata
     */
    public function getMetadata(): array;
}
