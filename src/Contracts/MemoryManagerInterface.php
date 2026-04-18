<?php

declare(strict_types=1);

namespace Marwa\AI\Contracts;

use Marwa\AI\Contracts\MessageInterface;
use Marwa\AI\Contracts\AIManagerInterface;

/**
 * Conversation memory and context management
 */
interface MemoryManagerInterface
{
    /**
     * Set a memory item
     */
    public function set(string $key, mixed $value, ?int $ttl = null): void;

    /**
     * Get a memory item
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Check if key exists
     */
    public function has(string $key): bool;

    /**
     * Remove a memory item
     */
    public function forget(string $key): void;

    /**
     * Clear all memory
     */
    public function flush(): void;

    /**
     * Get all memory items
     */
    public function all(): array;

    /**
     * Remember a value (get or compute)
     */
    public function remember(string $key, callable $callback, ?int $ttl = null): mixed;

    /**
     * Store conversation messages
     */
    public function storeMessages(string $conversationId, array $messages): void;

    /**
     * Retrieve conversation messages
     *
     * @return array<MessageInterface>
     */
    public function getMessages(string $conversationId, ?int $limit = null): array;

    /**
     * Summarize long conversations
     */
    public function summarize(string $conversationId, int $maxTokens = 1000): string;

    /**
     * Search memory by embedding similarity
     */
    public function search(string $query, int $limit = 5, ?float $threshold = null): array;

    /**
     * Add to semantic memory
     */
    public function rememberSemantic(string $text, array $metadata = []): void;
}
