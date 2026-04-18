<?php

declare(strict_types=1);

namespace Marwa\AI\Drivers;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Marwa\AI\Contracts\AIClientInterface;
use Marwa\AI\Contracts\AIResponseInterface;
use Marwa\AI\Contracts\EmbeddingResponseInterface;
use Marwa\AI\Contracts\ImageResponseInterface;
use Marwa\AI\Contracts\StreamChunkInterface;
use Marwa\AI\Contracts\UsageInterface;
use Marwa\AI\Support\AIResponse;
use Marwa\AI\Support\EmbeddingResponse;
use Marwa\AI\Support\ImageResponse;
use Marwa\AI\Support\StreamChunk;
use Marwa\AI\Support\Usage;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

abstract class BaseDriver implements AIClientInterface
{
    protected ClientInterface $http;
    protected array $config;
    protected LoggerInterface $logger;
    protected int $timeout;
    protected int $retries;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->timeout = $config['timeout'] ?? 30;
        $this->retries = $config['retries'] ?? 3;
        $this->logger = $config['logger'] ?? new NullLogger();

        $this->http = new Client([
            'base_uri' => $this->getBaseUri(),
            'timeout' => $this->timeout,
            'headers' => $this->getDefaultHeaders(),
        ]);
    }

    abstract protected function getBaseUri(): string;

    abstract protected function getDefaultHeaders(): array;

    abstract protected function getDefaultModel(): string;

    public function getProvider(): string
    {
        return $this->getProviderName();
    }

    abstract protected function getProviderName(): string;

    public function getModel(): string
    {
        return $this->config['model'] ?? $this->getDefaultModel();
    }

    public function completion(array $messages, array $options = []): AIResponseInterface
    {
        $payload = $this->buildCompletionPayload($messages, $options);

        $response = $this->request('POST', $this->getCompletionEndpoint(), $payload);

        return $this->parseCompletionResponse($response);
    }

    /**
     * Send a streaming completion request
     */
    abstract public function streamCompletion(array $messages, array $options = []): \Generator;

    protected function streamRequest(string $method, string $uri, array $payload = []): \Generator
    {
        $options = [
            'json' => $payload,
            'stream' => true,
        ];

        $resp = $this->http->request($method, $uri, $options);
        $body = $resp->getBody();

        $buffer = '';
        while (!$body->eof()) {
            $chunk = $body->read(1024);
            $buffer .= $chunk;

            while (($pos = strpos($buffer, "\n")) !== false) {
                $line = substr($buffer, 0, $pos);
                $buffer = substr($buffer, $pos + 1);
                $line = trim($line);

                if (empty($line)) {
                    continue;
                }

                if (str_starts_with($line, 'data: ')) {
                    $data = substr($line, 6);
                    if ($data === '[DONE]') {
                        return;
                    }
                    yield $data;
                }
            }
        }
    }

    abstract protected function buildCompletionPayload(array $messages, array $options): array;

    abstract protected function getCompletionEndpoint(): string;

    abstract protected function parseCompletionResponse(array $response): AIResponseInterface;

    public function embed(array $texts, array $options = []): EmbeddingResponseInterface
    {
        $payload = $this->buildEmbeddingPayload($texts, $options);
        $response = $this->request('POST', $this->getEmbeddingEndpoint(), $payload);
        return $this->parseEmbeddingResponse($response);
    }

    abstract protected function buildEmbeddingPayload(array $texts, array $options): array;

    abstract protected function getEmbeddingEndpoint(): string;

    abstract protected function parseEmbeddingResponse(array $response): EmbeddingResponseInterface;

    public function generateImage(string $prompt, array $options = []): ImageResponseInterface
    {
        $payload = $this->buildImagePayload($prompt, $options);
        $response = $this->request('POST', $this->getImageEndpoint(), $payload);
        return $this->parseImageResponse($response);
    }

    abstract protected function buildImagePayload(string $prompt, array $options): array;

    abstract protected function getImageEndpoint(): string;

    abstract protected function parseImageResponse(array $response): ImageResponseInterface;

    public function analyzeImage(string $imagePath, string $prompt, array $options = []): AIResponseInterface
    {
        $imageData = $this->encodeImage($imagePath);
        $messages = [
            ['role' => 'user', 'content' => [
                ['type' => 'text', 'text' => $prompt],
                ['type' => 'image_url', 'image_url' => ['url' => $imageData]]
            ]]
        ];

        return $this->completion($messages, $options);
    }

    protected function encodeImage(string $path): string
    {
        $mime = mime_content_type($path) ?: 'image/jpeg';
        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
    }

    abstract public function countTokens(string $text): int;

    abstract public function supports(string $feature): bool;

    protected function request(string $method, string $uri, array $payload = []): array
    {
        $attempt = 0;

        $options = [];
        if (!empty($payload)) {
            $options[$method === 'GET' ? 'query' : 'json'] = $payload;
        }

        while ($attempt < $this->retries) {
            try {
                $this->logger->debug("AI Request: {$method} {$uri}", ['payload' => $payload, 'attempt' => $attempt + 1]);
                $resp = $this->http->request($method, $uri, $options);
                $content = $resp->getBody()->getContents();
                $data = json_decode($content, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \RuntimeException('Invalid JSON response: ' . json_last_error_msg());
                }

                $this->logger->debug("AI Response: SUCCESS", ['model' => $data['model'] ?? 'unknown']);

                return $data;
            } catch (GuzzleException $e) {
                $attempt++;

                $this->logger->warning("AI Request Failed: " . $e->getMessage(), [
                    'attempt' => $attempt,
                    'code' => $e->getCode(),
                    'response' => method_exists($e, 'getResponse') ? $e->getResponse()?->getBody()->getContents() : null
                ]);

                if ($attempt >= $this->retries) {
                    throw $e;
                }

                if ($e->getCode() === 429 && method_exists($e, 'getResponse')) {
                    $retryAfter = (int) ($e->getResponse()?->getHeaderLine('Retry-After') ?: 1);
                    $this->logger->info("Rate limited. Retrying after {$retryAfter}s");
                    sleep($retryAfter);
                } else {
                    $delay = 100000 * $attempt;
                    usleep($delay);
                }
            }
        }

        throw new \RuntimeException('Max retries exceeded');
    }

    protected function buildMessages(array $messages): array
    {
        $built = [];

        foreach ($messages as $msg) {
            if (is_string($msg)) {
                $built[] = ['role' => 'user', 'content' => $msg];
            } elseif (isset($msg['role']) && isset($msg['content'])) {
                $built[] = $msg;
            }
        }

        return $built;
    }

    protected function createUsage(array $usageData, string $model): Usage
    {
        return new Usage(
            $usageData['prompt_tokens'] ?? $usageData['input_tokens'] ?? 0,
            $usageData['completion_tokens'] ?? $usageData['output_tokens'] ?? 0,
            $this->getProviderName(),
            $model
        );
    }
}
