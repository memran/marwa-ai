<?php

declare(strict_types=1);

namespace Marwa\AI;

use Marwa\AI\Contracts\AIClientInterface;
use Marwa\AI\Contracts\AIManagerInterface;
use Marwa\AI\Contracts\AIResponseInterface;
use Marwa\AI\Contracts\BatchProcessorInterface;
use Marwa\AI\Contracts\ClassifierInterface;
use Marwa\AI\Contracts\ConversationInterface;
use Marwa\AI\Contracts\EmbeddingResponseInterface;
use Marwa\AI\Contracts\HealthCheckInterface;
use Marwa\AI\Contracts\ImageResponseInterface;
use Marwa\AI\Contracts\MemoryManagerInterface;
use Marwa\AI\Contracts\PromptTemplateInterface;
use Marwa\AI\Contracts\StructuredResponseInterface;
use Marwa\AI\Contracts\ToolDefinitionInterface;
use Marwa\AI\Support\ChatBuilder;
use Marwa\AI\Support\Conversation;
use Marwa\AI\Support\EmbeddingResponse;
use Marwa\AI\Support\HealthChecker;
use Marwa\AI\Support\ToolDefinition;

/**
 * Get the AI manager instance or a specific driver
 */
function ai(?string $provider = null): AIManagerInterface|AIClientInterface
{
    /** @var AIManagerInterface $manager */
    $manager = MarwaAI::instance();

    if ($provider !== null) {
        return $manager->driver($provider);
    }

    return $manager;
}

/**
 * Create a prompt template
 */
function prompt(string $template, array $variables = []): PromptTemplateInterface
{
    /** @var AIManagerInterface $instance */
    $instance = MarwaAI::instance();
    return $instance->prompt($template, $variables);
}

/**
 * Start a fluent chat builder
 */
function chat(?string $provider = null): ChatBuilder
{
    /** @var AIManagerInterface $instance */
    $instance = MarwaAI::instance();
    return $instance->chat($provider);
}

/**
 * Start a new conversation
 */
function conversation(string | array $messages = []): ConversationInterface
{
    /** @var AIManagerInterface $instance */
    $instance = MarwaAI::instance();
    return $instance->conversation($messages);
}

/**
 * Generate text completion
 */
function complete(string $prompt, array $options = []): AIResponseInterface
{
    return conversation($prompt)->send($options);
}

/**
 * Stream text completion
 */
function stream(string $prompt, callable $onChunk, array $options = []): void
{
    foreach (conversation($prompt)->stream($options) as $chunk) {
        $onChunk($chunk);
    }
}

/**
 * Generate embeddings
 */
function embed(array $texts, array $options = []): EmbeddingResponseInterface
{
    $provider = $options['provider'] ?? null;
    /** @var AIClientInterface $client */
    $client = ai($provider);
    return $client->embed($texts, $options);
}

/**
 * Generate an image
 */
function image(string $prompt, array $options = []): ImageResponseInterface
{
    $provider = $options['provider'] ?? null;
    /** @var AIClientInterface $client */
    $client = ai($provider);
    return $client->generateImage($prompt, $options);
}

/**
 * Create a conversation from array data
 */
function conversation_from_array(array $data): ConversationInterface
{
    return Conversation::fromArray($data);
}

/**
 * Calculate cosine similarity between two vectors
 */
function cosine_similarity(array $a, array $b): float
{
    return EmbeddingResponse::similarity($a, $b);
}

/**
 * Create a tool definition
 */
function ai_tool(string $name, string $description, array $parameters, callable $callback): ToolDefinitionInterface
{
    return new ToolDefinition($name, $description, $parameters, $callback);
}

/**
 * Create a batch processor
 */
function ai_batch(): BatchProcessorInterface
{
    /** @var AIManagerInterface $instance */
    $instance = MarwaAI::instance();
    return $instance->batch();
}

/**
 * Get AI memory manager
 */
function ai_memory(): MemoryManagerInterface
{
    /** @var AIManagerInterface $instance */
    $instance = MarwaAI::instance();
    return $instance->memory();
}

/**
 * Get AI classifier
 */
function ai_classify(): ClassifierInterface
{
    /** @var AIManagerInterface $instance */
    $instance = MarwaAI::instance();
    return $instance->classifier();
}

/**
 * Create a structured output request
 */
function ai_structured(string $schema, array $messages, array $options = []): StructuredResponseInterface
{
    /** @var AIManagerInterface $instance */
    $instance = MarwaAI::instance();
    return $instance->structured($schema, $messages, $options);
}

/**
 * Check provider health
 */
function ai_health(?string $provider = null): HealthCheckInterface
{
    /** @var AIManagerInterface $manager */
    $manager = MarwaAI::instance();
    $driver = $manager->driver($provider ?? $manager->getDefaultProvider());
    return new HealthChecker($driver);
}
