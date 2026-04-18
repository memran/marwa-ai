<?php

declare(strict_types=1);

namespace Marwa\AI\Support;

use Marwa\AI\Contracts\AIClientInterface;
use Marwa\AI\Contracts\AIResponseInterface;
use Marwa\AI\Contracts\ConversationInterface;

class ChatBuilder
{
    private ConversationInterface $conversation;
    private array $options = [];

    public function __construct(
        ?ConversationInterface $conversation = null
    ) {
        $this->conversation = $conversation ?? new Conversation();
    }

    public function setClient(AIClientInterface $client): self
    {
        $this->conversation->setClient($client);
        return $this;
    }

    public function system(string $content): self
    {
        $this->conversation->system($content);
        return $this;
    }

    public function user(string $content, array $options = []): self
    {
        $this->conversation->user($content, $options);
        return $this;
    }

    public function assistant(string $content): self
    {
        $this->conversation->assistant($content);
        return $this;
    }

    public function model(string $model): self
    {
        $this->options['model'] = $model;
        return $this;
    }

    public function temperature(float $value): self
    {
        $this->options['temperature'] = $value;
        return $this;
    }

    public function maxTokens(int $value): self
    {
        $this->options['max_tokens'] = $value;
        return $this;
    }

    public function json(): self
    {
        $this->options['response_format'] = ['type' => 'json_object'];
        return $this;
    }

    public function with(array $options): self
    {
        $this->options = array_merge($this->options, $options);
        return $this;
    }

    public function send(): AIResponseInterface
    {
        return $this->conversation->send($this->options);
    }

    public function stream(): \Generator
    {
        return $this->conversation->stream($this->options);
    }

    public function getConversation(): ConversationInterface
    {
        return $this->conversation;
    }
}
