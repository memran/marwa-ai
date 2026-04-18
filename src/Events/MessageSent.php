<?php

declare(strict_types=1);

namespace Marwa\AI\Events;

class MessageSent
{
    public function __construct(
        public readonly array $messages,
        public readonly string $provider,
        public readonly string $model,
        public readonly array $options = []
    ) {}
}
