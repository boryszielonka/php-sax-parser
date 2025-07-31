<?php

declare(strict_types=1);

namespace BoZielonka\PhpSaxParser;

use InvalidArgumentException;

final class ConfigurableParsingStrategy implements ParsingStrategyInterface
{
    private readonly string $itemElement;
    /** @var FieldConfig[] */
    private readonly array $fieldConfigs;
    private array $collectedItems = [];
    private array $currentItem = [];
    private bool $insideItem = false;

    public function __construct(array $config)
    {
        $this->validateConfig($config);
        $this->itemElement = $config['item_element'];
        $this->fieldConfigs = $this->buildFieldConfigs($config['fields']);
    }

    public function shouldStartCollecting(string $path, array $attributes): bool
    {
        return $path === $this->itemElement || str_ends_with($path, '/' . $this->itemElement);
    }

    public function shouldStopCollecting(string $path): bool
    {
        return $this->shouldStartCollecting($path, []);
    }

    public function processElement(
        string $path,
        string $value,
        array $attributes,
        ParsingContextStacks $context
    ): void {
        if ($this->shouldStartCollecting($path, $attributes)) {
            $this->startNewItem($attributes);

            return;
        }

        if (!$this->insideItem) {
            return;
        }

        $this->processAttributeFields($path, $attributes);
    }

    public function processCharacterData(string $data, ParsingContextStacks $context): void
    {
        $trimmedData = trim($data);
        if (!$this->insideItem || empty($trimmedData)) {
            return;
        }

        $currentPath = $context->getCurrentPath();
        $matchingField = $this->findMatchingField($currentPath);

        if ($matchingField) {
            $this->processFieldValue($matchingField, $currentPath, $trimmedData);
        }
    }

    public function getCollectedData(): array
    {
        return $this->collectedItems;
    }

    public function reset(): void
    {
        $this->collectedItems = [];
        $this->currentItem = [];
        $this->insideItem = false;
    }

    public function finishCurrentItem(): void
    {
        if ($this->insideItem && !empty($this->currentItem)) {
            $this->collectedItems[] = $this->currentItem;
            $this->currentItem = [];
        }
        $this->insideItem = false;
    }

    private function buildFieldConfigs(array $fields): array
    {
        $configs = [];
        foreach ($fields as $name => $definition) {
            $configs[] = FieldConfig::fromDefinition($name, $definition);
        }

        return $configs;
    }

    private function startNewItem(array $attributes): void
    {
        $this->insideItem = true;
        $this->currentItem = [];

        foreach ($this->fieldConfigs as $fieldConfig) {
            if ($fieldConfig->isAttribute()) {
                $attrName = $fieldConfig->getAttributeName();
                if (isset($attributes[$attrName])) {
                    $this->currentItem[$fieldConfig->name] = $attributes[$attrName];
                }
            }
        }
    }

    private function processAttributeFields(string $path, array $attributes): void
    {
        foreach ($this->fieldConfigs as $fieldConfig) {
            if ($fieldConfig->type === FieldType::ATTRIBUTE && $fieldConfig->matchesPath($path)) {
                $attrName = $fieldConfig->attribute ?? '';
                if (isset($attributes[$attrName])) {
                    $this->currentItem[$fieldConfig->name] = [
                        'value' => '',
                        $attrName => $attributes[$attrName]
                    ];
                }

                return;
            }
        }
    }

    private function findMatchingField(string $path): ?FieldConfig
    {
        return array_find(
            $this->fieldConfigs,
            static fn ($fieldConfig) => $fieldConfig->matchesPath($path)
        );
    }

    private function processFieldValue(FieldConfig $fieldConfig, string $path, string $value): void
    {
        $this->currentItem[$fieldConfig->name] = match ($fieldConfig->type) {
            FieldType::ARRAY => $this->processArrayField($fieldConfig->name, $value),
            FieldType::OBJECT => $this->processObjectField($fieldConfig->name, $path, $value),
            FieldType::ATTRIBUTE => $this->processAttributeField($fieldConfig->name, $value),
            default => $value,
        };
    }

    private function processArrayField(string $fieldName, string $value): array
    {
        if (!isset($this->currentItem[$fieldName])) {
            $this->currentItem[$fieldName] = [];
        }
        $this->currentItem[$fieldName][] = $value;

        return $this->currentItem[$fieldName];
    }

    private function processObjectField(string $fieldName, string $path, string $value): array
    {
        if (!isset($this->currentItem[$fieldName])) {
            $this->currentItem[$fieldName] = [];
        }

        $pathParts = explode('/', $path);
        $key = end($pathParts);
        $this->currentItem[$fieldName][$key] = $value;

        return $this->currentItem[$fieldName];
    }

    private function processAttributeField(string $fieldName, string $value): mixed
    {
        if (isset($this->currentItem[$fieldName]) && is_array($this->currentItem[$fieldName])) {
            $this->currentItem[$fieldName]['value'] = $value;

            return $this->currentItem[$fieldName];
        }

        return $value;
    }

    private function validateConfig(array $config): void
    {
        if (!isset($config['item_element'])) {
            throw new InvalidArgumentException('Configuration must specify item_element');
        }

        if (!isset($config['fields']) || !is_array($config['fields'])) {
            throw new InvalidArgumentException('Configuration must specify fields array');
        }
    }
}
