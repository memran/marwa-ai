<?php

declare(strict_types=1);

namespace Marwa\AI\Contracts;

interface PromptTemplateInterface
{
    /**
     * Render template with variables
     */
    public function render(array $variables = []): string;

    /**
     * Add a variable
     */
    public function variable(string $name, mixed $default = null, callable $transform = null): self;

    /**
     * Add conditional block
     */
    public function when(string $condition, string $template, callable $callback = null): self;

    /**
     * Add loop block
     */
    public function loop(string $items, string $itemTemplate, callable $itemCallback = null): self;

    /**
     * Add a partial/template fragment
     */
    public function include(string $partial, array $data = []): self;

    /**
     * Get template name
     */
    public function getName(): string;

    /**
     * Get template source
     */
    public function getSource(): string;

    /**
     * Get metadata
     */
    public function getMetadata(): array;

    /**
     * Save template to file/cache
     */
    public function save(string $path): bool;

    /**
     * Compile template to PHP
     */
    public function compile(): callable;
}
