<?php

declare(strict_types=1);

namespace Marwa\AI\Support;

use Marwa\AI\Contracts\PromptTemplateInterface;

class PromptTemplate implements PromptTemplateInterface
{
    private array $conditionals = [];
    private array $loops = [];
    private array $partials = [];
    private array $metadata = [];
    private array $variables = [];

    public function __construct(private string $source, array $variables = [])
    {
        $this->variables = $variables;
        $this->parseSource();
    }

    private function parseSource(): void
    {
        preg_match_all('/\{\{(\w+)\}\}/', $this->source, $matches);
        foreach ($matches[1] as $var) {
            if (!isset($this->variables[$var])) {
                $this->variables[$var] = null;
            }
        }
    }

    public function render(array $variables = []): string
    {
        $merged = [...$this->variables, ...$variables];
        $result = $this->source;

        foreach ($merged as $key => $value) {
            if ($value !== null) {
                $result = str_replace("{{{$key}}}", (string) $value, $result);
            }
        }

        foreach ($this->partials as [$partial, $data]) {
            $result = str_replace($data['placeholder'], $partial->render($data['variables']), $result);
        }

        foreach ($this->conditionals as [$placeholder, $content]) {
            $result = str_replace($placeholder, '', $result);
        }

        foreach ($this->loops as [$placeholder, $itemTemplate]) {
            $result = str_replace($placeholder, '', $result);
        }

        return $result;
    }

    public function variable(string $name, mixed $default = null, ?callable $transform = null): self
    {
        $this->variables[$name] = $transform ? $transform($default) : $default;
        return $this;
    }

    public function when(string $condition, string $template, ?callable $callback = null): self
    {
        $placeholder = uniqid('when_', true);
        $this->conditionals[] = [$placeholder, $template];
        $this->source = str_replace($condition, $placeholder, $this->source);
        return $this;
    }

    public function loop(string $items, string $itemTemplate, ?callable $itemCallback = null): self
    {
        $placeholder = uniqid('loop_', true);
        $this->loops[] = [$placeholder, $itemTemplate];
        $this->source = str_replace($items, $placeholder, $this->source);
        return $this;
    }

    public function include(string $partial, array $data = []): self
    {
        $placeholder = uniqid('partial_', true);
        $this->partials[] = [$partial, ['placeholder' => $placeholder, 'variables' => $data]];
        $this->source = str_replace($partial, $placeholder, $this->source);
        return $this;
    }

    public function getName(): string
    {
        return '';
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function save(string $path): bool
    {
        return file_put_contents($path, $this->source) !== false;
    }

    public function compile(): callable
    {
        return fn(array $vars) => $this->render($vars);
    }

    public function withMetadata(array $metadata): self
    {
        $this->metadata = [...$this->metadata, ...$metadata];
        return $this;
    }
}
