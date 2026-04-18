<?php

declare(strict_types=1);

namespace Marwa\AI;

use Marwa\AI\Contracts\AIClientInterface;
use Marwa\AI\Contracts\AIManagerInterface;
use Marwa\AI\Contracts\BatchProcessorInterface;
use Marwa\AI\Contracts\ChainInterface;
use Marwa\AI\Contracts\ClassifierInterface;
use Marwa\AI\Contracts\ConversationInterface;
use Marwa\AI\Contracts\HealthCheckInterface;
use Marwa\AI\Contracts\MemoryManagerInterface;
use Marwa\AI\Contracts\PromptTemplateInterface;
use Marwa\AI\Contracts\StructuredResponseInterface;
use Marwa\AI\Contracts\ToolDefinitionInterface;


use Marwa\AI\Support\Traits\DelegatesToManager;


/**
 * Global AI facade with static access.
 * Provides the main entry point for the Marwa AI library.
 *
 * @method static \Marwa\AI\Contracts\ConversationInterface conversation(string|array $messages = [], array $options = [])
 * @method static \Marwa\AI\Contracts\AIClientInterface driver(string $provider = 'openai')
 */
class MarwaAI implements AIManagerInterface
{
    use DelegatesToManager;

    private static ?self $instance = null;

    private AIManager $manager;

    public function __construct(array $config = [])
    {
        $this->manager = new AIManager($config);
    }

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function initialize(array $config): self
    {
        self::$instance = new self($config);
        return self::$instance;
    }

    protected function getManager(): AIManager
    {
        return $this->manager;
    }

    public function __toString(): string
    {
        return (string) $this->manager;
    }

    public function __call(string $method, array $args): mixed
    {
        return $this->manager->$method(...$args);
    }
}
