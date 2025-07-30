<?php

declare(strict_types=1);

namespace BoZielonka\PhpSaxParser;

final readonly class Config
{
    public function __construct(
        public array $parsersConfig,
    ) {
    }
}
