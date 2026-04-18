<?php

declare(strict_types=1);

namespace Marwa\AI\Contracts;

/**
 * Sentiment analysis result
 */
final class SentimentResult
{
    public const POSITIVE = 'positive';
    public const NEGATIVE = 'negative';
    public const NEUTRAL = 'neutral';
    public const MIXED = 'mixed';

    public function __construct(
        public readonly string $sentiment,
        public readonly float $score,
        public readonly float $magnitude,
        public readonly array $emotions = []
    ) {}

    public function isPositive(): bool
    {
        return $this->sentiment === self::POSITIVE;
    }

    public function isNegative(): bool
    {
        return $this->sentiment === self::NEGATIVE;
    }

    public function getSentimentScore(): float
    {
        return match ($this->sentiment) {
            self::POSITIVE => $this->score,
            self::NEGATIVE => -$this->score,
            default => 0.0,
        };
    }

    public function toArray(): array
    {
        return [
            'sentiment' => $this->sentiment,
            'score' => $this->score,
            'magnitude' => $this->magnitude,
            'emotions' => $this->emotions,
        ];
    }
}
