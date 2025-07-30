<?php

declare(strict_types=1);

namespace BoZielonka\PhpSaxParser;

use Exception;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;

final class ConfigParser
{
    public function parseYamlFile(string $filePath): array
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new RuntimeException("Failed to read configuration file: {$filePath}");
        }

        return $this->parseYamlString($content);
    }

    public function parseYamlString(string $yamlContent): array
    {
        try {
            $config = Yaml::parse($yamlContent);

            if (!is_array($config)) {
                throw new InvalidArgumentException('Configuration must be a valid YAML array');
            }

            return $this->validateAndNormalizeConfig($config);
        } catch (Exception $e) {
            throw new InvalidArgumentException("Invalid YAML configuration: " . $e->getMessage());
        }
    }

    public function parseArrayConfig(array $config): array
    {
        return $this->validateAndNormalizeConfig($config);
    }

    private function validateAndNormalizeConfig(array $config): array
    {
        if (!isset($config['parsers']) || !is_array($config['parsers'])) {
            throw new InvalidArgumentException('Configuration must contain a "parsers" array');
        }

        foreach ($config['parsers'] as $parserName => $parserConfig) {
            $this->validateParserConfig($parserName, $parserConfig);
        }

        return $config;
    }

    private function validateParserConfig(string $parserName, mixed $parserConfig): void
    {
        if (!is_array($parserConfig)) {
            throw new InvalidArgumentException("Parser configuration for '{$parserName}' must be an array");
        }

        // Required fields
        if (!isset($parserConfig['item_element'])) {
            throw new InvalidArgumentException("Parser '{$parserName}' must specify 'item_element'");
        }

        if (!isset($parserConfig['fields']) || !is_array($parserConfig['fields'])) {
            throw new InvalidArgumentException("Parser '{$parserName}' must specify 'fields' as an array");
        }

        // Validate root_element if present
        if (isset($parserConfig['root_element']) && !is_string($parserConfig['root_element'])) {
            throw new InvalidArgumentException("Parser '{$parserName}' root_element must be a string");
        }

        // Validate fields
        foreach ($parserConfig['fields'] as $fieldName => $fieldConfig) {
            $this->validateFieldConfig($parserName, $fieldName, $fieldConfig);
        }
    }

    private function validateFieldConfig(string $parserName, string $fieldName, mixed $fieldConfig): void
    {
        if (is_string($fieldConfig)) {
            // Simple string mapping is valid
            return;
        }

        if (!is_array($fieldConfig)) {
            throw new InvalidArgumentException(
                "Field '{$fieldName}' in parser '{$parserName}' must be a string or array"
            );
        }

        // Validate field type if specified
        if (isset($fieldConfig['type'])) {
            $validTypes = ['string', 'array', 'object', 'attribute', 'integer', 'float', 'boolean'];
            if (!in_array($fieldConfig['type'], $validTypes, true)) {
                throw new InvalidArgumentException(
                    "Field '{$fieldName}' in parser '{$parserName}' has invalid type. " .
                    "Valid types: " . implode(', ', $validTypes)
                );
            }
        }

        // Validate path/source if specified
        if (isset($fieldConfig['path']) && !is_string($fieldConfig['path'])) {
            throw new InvalidArgumentException(
                "Field '{$fieldName}' in parser '{$parserName}' path must be a string"
            );
        }

        if (isset($fieldConfig['source']) && !is_string($fieldConfig['source'])) {
            throw new InvalidArgumentException(
                "Field '{$fieldName}' in parser '{$parserName}' source must be a string"
            );
        }

        // Validate nested fields for object types
        if (isset($fieldConfig['fields']) && !is_array($fieldConfig['fields'])) {
            throw new InvalidArgumentException(
                "Field '{$fieldName}' in parser '{$parserName}' nested fields must be an array"
            );
        }
    }
}
