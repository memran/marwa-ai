<?php

require __DIR__ . '/../vendor/autoload.php';

use Marwa\AI\MarwaAI;

/**
 * Marwa AI - Ollama Usage Example
 * 
 * Before running this:
 * 1. Install Ollama (https://ollama.com)
 * 2. Run: ollama run llama3.2 (or your preferred model)
 * 3. Ensure the Ollama server is running (usually on port 11434)
 */

// 1. Initialize with Ollama config
$ai = MarwaAI::initialize([
    'default' => 'ollama',
    'ollama' => [
        'base_url' => 'http://localhost:11434/v1/', // Standard Ollama OpenAI-compatible endpoint
        'model' => 'qwen3.5:0.8b', // Change this to your installed model
    ],
]);

echo "--- Ollama Simple Chat ---\n";

try {
    $response = $ai->conversation('Hello! What can you do?')
        ->send();

    echo "AI: " . $response->getContent() . "\n\n";

    echo "--- Ollama Streaming ---\n";
    echo "AI: ";

    $stream = $ai->conversation('Explain quantum computing in one sentence.')
        ->stream();

    foreach ($stream as $chunk) {
        echo $chunk->getDelta();
    }
    echo "\n\n";

    echo "--- Usage Stats ---\n";
    $stats = $ai->stats();
    print_r($stats);
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Make sure Ollama is running at http://localhost:11434\n";
}
