<?php

declare(strict_types=1);

namespace Marwa\AI\Support;

use Marwa\AI\Contracts\AIClientInterface;
use Marwa\AI\Contracts\AIResponseInterface;
use Marwa\AI\Contracts\ConversationInterface;
use Marwa\AI\Contracts\MessageInterface;
use RuntimeException;

class Conversation implements ConversationInterface
{
    /** @var array<MessageInterface> */
    private array $messages = [];

    private ?string $systemPrompt = null;
    private array $context = [];
    private string $id;

    public function __construct(
        string | array $initialMessages = [],
        private ?\Marwa\AI\Contracts\AIClientInterface $client = null
    ) {
        $this->id = uniqid('conv_', true);

        if (is_array($initialMessages)) {
            foreach ($initialMessages as $msg) {
                $this->addMessage($msg);
            }
        } elseif (is_string($initialMessages)) {
            $this->system($initialMessages);
        }
    }

    public function user(string $content, array $options = []): self
    {
        $this->messages[] = Message::user($content, $options['images'] ?? []);
        return $this;
    }

    public function assistant(string $content, array $options = []): self
    {
        $this->messages[] = Message::assistant($content);
        return $this;
    }

    public function system(string $content): self
    {
        $this->systemPrompt = $content;
        return $this;
    }

    public function tool(string $toolCallId, mixed $result, string $toolName): self
    {
        $this->messages[] = Message::tool($toolCallId, $result, $toolName);
        return $this;
    }

    public function addMessage(MessageInterface $message): self
    {
        $this->messages[] = $message;
        return $this;
    }

    public function getMessages(): array
    {
        $result = [];

        if ($this->systemPrompt !== null) {
            $result[] = Message::system($this->systemPrompt);
        }

        return [...$result, ...$this->messages];
    }

    public function clear(): self
    {
        $this->messages = [];
        return $this;
    }

    public function setSystem(string $prompt): self
    {
        $this->systemPrompt = $prompt;
        return $this;
    }

    public function getSystem(): ?string
    {
        return $this->systemPrompt;
    }

    public function withContext(array $context): self
    {
        $this->context = [...$this->context, ...$context];
        return $this;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function send(array $options = []): AIResponseInterface
    {
        if ($this->client === null) {
            throw new \RuntimeException('No AI client set for conversation.');
        }

        $messages = array_map(fn($m) => $m->toArray(), $this->getMessages());

        return $this->client->completion($messages, $options);
    }

    public function stream(array $options = []): \Generator
    {
        if ($this->client === null) {
            throw new \RuntimeException('No AI client set for conversation.');
        }

        $messages = array_map(fn($m) => $m->toArray(), $this->getMessages());

        return $this->client->streamCompletion($messages, $options);
    }

    public function continueWithTools(
        array $tools,
        int $maxIterations = 5
    ): AIResponseInterface {
        $iteration = 0;

        while ($iteration < $maxIterations) {
            $response = $this->send(['tools' => $tools]);
            $this->assistant($response->getContent());

            if (!$response->hasToolCalls()) {
                return $response;
            }

            foreach ($response->getToolCalls() as $toolCall) {
                $result = $toolCall->execute();
                $this->tool($toolCall->getId(), $result, $toolCall->getToolName());
            }

            $iteration++;
        }

        throw new \RuntimeException("Max tool iteration ({$maxIterations}) reached.");
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'system' => $this->systemPrompt,
            'messages' => array_map(fn($m) => $m->toArray(), $this->messages),
            'context' => $this->context,
        ];
    }

    public static function fromArray(array $data): self
    {
        $conv = new self();
        $conv->id = $data['id'];
        $conv->systemPrompt = $data['system'];
        $conv->context = $data['context'] ?? [];

        foreach ($data['messages'] as $msgData) {
            $conv->addMessage(Message::fromArray($msgData));
        }

        return $conv;
    }

    public function fork(): self
    {
        $forked = new self();
        $forked->messages = $this->messages;
        $forked->systemPrompt = $this->systemPrompt;
        $forked->context = $this->context;
        return $forked;
    }

    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setClient(\Marwa\AI\Contracts\AIClientInterface $client): self
    {
        $this->client = $client;
        return $this;
    }
}
