<?php

declare(strict_types=1);

namespace Marwa\AI\Tests\Integration;

use Marwa\AI\MarwaAI;
use Marwa\AI\Tests\Helpers\MockClient;
use PHPUnit\Framework\TestCase;

class EmbeddingTest extends TestCase
{
    public function test_embedding_similarity(): void
    {
        $manager = new MarwaAI([]);
        $manager->extend('test', fn() => new MockClient('test'));

        $embeddings = $manager->driver('test')->embed(['hello world', 'hello there']);

        $this->assertCount(2, $embeddings->getEmbeddings());
        $this->assertEquals(2, $embeddings->getDimensions());

        $sim = \Marwa\AI\Support\EmbeddingResponse::similarity(
            $embeddings->getEmbedding(0),
            $embeddings->getEmbedding(1)
        );
        $this->assertIsFloat($sim);
        $this->assertGreaterThan(0, $sim);
    }

    public function test_embedding_usage(): void
    {
        $manager = new MarwaAI([]);
        $manager->extend('test', fn() => new MockClient('test'));
        $embeddings = $manager->driver('test')->embed(['test text']);

        $usage = $embeddings->getUsage();
        $this->assertInstanceOf(\Marwa\AI\Contracts\UsageInterface::class, $usage);
        $this->assertGreaterThanOrEqual(0, $usage->getPromptTokens());
    }
}
