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

class AnthropicClient extends BaseDriver
{
    protected function getBaseUri(): string
    {
        return $this->config['base_url'] ?? 'https://api.anthropic.com/v1/';
    }

    protected function getDefaultHeaders(): array
    {
        $key = $this->config['api_key'] ?? getenv('ANTHROPIC_API_KEY') ?? '';
        return [
            'x-api-key' => $key,
            'anthropic-version' => '2023-06-01',
            'Content-Type' => 'application/json',
        ];
    }

    protected function getDefaultModel(): string
    {
        return $this->config['model'] ?? 'claude-3-opus-20240229';
    }

    protected function getProviderName(): string
    {
        return 'anthropic';
    }

    protected function buildCompletionPayload(array $messages, array $options): array
    {
        $system = '';
        $filteredMessages = [];

        foreach ($messages as $msg) {
            if ($msg['role'] === 'system') {
                $system = $msg['content'];
            } else {
                $filteredMessages[] = $msg;
            }
        }

        if (isset($options['system'])) {
            $system = $options['system'];
        }

        $payload = [
            'model' => $this->getModel(),
            'messages' => $filteredMessages,
            'max_tokens' => $options['max_tokens'] ?? 4096,
        ];

        if ($system) {
            $payload['system'] = $system;
        }
        if (isset($options['temperature'])) {
            $payload['temperature'] = $options['temperature'];
        }
        if (isset($options['tools'])) {
            $payload['tools'] = $this->formatTools($options['tools']);
        }

        return $payload;
    }

    private function formatTools(array $tools): array
    {
        $formatted = [];
        foreach ($tools as $tool) {
            if ($tool instanceof \Marwa\AI\Contracts\ToolDefinitionInterface) {
                $formatted[] = [
                    'name' => $tool->getName(),
                    'description' => $tool->getDescription(),
                    'input_schema' => $tool->getParameters(),
                ];
            } else {
                $formatted[] = $tool;
            }
        }
        return $formatted;
    }

    protected function getCompletionEndpoint(): string
    {
        return 'messages';
    }

    protected function parseCompletionResponse(array $response): AIResponseInterface
    {
        $content = '';
        foreach ($response['content'] as $block) {
            if ($block['type'] === 'text') {
                $content .= $block['text'];
            }
        }

        $usageData = $response['usage'];
        $usage = new Usage(
            $usageData['input_tokens'],
            $usageData['output_tokens'],
            $this->getProviderName(),
            $response['model']
        );

        $aiResponse = new AIResponse(
            $content,
            $usage,
            $response['model'],
            $response['stop_reason'],
            $response
        );

        if (isset($response['content'])) {
            foreach ($response['content'] as $block) {
                if ($block['type'] === 'tool_use') {
                    $aiResponse->addToolCall(new ToolCall(
                        $block['id'],
                        $block['name'],
                        $block['input'],
                        json_encode($block['input'])
                    ));
                }
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

            if (isset($parsed['type']) && $parsed['type'] === 'content_block_delta') {
                if (isset($parsed['delta']['text'])) {
                    yield new StreamChunk($parsed['delta']['text']);
                }
            }

            if (isset($parsed['type']) && $parsed['type'] === 'message_stop') {
                yield new StreamChunk('', true, null, $parsed['stop_reason'] ?? 'stop_sequence');
            }
        }
    }

    protected function buildEmbeddingPayload(array $texts, array $options): array
    {
        return [
            'model' => $options['model'] ?? 'claude-3-embedding',
            'input' => $texts,
        ];
    }

    protected function getEmbeddingEndpoint(): string
    {
        return 'embeddings';
    }

    protected function parseEmbeddingResponse(array $response): EmbeddingResponseInterface
    {
        // Anthropic uses different format
        $embeddings = [];
        foreach ($response['data'] as $item) {
            $embeddings[] = $item['embedding'];
        }

        return new EmbeddingResponse(
            $embeddings,
            $response['model'],
            $this->createUsage($response['usage'] ?? [], $response['model'])
        );
    }

    protected function buildImagePayload(string $prompt, array $options): array
    {
        return [
            'model' => $options['model'] ?? 'claude-3-sonnet',
            'prompt' => $prompt,
        ];
    }

    protected function getImageEndpoint(): string
    {
        return 'images';
    }

    protected function parseImageResponse(array $response): ImageResponseInterface
    {
        return new ImageResponse(
            ['data' => [['url' => $response['url'] ?? null]]],
            $response['model'] ?? 'claude-3-sonnet',
            $this->createUsage($response['usage'] ?? [], $response['model'] ?? 'claude-3-sonnet')
        );
    }

    public function countTokens(string $text): int
    {
        try {
            $resp = $this->http->request('POST', 'count_tokens', [
                'json' => ['model' => $this->getModel(), 'messages' => [['role' => 'user', 'content' => $text]]]
            ]);
            $data = json_decode($resp->getBody()->getContents(), true);
            return $data['usage']['input_tokens'] ?? 0;
        } catch (GuzzleException) {
            return (int) (strlen($text) / 4);
        }
    }

    public function supports(string $feature): bool
    {
        return match ($feature) {
            'streaming', 'tools', 'vision', 'function_calling', ' xml' => true,
            default => false,
        };
    }
}
