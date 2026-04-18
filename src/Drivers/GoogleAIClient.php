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

class GoogleAIClient extends BaseDriver
{
    protected function getBaseUri(): string
    {
        return $this->config['base_url'] ?? 'https://generativelanguage.googleapis.com/v1beta/';
    }

    protected function getDefaultHeaders(): array
    {
        $key = $this->config['api_key'] ?? getenv('GOOGLE_API_KEY') ?? '';
        return [
            'Authorization' => 'Bearer ' . $key,
            'Content-Type' => 'application/json',
        ];
    }

    protected function getDefaultModel(): string
    {
        return $this->config['model'] ?? 'gemini-pro';
    }

    protected function getProviderName(): string
    {
        return 'google';
    }

    protected function buildCompletionPayload(array $messages, array $options): array
    {
        $contents = [];
        $systemInstruction = null;

        foreach ($messages as $msg) {
            if ($msg['role'] === 'system') {
                $systemInstruction = ['parts' => [['text' => $msg['content']]]];
            } else {
                $role = $msg['role'] === 'assistant' ? 'model' : 'user';
                $parts = [];
                if (isset($msg['content'])) {
                    $parts[] = ['text' => $msg['content']];
                }
                if (isset($msg['images'])) {
                    foreach ($msg['images'] as $img) {
                        $parts[] = [
                            'inline_data' => [
                                'mime_type' => 'image/jpeg',
                                'data' => base64_encode(file_get_contents($img))
                            ]
                        ];
                    }
                }
                $contents[] = ['role' => $role, 'parts' => $parts];
            }
        }

        $payload = [
            'contents' => $contents,
        ];

        if ($systemInstruction) {
            $payload['systemInstruction'] = $systemInstruction;
        }
        if (isset($options['temperature'])) {
            $payload['generationConfig']['temperature'] = $options['temperature'];
        }
        if (isset($options['max_tokens'])) {
            $payload['generationConfig']['maxOutputTokens'] = $options['max_tokens'];
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
                    'function_declarations' => [[
                        'name' => $tool->getName(),
                        'description' => $tool->getDescription(),
                        'parameters' => $tool->getParameters(),
                    ]]
                ];
            } else {
                $formatted[] = $tool;
            }
        }
        return ['function_declarations' => array_merge(...$formatted)];
    }

    protected function getCompletionEndpoint(): string
    {
        return "models/{$this->getModel()}:generateContent";
    }

    protected function parseCompletionResponse(array $response): AIResponseInterface
    {
        $content = '';
        if (isset($response['candidates'][0]['content']['parts'][0]['text'])) {
            $content = $response['candidates'][0]['content']['parts'][0]['text'];
        }

        $usageData = $response['usageMetadata'] ?? [];
        $usage = new Usage(
            $usageData['promptTokenCount'] ?? 0,
            $usageData['candidatesTokenCount'] ?? 0,
            $this->getProviderName(),
            $response['modelVersion'] ?? $this->getModel()
        );

        return new AIResponse(
            $content,
            $usage,
            $response['modelVersion'] ?? $this->getModel(),
            null,
            $response
        );
    }

    public function streamCompletion(array $messages, array $options = []): \Generator
    {
        $payload = $this->buildCompletionPayload($messages, $options);

        foreach ($this->streamRequest('POST', $this->getCompletionEndpoint() . ':streamGenerateContent', $payload) as $data) {
            $parsed = json_decode($data, true);
            if (isset($parsed['candidates'][0]['content']['parts'][0]['text'])) {
                yield new StreamChunk($parsed['candidates'][0]['content']['parts'][0]['text']);
            }
        }

        yield new StreamChunk('', true, null, 'stop');
    }

    protected function buildEmbeddingPayload(array $texts, array $options): array
    {
        return [
            'model' => $options['model'] ?? 'text-embedding-004',
            'content' => array_map(fn($t) => ['text' => $t], $texts),
        ];
    }

    protected function getEmbeddingEndpoint(): string
    {
        return "models/{$this->getModel()}:embedContent";
    }

    protected function parseEmbeddingResponse(array $response): EmbeddingResponseInterface
    {
        $embeddings = [];
        foreach ($response['embedding'] as $item) {
            $embeddings[] = $item['values'];
        }

        return new EmbeddingResponse(
            $embeddings,
            $response['model'] ?? $this->getModel(),
            new Usage(0, 0, $this->getProviderName(), $this->getModel())
        );
    }

    protected function buildImagePayload(string $prompt, array $options): array
    {
        throw new \RuntimeException('Image generation not supported directly via Google AI API. Use Imagen API instead.');
    }

    protected function getImageEndpoint(): string
    {
        return 'images:generate';
    }

    protected function parseImageResponse(array $response): ImageResponseInterface
    {
        throw new \RuntimeException('Image generation not implemented for Google AI');
    }

    public function countTokens(string $text): int
    {
        try {
            $resp = $this->http->request('POST', "models/{$this->getModel()}:countTokens", [
                'json' => ['contents' => [['parts' => [['text' => $text]]]]]
            ]);
            $data = json_decode($resp->getBody()->getContents(), true);
            return $data['totalTokens'] ?? 0;
        } catch (GuzzleException) {
            return (int) (strlen($text) / 4);
        }
    }

    public function supports(string $feature): bool
    {
        return match ($feature) {
            'streaming', 'tools', 'vision', 'function_calling' => true,
            default => false,
        };
    }
}
