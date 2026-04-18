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

class MistralClient extends BaseDriver
{
    protected function getBaseUri(): string
    {
        return $this->config['base_url'] ?? 'https://api.mistral.ai/v1/';
    }

    protected function getDefaultHeaders(): array
    {
        $key = $this->config['api_key'] ?? getenv('MISTRAL_API_KEY') ?? '';
        return [
            'Authorization' => 'Bearer ' . $key,
            'Content-Type' => 'application/json',
        ];
    }

    protected function getDefaultModel(): string
    {
        return $this->config['model'] ?? 'mistral-large-latest';
    }

    protected function getProviderName(): string
    {
        return 'mistral';
    }

    protected function buildCompletionPayload(array $messages, array $options): array
    {
        $payload = [
            'model' => $this->getModel(),
            'messages' => $this->buildMessages($messages),
        ];

        if (isset($options['temperature'])) {
            $payload['temperature'] = $options['temperature'];
        }
        if (isset($options['max_tokens'])) {
            $payload['max_tokens'] = $options['max_tokens'];
        }
        if (isset($options['tools'])) {
            $payload['tools'] = $this->formatTools($options['tools']);
            $payload['tool_choice'] = $options['tool_choice'] ?? 'auto';
        }
        if (isset($options['response_format'])) {
            $payload['response_format'] = $options['response_format'];
        }

        return $payload;
    }

    private function formatTools(array $tools): array
    {
        return array_map(fn($t) => $t instanceof \Marwa\AI\Contracts\ToolDefinitionInterface
            ? $t->toProviderFormat('openai')
            : $t, $tools);
    }

    protected function getCompletionEndpoint(): string
    {
        return 'chat/completions';
    }

    protected function parseCompletionResponse(array $response): AIResponseInterface
    {
        $choice = $response['choices'][0];
        $message = $choice['message'];

        $usageData = $response['usage'];
        $usage = new Usage(
            $usageData['prompt_tokens'],
            $usageData['completion_tokens'],
            $this->getProviderName(),
            $response['model']
        );

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
        return [
            'model' => $options['model'] ?? 'mistral-embed',
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
            $response['data'],
            $response['model'],
            $this->createUsage($response['usage'] ?? [], $response['model'])
        );
    }

    protected function buildImagePayload(string $prompt, array $options): array
    {
        throw new \RuntimeException('Image generation not supported by Mistral AI');
    }

    protected function getImageEndpoint(): string
    {
        return 'images/generations';
    }

    protected function parseImageResponse(array $response): ImageResponseInterface
    {
        throw new \RuntimeException('Image generation not implemented');
    }

    public function countTokens(string $text): int
    {
        try {
            $resp = $this->http->request('POST', 'tokens/count', [
                'json' => ['model' => $this->getModel(), 'text' => $text]
            ]);
            $data = json_decode($resp->getBody()->getContents(), true);
            return $data['tokens'] ?? 0;
        } catch (GuzzleException) {
            return (int) (strlen($text) / 4);
        }
    }

    public function supports(string $feature): bool
    {
        return match ($feature) {
            'streaming', 'tools', 'function_calling', 'json_mode' => true,
            default => false,
        };
    }
}
