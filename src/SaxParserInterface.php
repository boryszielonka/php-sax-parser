<?php

declare(strict_types=1);

namespace BoZielonka\PhpSaxParser;

interface SaxParserInterface
{
    public function parse(string $filePath, string $parserName = 'default'): array;

    public function parseWithConfig(string $filePath, array $config): array;
}
