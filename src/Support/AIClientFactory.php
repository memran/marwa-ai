<?php

declare(strict_types=1);

namespace Marwa\AI\Support;

use Marwa\AI\Contracts\AIClientInterface;
use Marwa\AI\Exceptions\ConfigurationException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class AIClientFactory
{
    /** @var array<string, mixed> */
    private array $config;

    private LoggerInterface $logger;

    /** @var array<string, string> */
    private const DRIVER_MAP = [
        'openai' => \Marwa\AI\Drivers\OpenAIClient::class,
        'anthropic' => \Marwa\AI\Drivers\AnthropicClient::class,
        'google' => \Marwa\AI\Drivers\GoogleAIClient::class,
        'grok' => \Marwa\AI\Drivers\GrokClient::class,
        'xai' => \Marwa\AI\Drivers\GrokClient::class,
        'mistral' => \Marwa\AI\Drivers\MistralClient::class,
        'deepseek' => \Marwa\AI\Drivers\DeepSeekClient::class,
        'ollama' => \Marwa\AI\Drivers\OllamaClient::class,
    ];

    public function __construct(array $config = [], ?LoggerInterface $logger = null)
    {
        $this->config = $config;
        $this->logger = $logger ?? new NullLogger();
    }

    public function make(string $provider): AIClientInterface
    {
        $provider = strtolower($provider);

        if (!isset(self::DRIVER_MAP[$provider])) {
            throw new ConfigurationException("Provider '{$provider}' is not supported.");
        }

        $driverClass = self::DRIVER_MAP[$provider];
        $config = $this->config['providers'][$provider] ?? $this->config[$provider] ?? [];
        $config['logger'] = $this->logger;

        return new $driverClass($config);
    }

    public function getAvailableProviders(): array
    {
        return array_keys(self::DRIVER_MAP);
    }
}
