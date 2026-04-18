<?php

declare(strict_types=1);

namespace Marwa\AI\Tests\Unit;

use Marwa\AI\Support\Conversation;
use Marwa\AI\Tests\Helpers\MockClient;
use PHPUnit\Framework\TestCase;

class ConversationTest extends TestCase
{
    public function test_can_add_messages(): void
    {
        $conv = new Conversation();
        $conv->user('Hello')->assistant('Hi!')->system('Be helpful');

        $messages = $conv->getMessages();
        $this->assertCount(3, $messages);
    }

    public function test_can_send_with_mock_client(): void
    {
        $client = new MockClient(['Hello!']);
        $conv = (new Conversation('Start'))->setClient($client);

        $response = $conv->send();
        $this->assertEquals('Hello!', $response->getContent());
    }

    public function test_can_fork_conversation(): void
    {
        $conv = (new Conversation())->user('Hello')->assistant('Hi');
        $fork = $conv->fork();

        $fork->user('How are you?');
        $this->assertCount(2, $conv->getMessages());
        $this->assertCount(3, $fork->getMessages());
    }

    public function test_can_clear_messages(): void
    {
        $conv = (new Conversation())->user('Hello')->assistant('Hi')->clear();
        $this->assertEmpty($conv->getMessages());
    }

    public function test_can_serialize_and_deserialize(): void
    {
        $conv = (new Conversation())->user('Hello')->assistant('Hi');
        $data = $conv->toArray();

        $restored = Conversation::fromArray($data);
        $this->assertEquals($conv->getId(), $restored->getId());
        $this->assertCount(2, $restored->getMessages());
    }
}
