<?php

declare(strict_types=1);

namespace Marwa\AI\Support;

use Marwa\AI\Contracts\MemoryManagerInterface;
use Marwa\AI\Contracts\MessageInterface;
use Marwa\AI\Contracts\AIManagerInterface;

class MemoryManager implements MemoryManagerInterface
{
    /** @var array<string, mixed> */
    private array $memory = [];

    /** @var array<string, array<int, MessageInterface>> */
    private array $conversations = [];

    /** @var array<string, array{text: string, embedding: array<float>|null, metadata: array}> */
    private array $semanticMemory = [];

    private ?AIManagerInterface $ai = null;
    private float $similarityThreshold;

    public function __construct(array $config = [])
    {
        $this->ai = $config['ai'] ?? null;
        $this->similarityThreshold = $config['similarity_threshold'] ?? 0.75;
    }

    public function set(string $key, mixed $value, ?int $ttl = null): void
    {
        $this->memory[$key] = [
            'value' => $value,
            'expires' => $ttl ? time() + $ttl : null,
        ];
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (!isset($this->memory[$key])) {
            return $default;
        }

        $item = $this->memory[$key];
        if ($item['expires'] !== null && $item['expires'] < time()) {
            unset($this->memory[$key]);
            return $default;
        }

        return $item['value'];
    }

    public function has(string $key): bool
    {
        $value = $this->get($key);
        return $value !== null;
    }

    public function forget(string $key): void
    {
        unset($this->memory[$key]);
    }

    public function flush(): void
    {
        $this->memory = [];
    }

    public function all(): array
    {
        return array_map(fn($item) => $item['value'], $this->memory);
    }

    public function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        if ($this->has($key)) {
            return $this->get($key);
        }

        $value = $callback();
        $this->set($key, $value, $ttl);
        return $value;
    }

    public function storeMessages(string $conversationId, array $messages): void
    {
        foreach ($messages as $message) {
            $this->conversations[$conversationId][] = $message instanceof MessageInterface 
                ? $message 
                : Message::fromArray($message);
        }
    }

    public function getMessages(string $conversationId, ?int $limit = null): array
    {
        if (!isset($this->conversations[$conversationId])) {
            return [];
        }

        /** @var array<MessageInterface> $messages */
        $messages = $this->conversations[$conversationId];
        if ($limit !== null) {
            $messages = array_slice($messages, -$limit);
        }

        return $messages;
    }

    public function summarize(string $conversationId, int $maxTokens = 1000): string
    {
        $messages = $this->getMessages($conversationId);
        if (empty($messages)) {
            return '';
        }

        $text = implode("\n", array_map(fn($m) => $m->getContent(), $messages));

        if (strlen($text) <= $maxTokens * 4) {
            return $text;
        }

        if ($this->ai !== null) {
            $summary = $this->ai->conversation("Summarize the following conversation in under {$maxTokens} tokens:\n\n" . substr($text, 0, 4000))
                ->send();
            return $summary->getContent();
        }

        return substr($text, 0, $maxTokens * 4);
    }

    public function search(string $query, int $limit = 5, ?float $threshold = null): array
    {
        $threshold ??= $this->similarityThreshold;
        $queryEmbedding = null;

        if ($this->ai !== null) {
            $embedding = $this->ai->embed([$query]);
            $queryEmbedding = $embedding->getEmbedding(0);
        }

        if ($queryEmbedding === null) {
            return [];
        }

        $results = [];
        foreach ($this->semanticMemory as $id => $mem) {
            if ($mem['embedding'] !== null) {
                $score = EmbeddingResponse::similarity($queryEmbedding, $mem['embedding']);
                if ($score >= $threshold) {
                    $results[$id] = ['memory' => $mem, 'score' => $score];
                }
            }
        }

        uasort($results, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($results, 0, $limit, true);
    }

    public function rememberSemantic(string $text, array $metadata = []): void
    {
        $id = md5($text);

        if ($this->ai !== null) {
            $embedding = $this->ai->embed([$text]);
            $this->semanticMemory[$id] = [
                'text' => $text,
                'embedding' => $embedding->getEmbedding(0),
                'metadata' => $metadata,
            ];
        } else {
            $this->semanticMemory[$id] = [
                'text' => $text,
                'embedding' => null,
                'metadata' => $metadata,
            ];
        }
    }
}
