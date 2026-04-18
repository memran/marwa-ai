<?php

declare(strict_types=1);

namespace Marwa\AI\Contracts;

/**
 * Classification result value object
 */
final class ClassificationResult
{
    public function __construct(
        public readonly string $label,
        public readonly float $confidence,
        public readonly array $allLabels,
        public readonly array $metadata = []
    ) {}

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getConfidence(): float
    {
        return $this->confidence;
    }

    public function getAlternativeLabels(int $limit = 3): array
    {
        $labels = $this->allLabels;
        uasort($labels, fn($a, $b) => $b <=> $a);
        return array_slice($labels, 1, $limit, true);
    }

    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'confidence' => $this->confidence,
            'all_labels' => $this->allLabels,
            'metadata' => $this->metadata,
        ];
    }
}
