<?php

declare(strict_types=1);

namespace Marwa\AI;

use League\Container\ServiceProvider\AbstractServiceProvider;
use Marwa\AI\Contracts\AIManagerInterface;

class AiServiceProvider extends AbstractServiceProvider
{
    public function provides(string $id): bool
    {
        return in_array($id, [
            AIManagerInterface::class,
            AIManager::class,
            'ai',
        ], true);
    }

    public function register(): void
    {
        $this->getContainer()->addShared(AIManagerInterface::class, function () {
            // In a real Marwa app, we would get config from the container
            // For now, we'll use a default or empty config
            $config = []; 
            if (file_exists($path = 'config/ai.php')) {
                $config = require $path;
            }
            
            return new AIManager($config);
        });

        $this->getContainer()->addShared(AIManager::class, AIManagerInterface::class);
        $this->getContainer()->addShared('ai', AIManagerInterface::class);
    }
}
