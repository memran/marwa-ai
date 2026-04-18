# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Marwa AI** — A PHP 8.2+ AI abstraction library providing a unified API across multiple AI providers (OpenAI, Anthropic Claude, Google Gemini, xAI Grok, Mistral, DeepSeek, Ollama). It includes streaming support, tool/function calling, conversation memory, batch processing, MCP server support, text classification, embeddings, and image generation.

**Namespace**: `Marwa\AI` (all code lives under this root namespace)

**License**: MIT

## Common Development Commands

### Setup

```bash
# Install dependencies
composer install

# Publish config (if applicable)
# For framework integrations, publish config via respective commands
```

### Testing

```bash
# Run all tests
./vendor/bin/phpunit

# Run specific test file
./vendor/bin/phpunit tests/Unit/AIManagerTest.php

# Run single test method
./vendor/bin/phpunit --filter=test_can_create_manager tests/Unit/AIManagerTest.php

# Run with coverage
XDEBUG_MODE=coverage ./vendor/bin/phpunit --coverage-html coverage/

# Watch mode (if installed)
./vendor/bin/phpunit --watch
```

### Linting & Static Analysis

```bash
# PHP syntax check on all files
find src -name "*.php" -exec php -l {} \;

# PHPStan (level 6, configured in phpstan.neon)
composer analyse
# or directly:
./vendor/bin/phpstan analyse --memory-limit=256M

# PHP-CS-Fixer
composer fix
composer lint
```

### CLI Tool

```bash
# Standalone chat interface
php bin/marwa-ai chat --provider=openai

# Ask a single question
php bin/marwa-ai ask "What is PHP?"

# List available providers
php bin/marwa-ai providers
```

## Architecture

### Layered Structure

```
Marwa\AI
├── Contracts/          # 30+ PHP interfaces + value objects (enums, DTOs)
│   ├── AIClientInterface.php       # Core client contract
│   ├── AIManagerInterface.php      # Manager/orchestrator contract
│   ├── ConversationInterface.php
│   ├── AIResponseInterface.php
│   ├── EmbeddingResponseInterface.php
│   ├── ImageResponseInterface.php
│   ├── BatchProcessorInterface.php
│   ├── ChainInterface.php
│   ├── ClassifierInterface.php
│   ├── MemoryManagerInterface.php
│   ├── PromptTemplateInterface.php
│   ├── ToolDefinitionInterface.php
│   ├── MCPServerInterface.php      # MCP protocol support
│   └── [Result types, enums, value objects]
├── Drivers/            # Provider implementations (extend BaseDriver)
│   ├── BaseDriver.php              # Abstract base with common logic
│   ├── OpenAIClient.php            # OpenAI (GPT-4, DALL-E)
│   ├── AnthropicClient.php         # Claude (Opus, Sonnet, Haiku)
│   ├── GoogleAIClient.php          # Gemini Pro
│   ├── GrokClient.php              # xAI Grok
│   ├── MistralClient.php           # Mistral Large/Medium
│   ├── DeepSeekClient.php          # DeepSeek Chat
│   └── OllamaClient.php            # Local Ollama
├── Support/            # Concrete implementations & utilities
│   ├── MarwaAI.php                 # Global singleton facade (main entry)
│   ├── AIManager.php               # Orchestrator (delegates to drivers)
│   ├── AIClientFactory.php         # Factory for driver instantiation
│   ├── Conversation.php            # Message management + send/stream
│   ├── Message.php                 # Simple message value object
│   ├── AIResponse.php              # Standardized response
│   ├── Usage.php                   # Token counting + cost calculation
│   ├── EmbeddingResponse.php       # Vector storage + cosine similarity
│   ├── ImageResponse.php           # Generated image handling
│   ├── StreamChunk.php             # Streaming delta
│   ├── PromptTemplate.php          # Variable templating ({{var}}, conditionals)
│   ├── Chain.php                   # Pipeline/chain execution
│   ├── ChainHistory.php            # Execution history
│   ├── BatchProcessor.php          # Batch job queueing
│   ├── BatchResult.php
│   ├── Classifier.php              # Sentiment/intent/entity/zero-shot
│   ├── MemoryManager.php           # Context storage + semantic search
│   ├── ToolCall.php                # Tool invocation wrapper
│   ├── ToolDefinition.php          # Tool schema + callback
│   ├── HealthChecker.php           # Provider health checks
│   ├── StructuredResponse.php      # JSON schema extraction result
│   └── helpers.php                 # Global functions: ai(), complete(), embed(), etc.
├── Console/           # CLI commands
│   ├── TestCommand.php             # marwa-ai:test health check
│   ├── ClearCommand.php            # marwa-ai:clear cache/memory
│   ├── MakeToolCommand.php         # marwa-ai:make-tool stub
│   └── BatchCommand.php            # marwa-ai:batch demo
├── Exceptions/
│   ├── AIException.php             # Base exception
│   ├── ConfigurationException.php
│   └── RateLimitException.php
├── Application.php   # Standalone entry point
└── helpers.php       # Global helper functions (loaded via composer)
```

### Design Patterns

- **Facade**: `Marwa\AI\MarwaAI` is a singleton facade delegating to `AIManager`.
- **Factory**: `AIClientFactory` maps provider names to driver classes.
- **Strategy**: Each driver implements `AIClientInterface` with provider-specific logic.
- **Template Method**: `BaseDriver` defines request/response flow; concrete drivers implement provider-specific payload building.
- **Chain of Responsibility**: `Chain` executes sequential callable steps with retry/conditional branches.
- **Registry**: `AIManager` holds registered tools and instantiated drivers.

### Request Flow

1. Entry: `MarwaAI::instance()` or `ai()` helper → returns singleton `MarwaAI` facade.
2. Manager: `MarwaAI` delegates to internal `AIManager`.
3. Driver: `AIManager::driver('openai')` returns cached `OpenAIClient` via `AIClientFactory`.
4. Conversation: `ai()->conversation($messages)` creates `Conversation` with a client.
5. Send: `$conv->send()` → `$client->completion($messages)` → `BaseDriver::request()` → HTTP API.
6. Response: Raw provider response → driver-specific `parseCompletionResponse()` → standardized `AIResponse`.

### Streaming

All drivers implement `streamCompletion()` returning `\Generator` yielding `StreamChunk` objects:
- `$chunk->getDelta()` — incremental text delta
- `$chunk->isFinished()` — true on final chunk (check for usage + finish reason)
- Accumulated content retrievable via `$chunk->getAccumulated()` (internal state).

### Tools / Function Calling

1. Define: `ai_tool('name', 'description', ['type' => 'object', 'properties' => [...]], callable)` returns `ToolDefinition`.
2. Register: `ai()->tool($toolDefinition)`.
3. Pass to API: `['tools' => ai()->getTools()]` in options.
4. Tool calls appear in `$response->getToolCalls()`.
5. Execute: `$toolCall->execute()` calls the registered callback.
6. Continue: `$conversation->continueWithTools($tools)` auto-handles tool → result → final response loop.

### Classification

`ai_classify()` returns `Classifier` with:
- `sentiment($text)` → `SentimentResult` (positive/negative/neutral/mixed, score, magnitude)
- `detectIntent($text, $intents)` → `IntentResult`
- `extractEntities($text, $types)` → array of entities
- `zeroShot($text, $labels)` → `ClassificationResult`
- `fewShot($text, $examples)` → few-shot prediction
- `classify($text, $categories)` → single-label
- `classifyMulti($text, $categories)` → multi-label array

### Memory & Semantic Search

`ai_memory()` returns `MemoryManager`:
- `set($key, $value, $ttl)` / `get($key)` — key-value storage with optional TTL.
- `storeMessages($convId, $messages)` / `getMessages($convId)` — conversation history.
- `summarize($convId, $maxTokens)` — AI-powered summarization if configured.
- `rememberSemantic($text, $metadata)` — stores text + embedding (requires AI instance).
- `search($query, $limit, $threshold)` — similarity search using cosine distance.

### Batch Processing

`ai_batch()` → `BatchProcessor`:
- `add('completion', ['prompt' => '...'], ['provider' => 'openai'])`
- `add('embedding', ['texts' => [...]], ['provider' => 'openai'])`
- `add('image', 'prompt text', ['provider' => 'openai'])`
- `process()` → `BatchResult` (getResults(), getFailures(), getProgress()).

### Prompt Templates

`ai()->prompt($templateString)`:
- `{{variable}}` — replaced via `variable()` or `render([$vars])`
- `when($conditionPlaceholder, $template)` — conditional inclusion
- `include($partialTemplate, $data)` — nested templates
- `loop($itemsPlaceholder, $itemTemplate)` — iteration (manual implementation)
- `compile()` → returns PHP callable

### Provider Configuration

Providers auto-detected by `AIClientFactory`:
- Keys: `openai`, `anthropic`, `google`, `xai`, `grok`, `mistral`, `deepseek`, `ollama`.
- Config keys: `api_key`, `model`, `base_url`, `timeout`, `retries`.
- Env vars used as fallback (e.g. `OPENAI_API_KEY`).

### Important Implementation Notes

- All typed properties, strict_types=1.
- PHP 8.2 style (readonly classes/properties where applicable).
- No legacy PHP patterns.
- Error hierarchy: `AIException` → `ConfigurationException`, `RateLimitException`.
- `BaseDriver::request()` handles retries + rate limit backoff (429 → Retry-After).
- Streaming parsers are stateful — each line (data: ...) parsed as Server-Sent Events.
- Embedding similarity uses cosine similarity (static method on `EmbeddingResponse`).

## Key Files to Read for Understanding

1. **Entry point**: `src/MarwaAI.php` — singleton facade.
2. **Orchestration**: `src/AIManager.php` — core workflows.
3. **Driver abstraction**: `src/Drivers/BaseDriver.php` — common HTTP flow.
4. **Initial sample**: `src/Drivers/OpenAIClient.php` — OpenAI provider.
5. **Conversation flow**: `src/Support/Conversation.php` — message handling.
6. **Tool calling**: `src/Support/ToolDefinition.php`, `src/Support/ToolCall.php`.
7. **Contracts**: `src/Contracts/AIClientInterface.php` — full API surface.

## Code Style

- PSR-12 coding standard.
- Strict types everywhere.
- No `/* ... */` block comments — one-line `//` only for non-obvious reasoning.
- Typed properties and return types on all methods.
- Prefer constructor property promotion.
- Use `private` visibility; `protected` only for driver internals.
- Nullable types written as `?Type`, never `Type|null`.
- No `declare(strict_types=0)` — always 1.

## Testing Philosophy

- Unit tests: mocks, no network (MockClient in `tests/Helpers/MockClient.php`).
- Integration tests: real HTTP calls are allowed but typically skip without API keys via `@requires` or env checks.
- New features: add both unit (fast, isolated) and at least one integration test if API-dependent.
- Test file naming: `*Test.php` in `tests/Unit/` or `tests/Integration/`.

## Things to Avoid

- Do not add new driver without extending `BaseDriver` and implementing all abstract methods.
- Do not modify `AIResponse`, `Usage`, `EmbeddingResponse` to diverge from contracts.
- Do not push API keys or credentials in code or config examples.
- Do not bypass rate-limit handling — `BaseDriver::request()` must be used for all HTTP.

## Environment Variables

| Variable | Purpose |
|----------|---------|
| `AI_PROVIDER` | Default provider (openai, anthropic, etc.) |
| `OPENAI_API_KEY`, `ANTHROPIC_API_KEY`, `GOOGLE_API_KEY`, `XAI_API_KEY`, `MISTRAL_API_KEY`, `DEEPSEEK_API_KEY` | Provider credentials |
| `OLLAMA_BASE_URL` | Ollama endpoint (default http://localhost:11434/v1/) |
| `OPENAI_MODEL`, `ANTHROPIC_MODEL`, etc. | Provider-specific model override |

## CI / Quality Gates

(If present in repo — check `.github/workflows/`)
- `phpunit` must pass.
- `phpstan` level 6 must pass (`composer analyse`).
- `php-cs-fixer` dry-run must be clean (`composer lint`).
- No new `var_dump()`/`dd()` in committed code.

## Database / External Services

None required for core. Optional:
- Redis for production memory backend (currently array-based).
- MCP servers registered via `registerMcpServer()`.
