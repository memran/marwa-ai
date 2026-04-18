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

class GrokClient extends BaseDriver
{
    protected function getBaseUri(): string
    {
        return $this->config['base_url'] ?? 'https://api.x.ai/v1/';
    }

    protected function getDefaultHeaders(): array
    {
        $key = $this->config['api_key'] ?? getenv('XAI_API_KEY') ?? getenv('GROK_API_KEY') ?? '';
        return [
            'Authorization' => 'Bearer ' . $key,
            'Content-Type' => 'application/json',
        ];
    }

    protected function getDefaultModel(): string
    {
        return $this->config['model'] ?? 'grok-2-latest';
    }

    protected function getProviderName(): string
    {
        return 'xai';
    }

    protected function buildCompletionPayload(array $messages, array $options): array
    {
        return [
            'model' => $this->getModel(),
            'messages' => $this->buildMessages($messages),
            'stream' => false,
        ];
    }

    protected function getCompletionEndpoint(): string
    {
        return 'chat/completions';
    }

    protected function parseCompletionResponse(array $response): AIResponseInterface
    {
        $choice = $response['choices'][0];
        $message = $choice['message'];

        $usageData = $response['usage'] ?? [];
        $usage = $this->createUsage($usageData, $response['model']);

        $aiResponse = new AIResponse(
            $message['content'] ?? '',
            $usage,
            $response['model'],
            $choice['finish_reason'],
            $response
        );

        if (isset($message['tool_calls'])) {
            foreach ($message['tool_calls'] as $tc) {
                $aiResponse->addToolCall(ToolCall::fromArray($tc));
            }
        }

        return $aiResponse;
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
        throw new \RuntimeException('Embedding endpoint not yet available for xAI');
    }

    protected function getEmbeddingEndpoint(): string
    {
        return 'embeddings';
    }

    protected function parseEmbeddingResponse(array $response): EmbeddingResponseInterface
    {
        throw new \RuntimeException('Embeddings not supported by xAI yet');
    }

    protected function buildImagePayload(string $prompt, array $options): array
    {
        throw new \RuntimeException('Image generation not supported by xAI Grok');
    }

    protected function getImageEndpoint(): string
    {
        return 'images/generations';
    }

    protected function parseImageResponse(array $response): ImageResponseInterface
    {
        throw new \RuntimeException('Image generation not supported');
    }

    public function countTokens(string $text): int
    {
        return (int) (strlen($text) / 3.5);
    }

    public function supports(string $feature): bool
    {
        return match ($feature) {
            'streaming', 'tools', 'vision' => true,
            default => false,
        };
    }
}
