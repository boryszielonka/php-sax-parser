<?php

declare(strict_types=1);

namespace BoZielonka\PhpSaxParser;

interface ParsingStrategyInterface
{
    public function shouldStartCollecting(string $path, array $attributes): bool;

    public function shouldStopCollecting(string $path): bool;

    public function processElement(string $path, string $value, array $attributes, ParsingContextStacks $context): void;

    public function processCharacterData(string $data, ParsingContextStacks $context): void;

    public function getCollectedData(): array;

    public function reset(): void;
}
