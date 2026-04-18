<?php

declare(strict_types=1);

namespace Marwa\AI\Contracts;

interface AIClientInterface
{
    /**
     * Send a text completion request
     */
    public function completion(array $messages, array $options = []): AIResponseInterface;

    /**
     * Send a streaming completion request
     */
    public function streamCompletion(array $messages, array $options = []): \Generator;

    /**
     * Generate embeddings for text
     */
    public function embed(array $texts, array $options = []): EmbeddingResponseInterface;

    /**
     * Generate image from text prompt
     */
    public function generateImage(string $prompt, array $options = []): ImageResponseInterface;

    /**
     * Analyze/describe an image
     */
    public function analyzeImage(string $imagePath, string $prompt, array $options = []): AIResponseInterface;

    /**
     * Count tokens in text
     */
    public function countTokens(string $text): int;

    /**
     * Check if the client supports a feature
     */
    public function supports(string $feature): bool;

    /**
     * Get the provider name
     */
    public function getProvider(): string;

    /**
     * Get the model being used
     */
    public function getModel(): string;
}
