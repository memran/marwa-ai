<?php

declare(strict_types=1);

namespace Marwa\AI\Events;

use Marwa\AI\Contracts\AIResponseInterface;

class ResponseReceived
{
    public function __construct(
        public readonly AIResponseInterface $response,
        public readonly string $provider,
        public readonly float $duration
    ) {}
}
