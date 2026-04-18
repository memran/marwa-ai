<?php

declare(strict_types=1);

namespace Marwa\AI\Tests\Unit;

use Marwa\AI\Support\Message;
use PHPUnit\Framework\TestCase;

class MessageTest extends TestCase
{
    public function test_can_create_user_message(): void
    {
        $msg = Message::user('Hello');
        $this->assertEquals('user', $msg->getRole());
        $this->assertEquals('Hello', $msg->getContent());
    }

    public function test_can_create_system_message(): void
    {
        $msg = Message::system('You are helpful');
        $this->assertEquals('system', $msg->getRole());
    }

    public function test_can_create_tool_message(): void
    {
        $msg = Message::tool('call_123', 'Result data', 'search_tool');
        $this->assertEquals('tool', $msg->getRole());
        $this->assertEquals('call_123', $msg->getToolCallId());
        $this->assertEquals('search_tool', $msg->getToolName());
    }

    public function test_can_serialize_to_array(): void
    {
        $msg = Message::user('Test');
        $data = $msg->toArray();
        $this->assertEquals('user', $data['role']);
        $this->assertEquals('Test', $data['content']);
    }

    public function test_can_create_from_array(): void
    {
        $data = ['role' => 'assistant', 'content' => 'Hi there'];
        $msg = Message::fromArray($data);
        $this->assertEquals('assistant', $msg->getRole());
        $this->assertEquals('Hi there', $msg->getContent());
    }
}
