<?php

declare(strict_types=1);

namespace Marwa\AI\Tests\Unit;

use Marwa\AI\MarwaAI;
use Marwa\AI\Support\AIClientFactory;
use PHPUnit\Framework\TestCase;

class AIManagerTest extends TestCase
{
    public function test_can_create_manager(): void
    {
        $manager = new MarwaAI();
        $this->assertInstanceOf(\Marwa\AI\Contracts\AIManagerInterface::class, $manager);
    }

    public function test_can_list_providers(): void
    {
        $manager = new MarwaAI();
        $providers = $manager->getAvailableProviders();
        $this->assertContains('ollama', $providers);
    }

    public function test_can_set_and_get_default_provider(): void
    {
        $manager = new MarwaAI();
        $manager->setDefaultProvider('ollama');
        $this->assertEquals('ollama', $manager->getDefaultProvider());
    }

    public function test_can_create_conversation(): void
    {
        $manager = new MarwaAI();
        $conv = $manager->conversation('Hello');
        $this->assertInstanceOf(\Marwa\AI\Contracts\ConversationInterface::class, $conv);
        $this->assertCount(1, $conv->getMessages());
    }

    public function test_can_add_tool(): void
    {
        $manager = new MarwaAI();
        $tool = new \Marwa\AI\Support\ToolDefinition(
            'test_tool',
            'A test tool',
            ['type' => 'object', 'properties' => ['input' => ['type' => 'string']]],
            fn() => 'result'
        );
        $manager->tool($tool);

        $tools = $manager->getTools();
        $this->assertArrayHasKey('test_tool', $tools);
        $this->assertEquals('A test tool', $tools['test_tool']->getDescription());
    }

    public function test_factory_creates_clients(): void
    {
        $factory = new AIClientFactory([]);
        $client = $factory->make('ollama');
        $this->assertInstanceOf(\Marwa\AI\Contracts\AIClientInterface::class, $client);
        $this->assertEquals('ollama', $client->getProvider());
    }
}
