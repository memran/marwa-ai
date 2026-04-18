<?php

/**
 * Marwa AI - Complete Usage Examples
 * This file demonstrates all major features
 */

require __DIR__ . '/../vendor/autoload.php';

use Marwa\AI\MarwaAI;

// Initialize
$ai = MarwaAI::instance();

echo "=== 1. Basic Completion ===\n";
$response = $ai->conversation('Hello! Who are you?')->send();
echo $response->getContent() . "\n\n";

echo "=== 2. Provider Switching ===\n";
$response = $ai->driver('anthropic')
    ->completion([['role' => 'user', 'content' => 'Say hello']]);
echo $response->getContent() . "\n\n";

echo "=== 3. Streaming ===\n";
$ai->conversation('Write a haiku about code.')
    ->stream(function ($chunk) {
        echo $chunk->getDelta();
    });
echo "\n\n";

echo "=== 4. Tools/Function Calling ===\n";
$ai->tool(ai_tool(
    'get_time',
    'Get current time',
    ['type' => 'object'],
    fn() => date('H:i:s')
));

$conv = $ai->conversation('What time is it?');
$resp = $conv->send(['tools' => $ai->getTools()]);

if ($resp->hasToolCalls()) {
    foreach ($resp->getToolCalls() as $tool) {
        echo "Tool: " . $tool->getToolName() . "\n";
        $result = $tool->execute();
        echo "Result: $result\n";
        $conv->tool($tool->getId(), $result, $tool->getToolName());
    }
    $final = $conv->send();
    echo "Final: " . $final->getContent() . "\n";
}
echo "\n";

echo "=== 5. Prompt Templates ===\n";
$template = $ai->prompt('You are a {{role}}.\nTask: {{task}}');
echo $template
    ->variable('role', 'poet')
    ->variable('task', 'Write about stars')
    ->render() . "\n\n";

echo "=== 6. Classifiers ===\n";
$sentiment = ai_classify()->sentiment('I absolutely love this!');
echo "Sentiment: " . $sentiment->getSentimentScore() . "\n";

$intent = ai_classify()->detectIntent(
    'I need help with my account',
    ['greeting', 'support', 'billing']
);
echo "Intent: " . $intent->getIntent() . "\n\n";

echo "=== 7. Batch Processing ===\n";
$batch = ai_batch();
$batch->add('completion', ['prompt' => 'Count to 3'], ['provider' => 'openai']);
$batch->add('completion', ['prompt' => 'Count to 5'], ['provider' => 'openai']);
$result = $batch->process();
echo "Progress: " . $result->getProgress() . "%\n";
echo "Completed: " . $result->getCompleted() . "\n\n";

echo "=== 8. Memory ===\n";
$memory = ai_memory();
$memory->rememberSemantic('User prefers dark mode', ['user' => 1]);
$memories = $memory->search('dark mode preferences');
echo "Found " . count($memories) . " memories\n\n";

echo "=== 9. Structured Output ===\n";
$struct = ai_structured(
    ['type' => 'object', 'properties' => [
        'name' => ['type' => 'string'],
        'age' => ['type' => 'integer']
    ]],
    [['role' => 'user', 'content' => 'John is 30']]
);
echo "Name: " . $struct->name . ", Age: " . $struct->age . "\n\n";

echo "=== 10. Embeddings ===\n";
$embeds = embed(['Hello world', 'Hi there']);
$sim = cosine_similarity(
    $embeds->getEmbedding(0),
    $embeds->getEmbedding(1)
);
echo "Similarity: " . round($sim, 4) . "\n\n";

echo "=== Complete! ===\n";
