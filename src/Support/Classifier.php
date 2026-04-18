<?php

declare(strict_types=1);

namespace Marwa\AI\Support;

use Marwa\AI\Contracts\AIClientInterface;
use Marwa\AI\Contracts\ClassifierInterface;
use Marwa\AI\Contracts\ClassificationResult;
use Marwa\AI\Contracts\SentimentResult;
use Marwa\AI\Contracts\IntentResult;

class Classifier implements ClassifierInterface
{
    public function __construct(private AIClientInterface $client) {}

    public function classify(string $text, array $categories, array $options = []): ClassificationResult
    {
        $prompt = $this->buildClassificationPrompt($text, $categories, $options);
        $response = $this->client->completion([['role' => 'user', 'content' => $prompt]], $options);

        $parsed = $this->parseClassificationResponse($response->getContent(), $categories);

        return new ClassificationResult(
            $parsed['label'],
            $parsed['confidence'],
            $parsed['all_labels']
        );
    }

    public function classifyMulti(string $text, array $categories, array $options = []): array
    {
        $prompt = $this->buildMultiLabelPrompt($text, $categories);
        $response = $this->client->completion([['role' => 'user', 'content' => $prompt]], $options);
        $parsed = $this->parseMultiLabelResponse($response->getContent(), $categories);

        $results = [];
        foreach ($parsed as $category => $confidence) {
            $results[] = new ClassificationResult($category, (float) $confidence, [$category => (float) $confidence]);
        }

        return $results;
    }

    public function sentiment(string $text, array $options = []): SentimentResult
    {
        $prompt = "Analyze the sentiment of the following text. " .
            "Return JSON with 'sentiment' (positive, negative, neutral, mixed), " .
            "'score' (0-1), 'magnitude' (0-1), and optionally 'emotions' array.\n\nText: {$text}";

        $options['response_format'] = ['type' => 'json_object'];
        $response = $this->client->completion([['role' => 'user', 'content' => $prompt]], $options);

        $data = json_decode($response->getContent(), true);

        return new SentimentResult(
            $data['sentiment'] ?? 'neutral',
            (float) ($data['score'] ?? 0.5),
            (float) ($data['magnitude'] ?? 0.5),
            $data['emotions'] ?? []
        );
    }

    public function detectIntent(string $text, array $intents, array $options = []): IntentResult
    {
        $prompt = "What is the intent of this text? " .
            "Options: " . implode(', ', $intents) . ".\n\n" .
            "Return JSON with 'intent', 'confidence', and 'parameters' object.\n\nText: {$text}";

        $options['response_format'] = ['type' => 'json_object'];
        $response = $this->client->completion([['role' => 'user', 'content' => $prompt]], $options);
        $data = json_decode($response->getContent(), true);

        $allIntents = [];
        foreach ($intents as $intent) {
            $allIntents[$intent] = $intent === ($data['intent'] ?? '') ? 1.0 : 0.0;
        }

        return new IntentResult(
            $data['intent'] ?? $intents[0],
            (float) ($data['confidence'] ?? 0.5),
            $data['parameters'] ?? [],
            $allIntents
        );
    }

    public function extractEntities(string $text, array $entityTypes, array $options = []): array
    {
        $prompt = "Extract the following entities from the text: " . implode(', ', $entityTypes) . ".\n" .
            "Return JSON array of objects with 'entity', 'type', and 'value' fields.\n\n" .
            "Text: {$text}";

        $options['response_format'] = ['type' => 'json_object'];
        $response = $this->client->completion([['role' => 'user', 'content' => $prompt]], $options);
        $data = json_decode($response->getContent(), true);

        return $data['entities'] ?? [];
    }

    public function zeroShot(string $text, array $labels, string $hypothesisTemplate = ''): ClassificationResult
    {
        $template = $hypothesisTemplate ?: "This text is about {{label}}.";
        $results = [];

        foreach ($labels as $label) {
            $prompt = str_replace('{{label}}', $label, $template) . "\n\nText: {$text}";
            $response = $this->client->completion([['role' => 'user', 'content' => $prompt . "\nAnswer with YES or NO only."]]);
            $answer = trim(strtolower($response->getContent()));
            $results[$label] = $answer === 'yes' ? 1.0 : 0.0;
        }

        arsort($results);
        $top = array_key_first($results);

        return new ClassificationResult($top, $results[$top], $results);
    }

    public function fewShot(string $text, array $examples, array $options = []): ClassificationResult
    {
        $exampleText = '';
        foreach ($examples as $ex) {
            $exampleText .= "Text: {$ex['text']}\nLabel: {$ex['label']}\n\n";
        }

        $prompt = "Classify the following text into one of the given categories.\n\n" .
            $exampleText .
            "Text: {$text}\nLabel:";

        $response = $this->client->completion([['role' => 'user', 'content' => $prompt]], $options);
        $label = trim($response->getContent());

        return new ClassificationResult($label, 0.9, [$label => 0.9]);
    }

    private function buildClassificationPrompt(string $text, array $categories, array $options): string
    {
        $catList = implode(', ', $categories);
        return "Classify the following text into one of these categories: {$catList}.\n" .
            "Return JSON with 'label' (the category name), 'confidence' (0-1), and 'all_labels' object with confidence for each.\n\n" .
            "Text: {$text}";
    }

    private function buildMultiLabelPrompt(string $text, array $categories): string
    {
        $catList = implode(', ', $categories);
        return "Assign all relevant labels from: {$catList}.\n" .
            "Return JSON object mapping each label to a confidence score (0 or 1).\n\n" .
            "Text: {$text}";
    }

    private function parseClassificationResponse(string $response, array $categories): array
    {
        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['label' => $categories[0], 'confidence' => 0.0, 'all_labels' => []];
        }

        $all = [];
        foreach ($categories as $cat) {
            $all[$cat] = $data['all_labels'][$cat] ?? ($data['label'] === $cat ? 1.0 : 0.0);
        }

        return [
            'label' => $data['label'] ?? array_key_first($all),
            'confidence' => $data['confidence'] ?? max($all),
            'all_labels' => $all,
        ];
    }

    private function parseMultiLabelResponse(string $response, array $categories): array
    {
        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return array_fill_keys($categories, 0.0);
        }

        $result = [];
        foreach ($categories as $cat) {
            $result[$cat] = (float) ($data[$cat] ?? 0.0);
        }

        return $result;
    }
}
