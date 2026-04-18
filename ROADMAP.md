# Roadmap: Marwa AI

This roadmap outlines future development. Contributions welcome!

## v1.0 - Core Stabilization

- [x] Multi-provider foundation (7+ providers)
- [x] Unified completion API
- [x] Streaming support
- [x] Tool/function calling
- [x] Prompt templates
- [x] Batch processing
- [x] Conversation memory
- [x] Text classification (sentiment, intent, entities)
- [x] Embeddings + similarity search
- [x] Image generation
- [x] Structured output (JSON schema)
- [ ] Fine-grained rate limiting per provider
- [ ] Request/response middleware hooks
- [ ] Circuit breaker pattern
- [ ] Expanded error categories
- [ ] Provider failover chains

## v1.1-1.2 - Enhanced Features

- [ ] **Async/Parallel Requests**
  - Guzzle async pool
  - Promise-based batch API
  - Concurrent tool execution

- [ ] **Advanced Caching**
  - PSR-6/16 adapters
  - Response + embedding cache
  - Semantic cache keys

- [ ] **Smart Routing**
  - Cost-aware model selection
  - Load balancing across providers
  - Automatic failover

- [ ] **Safety & Guardrails**
  - Content filtering
  - PII detection & redaction
  - Custom validation hooks

- [ ] **Observability**
  - OpenTelemetry traces
  - Structured logging (PSR-3)
  - Metrics (Prometheus format)

## v1.3-1.4 - Multi-Modal & RAG

- [ ] **Audio**
  - Speech-to-text (Whisper, etc.)
  - Text-to-speech
  - Audio transcription

- [ ] **Vision**
  - Vision model support (GPT-4V, Claude 3, Gemini)
  - Image analysis
  - Document OCR

- [ ] **RAG Pipeline**
  - Document loaders (PDF, DOCX, HTML, Markdown)
  - Chunking strategies
  - Vector DB adapters (Pinecone, Weaviate, pgvector)
  - Hybrid search
  - Context compression

## v1.5+ - Ecosystem

- [ ] **Framework Integrations** (community packages)
  - Symfony bundle
  - Slim/Laminas middleware

- [ ] **More Providers**
  - Cohere
  - Together AI
  - Replicate
  - Hugging Face
  - Azure OpenAI
  - AWS Bedrock
  - Local (llama.cpp, transformers)

- [ ] **Developer Tools**
  - VS Code extension
  - Web playground
  - Prompt library manager
  - Cost calculator

- [ ] **Production Features**
  - Connection pooling
  - Request prioritization
  - Budget tracking & alerts
  - Audit logs
  - SLA monitoring

## Notes

- This roadmap is community-driven. Open issues for feature requests.
- Breaking changes will follow semantic versioning.
- Framework-specific integrations are best as separate packages.
