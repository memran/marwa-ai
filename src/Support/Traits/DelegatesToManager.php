<?php

declare(strict_types=1);

namespace Marwa\AI\Support\Traits;

use Marwa\AI\Contracts\AIClientInterface;
use Marwa\AI\Contracts\AIResponseInterface;
use Marwa\AI\Contracts\BatchProcessorInterface;
use Marwa\AI\Contracts\ChainInterface;
use Marwa\AI\Contracts\ClassifierInterface;
use Marwa\AI\Contracts\ConversationInterface;
use Marwa\AI\Contracts\HealthCheckInterface;
use Marwa\AI\Contracts\MemoryManagerInterface;
use Marwa\AI\Contracts\PromptTemplateInterface;
use Marwa\AI\Contracts\StructuredResponseInterface;
use Marwa\AI\Contracts\ToolDefinitionInterface;
use Marwa\AI\Contracts\MCPServerInterface;

trait DelegatesToManager
{
    abstract protected function getManager(): \Marwa\AI\AIManager;

    public function conversation(string | array $messages = [], array $options = []): ConversationInterface
    {
        return $this->getManager()->conversation($messages, $options);
    }

    public function driver(string $provider = 'ollama'): AIClientInterface
    {
        return $this->getManager()->driver($provider);
    }

    public function extend(string $name, callable $callback): void
    {
        $this->getManager()->extend($name, $callback);
    }

    public function getAvailableProviders(): array
    {
        return $this->getManager()->getAvailableProviders();
    }

    public function getDefaultProvider(): string
    {
        return $this->getManager()->getDefaultProvider();
    }

    public function setDefaultProvider(string $provider): void
    {
        $this->getManager()->setDefaultProvider($provider);
    }

    public function configure(string $provider, array $config): void
    {
        $this->getManager()->configure($provider, $config);
    }

    public function prompt(string $template, array $variables = []): PromptTemplateInterface
    {
        return $this->getManager()->prompt($template, $variables);
    }

    public function cache(string $name, callable $callback, int $ttl = 3600): mixed
    {
        return $this->getManager()->cache($name, $callback, $ttl);
    }

    public function chain(array $steps, array $context = []): ChainInterface
    {
        return $this->getManager()->chain($steps, $context);
    }

    public function embed(array $texts, array $options = []): \Marwa\AI\Contracts\EmbeddingResponseInterface
    {
        return $this->getManager()->embed($texts, $options);
    }

    public function tool(ToolDefinitionInterface $tool): void
    {
        $this->getManager()->tool($tool);
    }

    public function getTools(): array
    {
        return $this->getManager()->getTools();
    }

    public function registerMcpServer(string $name, MCPServerInterface $server): void
    {
        $this->getManager()->registerMcpServer($name, $server);
    }

    public function batch(): BatchProcessorInterface
    {
        return $this->getManager()->batch();
    }

    public function memory(): MemoryManagerInterface
    {
        return $this->getManager()->memory();
    }

    public function classifier(): ClassifierInterface
    {
        return $this->getManager()->classifier();
    }

    public function structured(
        string $schema,
        array $messages,
        array $options = []
    ): StructuredResponseInterface {
        return $this->getManager()->structured($schema, $messages, $options);
    }

    public function health(): HealthCheckInterface
    {
        return $this->getManager()->health();
    }

    public function complete(string $prompt, array $options = []): AIResponseInterface
    {
        return $this->getManager()->complete($prompt, $options);
    }

    public function chat(?string $provider = null): \Marwa\AI\Support\ChatBuilder
    {
        return $this->getManager()->chat($provider);
    }

    public function stats(): array
    {
        return $this->getManager()->stats();
    }
}
