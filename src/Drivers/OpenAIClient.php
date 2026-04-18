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

class OpenAIClient extends BaseDriver
{
    /**
     * @var array<string, mixed>
     */
    protected array $config;
    protected function getBaseUri(): string
    {
        return $this->config['base_url'] ?? 'https://api.openai.com/v1/';
    }

    protected function getDefaultHeaders(): array
    {
        $key = $this->config['api_key'] ?? (getenv('OPENAI_API_KEY') ?: '');
        return [
            'Authorization' => 'Bearer ' . $key,
            'Content-Type' => 'application/json',
        ];
    }

    protected function getDefaultModel(): string
    {
        return $this->config['model'] ?? 'gpt-4o';
    }

    protected function getProviderName(): string
    {
        return 'openai';
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
            if (is_string($options['response_format']) && $options['response_format'] === 'json_object') {
                $payload['response_format'] = ['type' => 'json_object'];
            } else {
                $payload['response_format'] = $options['response_format'];
            }
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

        $content = $message['content'] ?? '';

        $usageData = $response['usage'];
        $usage = $this->createUsage($usageData, $response['model']);

        $aiResponse = new AIResponse(
            $content,
            $usage,
            $response['model'],
            $choice['finish_reason'],
            $response
        );

        if (isset($message['tool_calls'])) {
            foreach ($message['tool_calls'] as $tc) {
                $toolCall = ToolCall::fromArray($tc);
                $aiResponse->addToolCall($toolCall);
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
            if (isset($parsed['choices'][0]['delta'])) {
                $delta = $parsed['choices'][0]['delta'];

                if (isset($delta['content'])) {
                    yield new StreamChunk($delta['content'], false);
                }

                if (isset($parsed['choices'][0]['finish_reason'])) {
                    $usage = null;
                    if (isset($parsed['usage'])) {
                        $usage = $this->createUsage($parsed['usage'], $parsed['model'] ?? $this->getModel());
                    }
                    yield new StreamChunk('', true, $usage, $parsed['choices'][0]['finish_reason']);
                }
            }
        }
    }

    protected function buildEmbeddingPayload(array $texts, array $options): array
    {
        return [
            'model' => $options['model'] ?? 'text-embedding-3-small',
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
        $payload = [
            'model' => $options['model'] ?? 'dall-e-3',
            'prompt' => $prompt,
            'n' => $options['n'] ?? 1,
            'size' => $options['size'] ?? '1024x1024',
        ];

        if (isset($options['quality'])) {
            $payload['quality'] = $options['quality'];
        }

        return $payload;
    }

    protected function getImageEndpoint(): string
    {
        return 'images/generations';
    }

    protected function parseImageResponse(array $response): ImageResponseInterface
    {
        return new ImageResponse(
            $response,
            $response['model'] ?? 'dall-e-3',
            $this->createUsage($response['usage'] ?? [], $response['model'] ?? 'dall-e-3')
        );
    }

    public function countTokens(string $text): int
    {
        try {
            $resp = $this->http->request('POST', 'tokenizer', [
                'json' => ['model' => $this->getModel(), 'text' => $text]
            ]);
            $data = json_decode($resp->getBody()->getContents(), true);
            return $data['token_count'] ?? strlen($text) / 4;
        } catch (GuzzleException) {
            return (int) (strlen($text) / 4);
        }
    }

    public function supports(string $feature): bool
    {
        return match ($feature) {
            'streaming', 'tools', 'vision', 'function_calling', 'json_mode' => true,
            default => false,
        };
    }
}
