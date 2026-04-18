<?php

declare(strict_types=1);

namespace Marwa\AI\Contracts;

interface ImageResponseInterface
{
    /**
     * Get generated image URLs
     *
     * @return array<string>
     */
    public function getUrls(): array;

    /**
     * Get base64 encoded images
     *
     * @return array<string>
     */
    public function getBase64(): array;

    /**
     * Save images to disk
     *
     * @return array<string> Saved file paths
     */
    public function save(string $directory, string $prefix = 'image_'): array;

    /**
     * Get revised prompt if available
     */
    public function getRevisedPrompt(): ?string;

    /**
     * Get model used
     */
    public function getModel(): string;

    /**
     * Get provider response size info
     */
    public function getSize(): ?string;

    /**
     * Get provider response quality info
     */
    public function getQuality(): ?string;

    /**
     * Get usage statistics
     */
    public function getUsage(): UsageInterface;

    /**
     * Get raw provider response
     */
    public function getRawResponse(): mixed;
}
