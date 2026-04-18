<?php

declare(strict_types=1);

namespace Marwa\AI\Support;

use Marwa\AI\Contracts\EmbeddingResponseInterface;
use Marwa\AI\Contracts\UsageInterface;

final class EmbeddingResponse implements EmbeddingResponseInterface
{
    /** @var array<array<float>> */
    private array $embeddings;

    public function __construct(
        private array $rawEmbeddings,
        private string $model,
        private UsageInterface $usage
    ) {
        $this->embeddings = $this->normalizeEmbeddings($rawEmbeddings);
    }

    private function normalizeEmbeddings(array $raw): array
    {
        $result = [];
        foreach ($raw as $embedding) {
            if (is_object($embedding) && isset($embedding->embedding)) {
                $result[] = $embedding->embedding;
            } elseif (is_array($embedding) && isset($embedding['embedding'])) {
                $result[] = $embedding['embedding'];
            } elseif (is_array($embedding)) {
                $result[] = $embedding;
            } else {
                $result[] = (array) $embedding;
            }
        }
        return $result;
    }

    public function getEmbeddings(): array
    {
        return $this->embeddings;
    }

    public function getEmbedding(int $index): array
    {
        return $this->embeddings[$index] ?? throw new \OutOfBoundsException("No embedding at index {$index}");
    }

    public function getDimensions(): int
    {
        return count($this->embeddings[0] ?? []);
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function getUsage(): UsageInterface
    {
        return $this->usage;
    }

    public function getRawResponse(): mixed
    {
        return $this->rawEmbeddings;
    }

    public static function similarity(array $a, array $b): float
    {
        if (count($a) !== count($b)) {
            return 0.0;
        }

        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        foreach ($a as $i => $val) {
            $dotProduct += $val * $b[$i];
            $normA += $val * $val;
            $normB += $b[$i] * $b[$i];
        }

        $normA = sqrt($normA);
        $normB = sqrt($normB);

        if ($normA == 0.0 || $normB == 0.0) {
            return 0.0;
        }

        return $dotProduct / ($normA * $normB);
    }
}
