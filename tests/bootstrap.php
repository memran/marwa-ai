<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

if (!class_exists(\PHPUnit\Framework\TestCase::class)) {
    fwrite(STDERR, "PHPUnit not installed. Run: composer require --dev phpunit/phpunit\n");
    exit(1);
}

// Set test environment variables
putenv('AI_PROVIDER=openai');
putenv('OPENAI_API_KEY=sk-test-key');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');
