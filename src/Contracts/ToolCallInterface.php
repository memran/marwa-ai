<?php

declare(strict_types=1);

namespace Marwa\AI\Contracts;

interface ToolCallInterface extends \Stringable
{
    /**
     * Get the tool call ID
     */
    public function getId(): string;

    /**
     * Get the tool/function name
     */
    public function getToolName(): string;

    /**
     * Get the arguments as associative array
     */
    public function getArguments(): array;

    /**
     * Get raw arguments JSON
     */
    public function getRawArguments(): string;

    /**
     * Execute the tool call
     */
    public function execute(): mixed;
}
