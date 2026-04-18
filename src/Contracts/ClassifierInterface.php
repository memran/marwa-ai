<?php

declare(strict_types=1);

namespace Marwa\AI\Contracts;

/**
 * Text classification interface
 */
interface ClassifierInterface
{
    /**
     * Single-label classification
     */
    public function classify(string $text, array $categories, array $options = []): ClassificationResult;

    /**
     * Multi-label classification
     */
    public function classifyMulti(string $text, array $categories, array $options = []): array;

    /**
     * Sentiment analysis
     */
    public function sentiment(string $text, array $options = []): SentimentResult;

    /**
     * Intent detection
     */
    public function detectIntent(string $text, array $intents, array $options = []): IntentResult;

    /**
     * Entity extraction
     */
    public function extractEntities(string $text, array $entityTypes, array $options = []): array;

    /**
     * Zero-shot classification
     */
    public function zeroShot(string $text, array $labels, string $hypothesisTemplate = ''): ClassificationResult;

    /**
     * Custom classifier with examples (few-shot)
     */
    public function fewShot(
        string $text,
        array $examples,
        array $options = []
    ): ClassificationResult;
}
