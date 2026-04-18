<?php

declare(strict_types=1);

namespace Marwa\AI\Support;

use Marwa\AI\Contracts\UsageInterface;

final class Usage implements UsageInterface
{
    private static array $pricing = [
        'openai' => [
            'gpt-4o' => ['prompt' => 2.50, 'completion' => 10.00],
            'gpt-4-turbo' => ['prompt' => 10.00, 'completion' => 30.00],
            'gpt-4' => ['prompt' => 30.00, 'completion' => 60.00],
            'gpt-3.5-turbo' => ['prompt' => 0.50, 'completion' => 1.50],
        ],
        'anthropic' => [
            'claude-3-opus' => ['prompt' => 15.00, 'completion' => 75.00],
            'claude-3-sonnet' => ['prompt' => 3.00, 'completion' => 15.00],
            'claude-3-haiku' => ['prompt' => 0.25, 'completion' => 1.25],
        ],
        'google' => [
            'gemini-pro' => ['prompt' => 0.125, 'completion' => 0.375],
            'gemini-ultra' => ['prompt' => 1.25, 'completion' => 3.75],
        ],
        'mistral' => [
            'mistral-large' => ['prompt' => 2.00, 'completion' => 6.00],
            'mistral-medium' => ['prompt' => 0.80, 'completion' => 2.40],
        ],
    ];

    public function __construct(
        public readonly int $promptTokens,
        public readonly int $completionTokens,
        public readonly string $provider,
        public readonly string $model
    ) {}

    public function getTotalTokens(): int
    {
        return $this->promptTokens + $this->completionTokens;
    }

    public function getPromptTokens(): int
    {
        return $this->promptTokens;
    }

    public function getCompletionTokens(): int
    {
        return $this->completionTokens;
    }

    public function getCost(): float
    {
        $pricing = self::$pricing[$this->provider][$this->model] ?? null;
        if ($pricing === null) {
            return 0.0;
        }

        $promptCost = ($this->promptTokens / 1000) * $pricing['prompt'];
        $completionCost = ($this->completionTokens / 1000) * $pricing['completion'];

        return round($promptCost + $completionCost, 6);
    }

    public function toArray(): array
    {
        return [
            'prompt_tokens' => $this->promptTokens,
            'completion_tokens' => $this->completionTokens,
            'total_tokens' => $this->getTotalTokens(),
            'cost' => $this->getCost(),
            'model' => $this->model,
        ];
    }

    public static function setPricing(string $provider, string $model, array $prices): void
    {
        self::$pricing[$provider][$model] = $prices;
    }
}
