<?php

declare(strict_types=1);

namespace Marwa\AI;

use Marwa\AI\Contracts\AIClientInterface;
use Marwa\AI\Contracts\AIManagerInterface;
use Marwa\AI\Contracts\AIResponseInterface;
use Marwa\AI\Contracts\BatchProcessorInterface;
use Marwa\AI\Contracts\ChainInterface;
use Marwa\AI\Contracts\ClassifierInterface;
use Marwa\AI\Contracts\ConversationInterface;
use Marwa\AI\Contracts\HealthCheckInterface;
use Marwa\AI\Contracts\MCPServerInterface;
use Marwa\AI\Contracts\MemoryManagerInterface;
use Marwa\AI\Contracts\PromptTemplateInterface;
use Marwa\AI\Contracts\StructuredResponseInterface;
use Marwa\AI\Contracts\ToolDefinitionInterface;
use Marwa\AI\Support\AIClientFactory;
use Marwa\AI\Support\BatchProcessor;
use Marwa\AI\Support\Chain;
use Marwa\AI\Support\ChatBuilder;
use Marwa\AI\Support\Classifier;
use Marwa\AI\Support\Conversation;
use Marwa\AI\Support\HealthChecker;
use Marwa\AI\Support\MemoryManager;
use Marwa\AI\Support\PromptTemplate;
use Marwa\AI\Support\StructuredResponse;
use Marwa\AI\Support\ToolDefinition as SupportToolDefinition;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;

class AIManager implements AIManagerInterface
{
    /** @var array<string, AIClientInterface> */
    private array $drivers = [];

    /** @var array<string, ToolDefinitionInterface> */
    private array $tools = [];

    /** @var array<string, mixed> */
    private array $config = [];

    /** @var array<string, mixed> */
    private array $context = [];

    private AIClientFactory $factory;
    private ?EventDispatcherInterface $dispatcher;
    private LoggerInterface $logger;

    public function __construct(
        array $config = [],
        ?EventDispatcherInterface $dispatcher = null,
        ?LoggerInterface $logger = null
    ) {
        $this->config = $config;
        $this->dispatcher = $dispatcher;
        $this->logger = $logger ?? new NullLogger();
        $this->factory = new AIClientFactory($config, $this->logger);
    }

    public function getDispatcher(): ?EventDispatcherInterface
    {
        return $this->dispatcher;
    }

    public function conversation(string | array $messages = [], array $options = []): ConversationInterface
    {
        $conv = new Conversation($messages);
        $conv->setClient($this->driver($options['provider'] ?? $this->getDefaultProvider()));
        return $conv->withContext($options['context'] ?? $this->context);
    }

    public function driver(string $provider = 'ollama'): AIClientInterface
    {
        $provider = strtolower($provider);

        if (isset($this->drivers[$provider])) {
            return $this->drivers[$provider];
        }

        if (!isset($this->drivers[$provider])) {
            $this->drivers[$provider] = $this->factory->make($provider);
        }

        return $this->drivers[$provider];
    }

    public function extend(string $name, callable $callback): void
    {
        $this->drivers[$name] = $callback($this->config[$name] ?? []);
    }

    public function getAvailableProviders(): array
    {
        return $this->factory->getAvailableProviders();
    }

    public function getDefaultProvider(): string
    {
        return $this->config['default'] ?? 'ollama';
    }

    public function setDefaultProvider(string $provider): void
    {
        $this->config['default'] = $provider;
    }

    public function configure(string $provider, array $config): void
    {
        $this->config[$provider] = [...($this->config[$provider] ?? []), ...$config];
    }

    public function prompt(string $template, array $variables = []): PromptTemplateInterface
    {
        return new PromptTemplate($template, $variables);
    }

    public function cache(string $name, callable $callback, int $ttl = 3600): mixed
    {
        static $cache = [];

        if (isset($cache[$name]) && $cache[$name]['expires'] > time()) {
            return $cache[$name]['value'];
        }

        $value = $callback();
        $cache[$name] = ['value' => $value, 'expires' => time() + $ttl];

        return $value;
    }

    public function chain(array $steps, array $context = []): ChainInterface
    {
        return new Chain($steps, $context);
    }

    public function embed(array $texts, array $options = []): \Marwa\AI\Contracts\EmbeddingResponseInterface
    {
        $client = $this->driver($options['provider'] ?? $this->getDefaultProvider());
        return $client->embed($texts, $options);
    }

    public function tool(ToolDefinitionInterface $tool): void
    {
        $this->tools[$tool->getName()] = $tool;
    }

    public function getTools(): array
    {
        return $this->tools;
    }

    public function registerMcpServer(string $name, \Marwa\AI\Contracts\MCPServerInterface $server): void
    {
        $this->tools += $this->convertMcpTools($server);
    }

    private function convertMcpTools(\Marwa\AI\Contracts\MCPServerInterface $server): array
    {
        $tools = [];
        foreach ($server->listTools() as $tool) {
            $tools[$tool['name']] = new SupportToolDefinition(
                $tool['name'],
                $tool['description'],
                $tool['inputSchema'],
                fn($args, $call) => $server->handleToolCall($tool['name'], $args)
            );
        }
        return $tools;
    }

    public function batch(): BatchProcessorInterface
    {
        return new BatchProcessor($this);
    }

    public function memory(): MemoryManagerInterface
    {
        return new MemoryManager($this->config['memory'] ?? []);
    }

    public function classifier(): ClassifierInterface
    {
        return new Classifier($this->driver());
    }

    public function structured(
        string $schema,
        array $messages,
        array $options = []
    ): StructuredResponseInterface {
        $options['response_format'] = ['type' => 'json_object', 'schema' => $schema];
        $client = $this->driver($options['provider'] ?? $this->getDefaultProvider());
        $response = $client->completion($messages, $options);

        $parsed = json_decode($response->getContent(), true);
        $errors = json_last_error() !== JSON_ERROR_NONE
            ? [json_last_error_msg()]
            : [];

        return new StructuredResponse(
            $parsed ?: [],
            $errors,
            $response->getContent(),
            ['model' => $response->getModel()]
        );
    }

    public function health(): HealthCheckInterface
    {
        return new HealthChecker($this->driver());
    }

    public function complete(string $prompt, array $options = []): AIResponseInterface
    {
        return $this->conversation($prompt)->send($options);
    }

    public function chat(?string $provider = null): ChatBuilder
    {
        $builder = new ChatBuilder();
        $builder->setClient($this->driver($provider ?? $this->getDefaultProvider()));
        return $builder;
    }

    public function stats(): array
    {
        return [
            'providers' => $this->getAvailableProviders(),
            'default' => $this->getDefaultProvider(),
            'registered_tools' => count($this->tools),
            'active_drivers' => count($this->drivers),
        ];
    }

    public function __toString(): string
    {
        return 'Marwa AI Manager - Default provider: ' . $this->getDefaultProvider();
    }
}
