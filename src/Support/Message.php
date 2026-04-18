<?php

declare(strict_types=1);

namespace Marwa\AI\Support;

use Marwa\AI\Contracts\MessageInterface;

final class Message implements MessageInterface
{
    /** @var array<array{type: string, content: mixed}> */
    private array $parts = [];

    public function __construct(
        private string $role,
        private string $content,
        private ?string $toolCallId = null,
        private ?string $toolName = null,
        private array $images = [],
        private array $files = []
    ) {
        $this->buildParts();
    }

    private function buildParts(): void
    {
        if (!empty($this->images)) {
            foreach ($this->images as $image) {
                $this->parts[] = [
                    'type' => 'image',
                    'content' => $image,
                ];
            }
            $this->parts[] = [
                'type' => 'text',
                'content' => $this->content,
            ];
        } else {
            $this->parts[] = [
                'type' => 'text',
                'content' => $this->content,
            ];
        }
    }

    public static function user(string $content, array $images = []): self
    {
        return new self(self::ROLE_USER, $content, images: $images);
    }

    public static function assistant(string $content): self
    {
        return new self(self::ROLE_ASSISTANT, $content);
    }

    public static function system(string $content): self
    {
        return new self(self::ROLE_SYSTEM, $content);
    }

    public static function tool(string $toolCallId, mixed $result, string $toolName): self
    {
        return new self(self::ROLE_TOOL, is_string($result) ? $result : json_encode($result), $toolCallId, $toolName);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['role'],
            $data['content'],
            $data['tool_call_id'] ?? null,
            $data['tool_name'] ?? null,
            $data['images'] ?? [],
            $data['files'] ?? []
        );
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getParts(): array
    {
        return $this->parts;
    }

    public function getToolCallId(): ?string
    {
        return $this->toolCallId;
    }

    public function getToolName(): ?string
    {
        return $this->toolName;
    }

    public function hasImages(): bool
    {
        return !empty($this->images);
    }

    public function hasFiles(): bool
    {
        return !empty($this->files);
    }

    public function toArray(): array
    {
        $data = [
            'role' => $this->role,
            'content' => $this->content,
        ];

        if ($this->toolCallId) {
            $data['tool_call_id'] = $this->toolCallId;
        }
        if ($this->toolName) {
            $data['tool_name'] = $this->toolName;
        }
        if (!empty($this->images)) {
            $data['images'] = $this->images;
        }

        return $data;
    }
}
