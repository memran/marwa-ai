# Marwa AI

A comprehensive AI abstraction library for PHP with unified support for multiple AI providers.

## Features

- **Multi-provider support**: OpenAI, Anthropic Claude, Google Gemini, xAI Grok, Mistral, DeepSeek, Ollama
- **Unified API**: Same interface across all providers
- **Streaming**: Real-time token streaming
- **Function/Tool calling**: Structured function execution
- **Prompt templates**: Reusable template system with variables, conditionals, loops
- **Conversation memory**: Persistent context across requests
- **Batch processing**: Queue and process multiple AI requests
- **MCP server support**: Model Context Protocol integration
- **Text classification**: Sentiment, intent, entity extraction
- **Embeddings**: Vector generation and similarity search
- **Image generation**: DALL-E integration
- **Structured output**: JSON schema enforcement

## Installation

```bash
composer require memran/marwa-ai
```

## Quick Start

```php
use Marwa\AI\MarwaAI;

// Simple completion
$response = MarwaAI::instance()->conversation('Hello, how are you?')->send();
echo $response->getContent();

// With provider-specific options
$response = MarwaAI::driver('anthropic')
    ->completion([
        ['role' => 'user', 'content' => 'Explain quantum computing']
    ], [
        'temperature' => 0.7,
        'max_tokens' => 500,
    ]);
```

### Using the Helper Functions

```php
use function Marwa\AI\{ai, complete, conversation};

// Quick completion
$result = complete('Tell me a joke');
echo $result->getContent();

// Create conversation with helper
$conv = conversation('You are a helpful assistant');
$conv->user('What is PHP?')
     ->send()
     ->getContent();
```

## Conversations

```php
$conv = ai()->conversation([
    ['role' => 'system', 'content' => 'You are a PHP expert.'],
    ['role' => 'user', 'content' => 'What is dependency injection?']
]);

$response = $conv->send();
echo $response->getContent();

// Continue the conversation
$conv->user('Can you show me an example?')
     ->send();
```

### Context & Metadata

```php
$conv = ai()->conversation()
    ->withContext(['user_id' => 123, 'locale' => 'en'])
    ->setSystem('Remember the user prefers concise answers.');

// Later, restore from storage
$conv = \Marwa\AI\Support\Conversation::fromArray($savedData);
```

### Streaming

```php
// Using the helper
use function Marwa\AI\stream;

stream('Write a poem about code', function ($chunk) {
    echo $chunk->getDelta();
    flush();
});

// Or using conversation directly
foreach (ai()->conversation('Write a poem about code')->stream() as $chunk) {
    echo $chunk->getDelta();
}
```

## Prompt Templates

```php
$template = ai()->prompt('
    You are a {{role}} expert.
    {{#show_tips}}
    Provide practical examples.
    {{/show_tips}}
    Question: {{question}}
');

$response = $template
    ->variable('role', 'PHP developer')
    ->variable('show_tips', true)
    ->render([
        'question' => 'What is a closure?'
    ]);
```

## Tools / Function Calling

```php
use function Marwa\AI\ai_tool;

ai()->tool(ai_tool(
    'get_weather',
    'Get current weather for a location',
    [
        'type' => 'object',
        'properties' => [
            'city' => ['type' => 'string', 'description' => 'City name'],
            'country' => ['type' => 'string', 'description' => 'Country code'],
        ],
        'required' => ['city'],
    ],
    function (array $args) {
        // Your business logic here
        return "Weather in {$args['city']}: 22°C, sunny";
    }
));

$conv = ai()->conversation('What is the weather in Paris?');
$response = $conv->send(['tools' => ai()->getTools()]);

if ($response->hasToolCalls()) {
    // Auto-execute tools and continue
    $final = $conv->continueWithTools(ai()->getTools());
    echo $final->getContent();
}
```

## Classification

```php
use function Marwa\AI\ai_classify;

// Sentiment analysis
$sentiment = ai_classify()->sentiment('I love this product!');
if ($sentiment->isPositive()) {
    echo "Positive score: " . $sentiment->getSentimentScore();
}

// Intent detection
$intent = ai_classify()->detectIntent(
    'I need to reset my password',
    ['greeting', 'password_reset', 'billing', 'technical_support']
);
echo "Detected: " . $intent->getIntent();
echo "Confidence: " . $intent->getConfidence();

// Entity extraction
$entities = ai_classify()->extractEntities(
    'Contact John Doe at john@example.com',
    ['person', 'email', 'organization']
);

// Zero-shot classification
$result = ai_classify()->zeroShot(
    'The new iPhone has an amazing camera',
    ['technology', 'sports', 'politics']
);
```

## Batch Processing

```php
$batch = ai_batch();

// Add multiple tasks
$batch->add('completion', ['prompt' => 'Summarize article A'], ['provider' => 'openai']);
$batch->add('completion', ['prompt' => 'Summarize article B'], ['provider' => 'openai']);
$batch->add('embedding', ['texts' => ['doc1', 'doc2']], ['provider' => 'openai']);

// Process all
$result = $batch->process();

foreach ($result->getResults() as $id => $output) {
    echo "Task {$id}: Done\n";
}

foreach ($result->getFailures() as $failure) {
    echo "Task {$failure['id']} failed: {$failure['error']}\n";
}
```

## Memory & Context

```php
use function Marwa\AI\ai_memory;

$memory = ai_memory();

// Store semantic memories for RAG
$memory->rememberSemantic(
    'Users prefer dark mode with large fonts',
    ['user_id' => 123, 'category' => 'preferences']
);

// Search by semantic similarity
$results = $memory->search('interface preferences');
foreach ($results as $match) {
    echo "Score: {$match['score']}\n";
    echo "Memory: {$match['memory']['text']}\n";
}

// Conversation history storage
$memory->storeMessages('conv_123', $messages);
$history = $memory->getMessages('conv_123');
```

## Embeddings

```php
use function Marwa\AI\embed;
use function Marwa\AI\cosine_similarity;

$embeddings = embed(['Text 1', 'Text 2', 'Text 3']);

// Get individual vectors
$vec1 = $embeddings->getEmbedding(0);

// Cosine similarity
$sim = cosine_similarity(
    $embeddings->getEmbedding(0),
    $embeddings->getEmbedding(1)
);

// Dimensions
$dim = $embeddings->getDimensions();
```

## Image Generation

```php
use function Marwa\AI\image;

$images = image('A sunset over mountains', [
    'size' => '1024x1024',
    'quality' => 'hd',
    'n' => 3,
]);

foreach ($images->getUrls() as $url) {
    echo "Image URL: {$url}\n";
}

// Save to disk
$paths = $images->save('/path/to/save', 'sunset_');
```

## Structured Output

```php
use function Marwa\AI\ai_structured;

$result = ai_structured(
    [
        'type' => 'object',
        'properties' => [
            'name' => ['type' => 'string'],
            'age' => ['type' => 'integer'],
            'skills' => [
                'type' => 'array',
                'items' => ['type' => 'string']
            ],
        ],
    ],
    [
        ['role' => 'user', 'content' => 'Extract: John is 30 and knows PHP, JS']
    ]
);

echo $result->name;  // "John"
echo $result->age;   // 30
echo implode(', ', $result->skills); // "PHP, JS"
```

## Health Checks

```php
use function Marwa\AI\ai_health;

$health = ai_health('openai');

if ($health->check()->isOperational()) {
    echo "Latency: " . $health->getLatency() . "ms\n";
    $models = $health->getModels();
    print_r($models);
}
```

## Configuration

### Standalone Usage (Singleton)

```php
use Marwa\AI\MarwaAI;

$ai = MarwaAI::initialize([
    'default' => 'anthropic',
    'anthropic' => [
        'api_key' => 'sk-ant-...',
        'model' => 'claude-3-sonnet',
    ],
    'openai' => [
        'api_key' => 'sk-...',
        'model' => 'gpt-4o',
    ],
]);

$response = $ai->conversation('Hello')->send();
```

### Alternative: Application Instance (Non-singleton)

If you prefer not to use the global singleton, you can instantiate the `Application` class.

```php
use Marwa\AI\Application;

$ai = new Application([
    'default' => 'openai',
    'openai' => ['api_key' => '...']
]);

$response = $ai->conversation('Hello')->send();
```

### Using a Config File

```php
// config/ai.php
return [
    'default' => getenv('AI_PROVIDER') ?: 'ollama',

    'providers' => [
        'ollama' => [
            'base_url' => getenv('OLLAMA_BASE_URL') ?: 'http://localhost:11434/v1/',
            'model' => getenv('OLLAMA_MODEL') ?: 'llama3.2',
        ],
    ],

    'memory' => [
        'driver' => 'array',
        'prefix' => 'marwa_ai:',
        'similarity_threshold' => 0.75,
    ],
];

// Usage
$ai = MarwaAI::initialize(require 'config/ai.php');
```

## Chain / Pipeline

```php
use function Marwa\AI\ai;

$chain = ai()->chain([
    fn($input) => "Analyze: {$input}",
    fn($analysis) => strtoupper($analysis),
    fn($upper) => "Final: {$upper}",
]);

$result = $chain->execute();

// Streaming chain
foreach ($chain->stream() as $chunk) {
    echo $chunk->getDelta();
}

// View execution history
$history = $chain->getHistory();
foreach ($history->getSteps() as $step) {
    printf(
        "Step %s: %.3fs\n",
        $step['name'],
        $step['duration']
    );
}
```

## MCP Server

```php
use Marwa\AI\Contracts\MCPServerInterface;
use Marwa\AI\Contracts\MCPResponse;

$server = new class implements MCPServerInterface {
    public function getName(): string { return 'my-server'; }
    public function getVersion(): string { return '1.0.0'; }
    public function getCapabilities() { /* ... */ }
    public function listTools(): array { return [/* ... */]; }
    public function handleToolCall(string $tool, array $args): MCPResponse {
        // Handle MCP tool calls
        return MCPResponse::success($result);
    }
};

ai()->registerMcpServer('my-server', $server);
```

## Error Handling

```php
use Marwa\AI\Exceptions\AIException;
use Marwa\AI\Exceptions\ConfigurationException;
use Marwa\AI\Exceptions\RateLimitException;

try {
    $resp = ai()->driver('openai')->completion($messages);
} catch (RateLimitException $e) {
    echo "Rate limited. Retry after {$e->retryAfter} seconds";
    sleep($e->retryAfter);
} catch (ConfigurationException $e) {
    echo "Check your API key configuration";
} catch (AIException $e) {
    echo "AI error: " . $e->getMessage();
}
```

## CLI Tool

```bash
# Interactive chat session
php bin/marwa-ai chat --provider=openai

# Single question
php bin/marwa-ai ask "What is PHP?"

# List available providers
php bin/marwa-ai providers
```

## Providers Reference

| Provider | Streaming | Tools | Vision | Embeddings |
|----------|-----------|-------|--------|------------|
| OpenAI | Yes | Yes | Yes | Yes |
| Anthropic Claude | Yes | Yes | Yes | Yes |
| Google Gemini | Yes | Yes | Yes | Yes |
| xAI Grok | Yes | Yes | Yes | No |
| Mistral | Yes | Yes | No | Yes |
| DeepSeek | Yes | Yes | No | Yes |
| Ollama (local) | Yes | Yes | Yes | Yes* |

*Embeddings depend on the loaded model

## Environment Variables

| Variable | Purpose |
|----------|---------|
| `AI_PROVIDER` | Default provider |
| `OPENAI_API_KEY` | OpenAI credentials |
| `OPENAI_MODEL` | OpenAI model override |
| `ANTHROPIC_API_KEY` | Anthropic credentials |
| `ANTHROPIC_MODEL` | Anthropic model override |
| `GOOGLE_API_KEY` | Google credentials |
| `GOOGLE_MODEL` | Google model override |
| `XAI_API_KEY` | xAI Grok credentials |
| `MISTRAL_API_KEY` | Mistral credentials |
| `DEEPSEEK_API_KEY` | DeepSeek credentials |
| `OLLAMA_BASE_URL` | Ollama endpoint |

## License

MIT
