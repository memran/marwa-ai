<?php

declare(strict_types=1);

namespace Marwa\AI\Contracts;

interface MessageInterface
{
    public const ROLE_USER = 'user';
    public const ROLE_ASSISTANT = 'assistant';
    public const ROLE_SYSTEM = 'system';
    public const ROLE_TOOL = 'tool';

    /**
     * Get message role
     */
    public function getRole(): string;

    /**
     * Get message content
     */
    public function getContent(): string;

    /**
     * Get message parts (for multimodal)
     *
     * @return array<array{type: string, content: mixed}>
     */
    public function getParts(): array;

    /**
     * Get tool call ID (for tool messages)
     */
    public function getToolCallId(): ?string;

    /**
     * Get tool name (for tool messages)
     */
    public function getToolName(): ?string;

    /**
     * Check if message contains images
     */
    public function hasImages(): bool;

    /**
     * Check if message contains files
     */
    public function hasFiles(): bool;

    /**
     * Get raw data
     */
    public function toArray(): array;
}
