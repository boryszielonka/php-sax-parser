<?php

declare(strict_types=1);

namespace BoZielonka\PhpSaxParser;

final readonly class FieldConfig
{
    public function __construct(
        public string       $name,
        public string|array $definition,
        public FieldType    $type = FieldType::STRING,
        public ?string      $path = null,
        public ?string      $attribute = null,
    ) {
    }

    public static function fromDefinition(string $name, string|array $definition): self
    {
        if (is_string($definition)) {
            return new self($name, $definition);
        }

        $type = FieldType::tryFrom($definition['type'] ?? 'string') ?? FieldType::STRING;
        $path = $definition['path'] ?? $definition['source'] ?? null;
        $attribute = $definition['attribute'] ?? null;

        return new self($name, $definition, $type, $path, $attribute);
    }

    public function isAttribute(): bool
    {
        return is_string($this->definition) && str_starts_with($this->definition, '@');
    }

    public function getAttributeName(): string
    {
        if (!$this->isAttribute()) {
            return '';
        }

        return substr($this->definition, 1);
    }

    public function getPath(): string
    {
        if (is_string($this->definition) && !$this->isAttribute()) {
            return $this->definition;
        }

        return $this->path ?? '';
    }

    public function matchesPath(string $currentPath): bool
    {
        if ($this->isAttribute()) {
            return false;
        }

        $fieldPath = $this->getPath();
        if (empty($fieldPath)) {
            return false;
        }

        return str_ends_with($currentPath, '/' . $fieldPath) || $currentPath === $fieldPath;
    }
}
