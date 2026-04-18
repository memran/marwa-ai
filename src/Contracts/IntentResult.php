<?php

declare(strict_types=1);

namespace Marwa\AI\Contracts;

/**
 * Intent detection result
 */
final class IntentResult
{
    public function __construct(
        public readonly string $intent,
        public readonly float $confidence,
        public readonly array $parameters = [],
        public readonly array $allIntents = []
    ) {}

    public function getIntent(): string
    {
        return $this->intent;
    }

    public function getConfidence(): float
    {
        return $this->confidence;
    }

    public function getParameter(string $name, mixed $default = null): mixed
    {
        return $this->parameters[$name] ?? $default;
    }

    public function toArray(): array
    {
        return [
            'intent' => $this->intent,
            'confidence' => $this->confidence,
            'parameters' => $this->parameters,
            'all_intents' => $this->allIntents,
        ];
    }
}
