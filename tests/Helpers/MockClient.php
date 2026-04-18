<?php

declare(strict_types=1);

namespace Marwa\AI\Tests\Helpers;

use Marwa\AI\Contracts\AIClientInterface;
use Marwa\AI\Contracts\AIResponseInterface;
use Marwa\AI\Contracts\EmbeddingResponseInterface;
use Marwa\AI\Contracts\ImageResponseInterface;
use Marwa\AI\Contracts\StreamChunkInterface;
use Marwa\AI\Support\AIResponse;
use Marwa\AI\Support\EmbeddingResponse;
use Marwa\AI\Support\ImageResponse;
use Marwa\AI\Support\StreamChunk;
use Marwa\AI\Support\Usage;
use Marwa\AI\Support\ToolCall;

class MockClient implements AIClientInterface
{
    private string $response;

    public function __construct(string $response, private array $toolCalls = [])
    {
        $this->response = $response;
    }

    public function completion(array $messages, array $options = []): AIResponseInterface
    {
        $usage = new Usage(10, 20, 'openai', 'gpt-4');
        $resp = new AIResponse($this->response, $usage, 'gpt-4', 'stop', ['mock' => true]);

        foreach ($this->toolCalls as $tc) {
            $resp->addToolCall(new ToolCall(
                $tc['id'] ?? 'call_1',
                $tc['name'] ?? 'test_tool',
                $tc['arguments'] ?? [],
                json_encode($tc['arguments'] ?? [])
            ));
        }

        return $resp;
    }

    public function streamCompletion(array $messages, array $options = []): \Generator
    {
        yield new StreamChunk($this->response, true);
    }

    public function embed(array $texts, array $options = []): EmbeddingResponseInterface
    {
        $embeddings = [];
        foreach ($texts as $text) {
            $dim = 1536;
            $emb = array_fill(0, $dim, 0.0);
            $emb[0] = strlen($text) / 100;
            $embeddings[] = $emb;
        }

        return new EmbeddingResponse($embeddings, 'text-embedding-3-small', new Usage(0, 0, 'openai', 'embed'));
    }

    public function generateImage(string $prompt, array $options = []): ImageResponseInterface
    {
        return new ImageResponse(
            ['data' => [['url' => 'https://fake.image/url.png']]],
            'dall-e-3',
            new Usage(0, 0, 'openai', 'dall-e-3')
        );
    }

    public function analyzeImage(string $imagePath, string $prompt, array $options = []): AIResponseInterface
    {
        return $this->completion([]);
    }

    public function countTokens(string $text): int
    {
        return (int) (strlen($text) / 4);
    }

    public function supports(string $feature): bool
    {
        return in_array($feature, ['streaming', 'tools', 'vision']);
    }

    public function getProvider(): string
    {
        return 'mock';
    }

    public function getModel(): string
    {
        return 'mock-model';
    }
}
