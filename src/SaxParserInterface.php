<?php

declare(strict_types=1);

namespace BoZielonka\PhpSaxParser;

interface SaxParserInterface
{
    public function parse(string $filePath, string $parserName = 'default'): array;

    public function parseWithConfig(string $filePath, array $config): array;

    public function parseEach(string $filePath, string $parserName, callable $callback): void;

    public function parseEachWithConfig(string $filePath, array $config, callable $callback): void;
}
