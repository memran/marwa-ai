<?php

declare(strict_types=1);

namespace Marwa\AI\Support;

use Marwa\AI\Contracts\ImageResponseInterface;
use Marwa\AI\Contracts\UsageInterface;

final class ImageResponse implements ImageResponseInterface
{
    /** @var array<string> */
    private array $urls = [];

    /** @var array<string> */
    private array $base64 = [];

    /** @var array<string> */
    private array $savedPaths = [];

    public function __construct(
        private array $raw,
        private string $model,
        private UsageInterface $usage
    ) {
        $this->parseResponse($raw);
    }

    private function parseResponse(array $raw): void
    {
        if (isset($raw['data'])) {
            foreach ($raw['data'] as $item) {
                if (isset($item['url'])) {
                    $this->urls[] = $item['url'];
                }
                if (isset($item['b64_json'])) {
                    $this->base64[] = $item['b64_json'];
                }
            }
        }
    }

    public function getUrls(): array
    {
        return $this->urls;
    }

    public function getBase64(): array
    {
        return $this->base64;
    }

    public function save(string $directory, string $prefix = 'image_'): array
    {
        $this->savedPaths = [];

        foreach ($this->base64 as $index => $data) {
            $filename = $directory . '/' . $prefix . uniqid() . '_' . $index . '.png';
            file_put_contents($filename, base64_decode($data));
            $this->savedPaths[] = $filename;
        }

        return $this->savedPaths;
    }

    public function getRevisedPrompt(): ?string
    {
        return $this->raw['revised_prompt'] ?? null;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function getSize(): ?string
    {
        return $this->raw['size'] ?? null;
    }

    public function getQuality(): ?string
    {
        return $this->raw['quality'] ?? null;
    }

    public function getUsage(): UsageInterface
    {
        return $this->usage;
    }

    public function getRawResponse(): mixed
    {
        return $this->raw;
    }
}
