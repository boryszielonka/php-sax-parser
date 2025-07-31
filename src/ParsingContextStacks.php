<?php

declare(strict_types=1);

namespace BoZielonka\PhpSaxParser;

use RuntimeException;
use SplStack;

final class ParsingContextStacks
{
    private SplStack $elementStack;
    private SplStack $dataStack;
    private string $currentPath = '';
    private array $currentData = [];

    public function __construct()
    {
        $this->elementStack = new SplStack();
        $this->dataStack = new SplStack();
    }

    public function pushElement(string $element, array $attributes): void
    {
        $elementInfo = [
            'name' => $element,
            'attributes' => $attributes,
            'path' => $this->buildPath($element)
        ];

        $this->elementStack->push($elementInfo);
        $this->currentPath = $elementInfo['path'];

        $this->dataStack->push($this->currentData);
        $this->currentData = [];
    }

    public function popElement(): string
    {
        if ($this->elementStack->isEmpty()) {
            throw new RuntimeException('Cannot pop element from empty stack');
        }

        $elementInfo = $this->elementStack->pop();

        // Restor prev data state
        $this->currentData = $this->dataStack->pop();

        // Update current path
        $this->currentPath = $this->elementStack->isEmpty()
            ? ''
            : $this->elementStack->top()['path'];

        return $elementInfo['name'];
    }

    public function getCurrentPath(): string
    {
        return $this->currentPath;
    }

    public function startCollecting(): void
    {
        $this->currentData = [];
    }

    public function stopCollecting(): array
    {
        $data = $this->currentData;
        $this->currentData = [];

        return $data;
    }

    private function buildPath(string $element): string
    {
        if ($this->elementStack->isEmpty()) {
            return $element;
        }

        return $this->currentPath . '/' . $element;
    }
}
