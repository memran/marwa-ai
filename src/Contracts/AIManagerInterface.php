<?php

declare(strict_types=1);

namespace Marwa\AI\Contracts;

use Stringable;

/**
 * Main manager interface for AI operations
 */
interface AIManagerInterface extends \Stringable
{
    /**
     * Create a new chat conversation
     */
    public function conversation(string | array $messages = [], array $options = []): ConversationInterface;

    /**
     * Get a provider-specific client
     */
    public function driver(string $provider = 'openai'): AIClientInterface;

    /**
     * Register a custom driver
     */
    public function extend(string $name, callable $callback): void;

    /**
     * Get list of available providers
     *
     * @return array<string>
     */
    public function getAvailableProviders(): array;

    /**
     * Get the default provider
     */
    public function getDefaultProvider(): string;

    /**
     * Set default provider
     */
    public function setDefaultProvider(string $provider): void;

    /**
     * Configure a provider globally
     */
    public function configure(string $provider, array $config): void;

    /**
     * Create a prompt template
     */
    public function prompt(string $template, array $variables = []): PromptTemplateInterface;

    /**
     * Cache a prompt template
     */
    public function cache(string $name, callable $callback, int $ttl = 3600): mixed;

    /**
     * Create a chain of prompts
     */
    public function chain(array $steps, array $context = []): ChainInterface;

    /**
     * Register a tool/function
     */
    public function tool(ToolDefinitionInterface $tool): void;

    /**
     * Get registered tools
     *
     * @return array<string, ToolDefinitionInterface>
     */
    public function getTools(): array;

    /**
     * Register an MCP server
     */
    public function registerMcpServer(string $name, MCPServerInterface $server): void;

    /**
     * Get batch processor
     */
    public function batch(): BatchProcessorInterface;

    /**
     * Get conversation memory manager
     */
    public function memory(): MemoryManagerInterface;

    /**
     * Create a text classifier
     */
    public function classifier(): ClassifierInterface;

    /**
     * Generate text with structured output
     */
    public function structured(
        string $schema,
        array $messages,
        array $options = []
    ): StructuredResponseInterface;

    /**
     * Check provider health
     */
    public function health(): HealthCheckInterface;

    /**
     * Send a simple text completion request
     */
    public function complete(string $prompt, array $options = []): AIResponseInterface;

    /**
     * Create a fluent chat builder
     */
    public function chat(string $provider = null): \Marwa\AI\Support\ChatBuilder;

    /**
     * Get statistics/metrics
     */
    public function stats(): array;
}
