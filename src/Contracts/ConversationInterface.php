<?php

declare(strict_types=1);

namespace Marwa\AI\Contracts;

interface ConversationInterface
{
    /**
     * Add a user message
     */
    public function user(string $content, array $options = []): self;

    /**
     * Add an assistant message
     */
    public function assistant(string $content, array $options = []): self;

    /**
     * Add a system message
     */
    public function system(string $content): self;

    /**
     * Add a tool result message
     */
    public function tool(string $toolCallId, mixed $result, string $toolName): self;

    /**
     * Get messages
     *
     * @return array<MessageInterface>
     */
    public function getMessages(): array;

    /**
     * Clear all messages
     */
    public function clear(): self;

    /**
     * Set system prompt
     */
    public function setSystem(string $prompt): self;

    /**
     * Get system prompt
     */
    public function getSystem(): ?string;

    /**
     * Set context/metadata
     */
    public function withContext(array $context): self;

    /**
     * Get context
     */
    public function getContext(): array;

    /**
     * Send conversation to AI and get response
     */
    public function send(array $options = []): AIResponseInterface;

    /**
     * Send and stream response
     */
    public function stream(array $options = []): \Generator;

    /**
     * Continue conversation with tool execution
     */
    public function continueWithTools(
        array $tools,
        int $maxIterations = 5
    ): AIResponseInterface;

    /**
     * Export conversation to array
     */
    public function toArray(): array;

    /**
     * Import conversation from array
     */
    public static function fromArray(array $data): self;

    /**
     * Clone conversation with new messages
     */
    public function fork(): self;

    /**
     * Get conversation ID
     */
    public function getId(): string;
}
