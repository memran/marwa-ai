<?php

declare(strict_types=1);

namespace Marwa\AI;

use Marwa\AI\Contracts\AIManagerInterface;
use Marwa\AI\Support\Traits\DelegatesToManager;

/**
 * Application entry point for standalone usage
 */
class Application implements AIManagerInterface
{
    use DelegatesToManager;

    private AIManager $manager;

    public function __construct(array $config)
    {
        $this->manager = new AIManager($config);
    }

    public static function make(array $config = []): self
    {
        return new self($config);
    }

    /**
     * Get the underlying manager
     */
    public function getManager(): AIManager
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
