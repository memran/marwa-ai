<?php

declare(strict_types=1);

namespace Marwa\AI\Contracts;

/**
 * @phpstan-import-type Vectors from \Marwa\AI\Support\Embedding
 */
interface EmbeddingResponseInterface
{
    /**
     * Get embedding vectors
     *
     * @return array<array<float>>
     */
    public function getEmbeddings(): array;

    /**
     * Get embedding for specific index
     *
     * @return array<float>
     */
    public function getEmbedding(int $index): array;

    /**
     * Get dimensions
     */
    public function getDimensions(): int;

    /**
     * Get model used
     */
    public function getModel(): string;

    /**
     * Get usage statistics
     */
    public function getUsage(): UsageInterface;

    /**
     * Calculate similarity between two embeddings
     */
    public static function similarity(array $a, array $b): float;

    /**
     * Get raw provider response
     */
    public function getRawResponse(): mixed;
}
