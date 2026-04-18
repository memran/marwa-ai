<?php

declare(strict_types=1);

namespace Marwa\AI\Drivers;

use GuzzleHttp\Exception\GuzzleException;
use Marwa\AI\Contracts\AIResponseInterface;
use Marwa\AI\Contracts\EmbeddingResponseInterface;
use Marwa\AI\Contracts\ImageResponseInterface;
use Marwa\AI\Support\AIResponse;
use Marwa\AI\Support\EmbeddingResponse;
use Marwa\AI\Support\ImageResponse;
use Marwa\AI\Support\StreamChunk;
use Marwa\AI\Support\Usage;
use Marwa\AI\Support\ToolCall;

class OllamaClient extends BaseDriver
{
    protected function getBaseUri(): string
    {
        return $this->config['base_url'] ?? 'http://localhost:11434/v1/';
    }

    protected function getDefaultHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
        ];
    }

    protected function getDefaultModel(): string
    {
        return $this->config['model'] ?? 'llama3.2';
    }

    protected function getProviderName(): string
    {
        return 'ollama';
    }

    protected function buildCompletionPayload(array $messages, array $options): array
    {
        $payload = [
            'model' => $this->getModel(),
            'messages' => $this->buildMessages($messages),
            'stream' => false,
        ];

        if (isset($options['temperature'])) {
            $payload['options']['temperature'] = $options['temperature'];
        }
        if (isset($options['max_tokens'])) {
            $payload['options']['num_predict'] = $options['max_tokens'];
        }

        return $payload;
    }

    protected function getCompletionEndpoint(): string
    {
        return 'chat/completions';
    }

    protected function parseCompletionResponse(array $response): AIResponseInterface
    {
        $message = $response['choices'][0]['message'];

        $usageData = $response['usage'] ?? [
            'prompt_tokens' => count($response['choices'][0]['message']['content'] ?? '') / 4,
            'completion_tokens' => 0,
        ];

        $usage = $this->createUsage($usageData, $response['model']);

        return new AIResponse(
            $message['content'] ?? '',
            $usage,
            $response['model'],
            null,
            $response
        );
    }

    public function streamCompletion(array $messages, array $options = []): \Generator
    {
        $payload = $this->buildCompletionPayload($messages, $options);
        $payload['stream'] = true;

        foreach ($this->streamRequest('POST', $this->getCompletionEndpoint(), $payload) as $data) {
            $parsed = json_decode($data, true);
            if (isset($parsed['choices'][0]['delta']['content'])) {
                yield new StreamChunk($parsed['choices'][0]['delta']['content']);
            }

            if (isset($parsed['choices'][0]['finish_reason']) && $parsed['choices'][0]['finish_reason'] !== null) {
                yield new StreamChunk('', true, null, $parsed['choices'][0]['finish_reason']);
            }
        }
    }

    protected function buildEmbeddingPayload(array $texts, array $options): array
    {
        return [
            'model' => $options['model'] ?? $this->getModel(),
            'input' => $texts,
        ];
    }

    protected function getEmbeddingEndpoint(): string
    {
        return 'embeddings';
    }

    protected function parseEmbeddingResponse(array $response): EmbeddingResponseInterface
    {
        return new EmbeddingResponse(
            [$response['embedding']],
            $response['model'],
            new Usage(0, 0, $this->getProviderName(), $this->getModel())
        );
    }

    protected function buildImagePayload(string $prompt, array $options): array
    {
        throw new \RuntimeException('Image generation not supported by Ollama yet');
    }

    protected function getImageEndpoint(): string
    {
        return 'images/generate';
    }

    protected function parseImageResponse(array $response): ImageResponseInterface
    {
        throw new \RuntimeException('Image generation not implemented');
    }

    public function countTokens(string $text): int
    {
        return (int) (strlen($text) / 4);
    }

    public function supports(string $feature): bool
    {
        return match ($feature) {
            'streaming', 'tools', 'vision', 'local' => true,
            default => false,
        };
    }
}
