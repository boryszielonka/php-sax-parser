<?php

declare(strict_types=1);

namespace BoZielonka\PhpSaxParser;

use InvalidArgumentException;
use RuntimeException;

final readonly class SaxParser implements SaxParserInterface
{
    public function __construct(
        private Config $config
    ) {
    }

    public function parse(string $filePath, string $parserName = 'default'): array
    {
        if (!isset($this->config->parsersConfig['parsers'][$parserName])) {
            throw new InvalidArgumentException("Parser config '{$parserName}' not found");
        }

        //        return $this->parseWithConfig($filePath, $this->parsersConfig['parsers'][$parserName]);
        return $this->parseWithConfig($filePath, $this->config->parsersConfig['parsers'][$parserName]);
    }

    /**
     * @TODO #1 consider private
     */
    public function parseWithConfig(string $filePath, array $config): array
    {
        if (!file_exists($filePath)) {
            throw new InvalidArgumentException("XML file not found: {$filePath}");
        }

        $context = new ParsingContextStacks();
        $strategy = new ConfigurableParsingStrategy($config);

        $xmlParser = xml_parser_create();
        $this->setupXmlHandlers($xmlParser, $context, $strategy);

        try {
            $this->parseFile($xmlParser, $filePath);

            return $strategy->getCollectedData();
        } finally {
            xml_parser_free($xmlParser);
        }
    }

    private function setupXmlHandlers(
        \XMLParser $xmlParser,
        ParsingContextStacks $context,
        ConfigurableParsingStrategy $strategy
    ): void {
        $startHandler = static function (
            \XMLParser $parser,
            string $name,
            array $attributes
        ) use (
            $context,
            $strategy
        ): void {
            $context->pushElement(strtolower($name), $attributes);
            $currentPath = $context->getCurrentPath();

            if ($strategy->shouldStartCollecting($currentPath, $attributes)) {
                $context->startCollecting();
            }

            $strategy->processElement($currentPath, '', $attributes, $context);
        };

        $endHandler = static function (\XMLParser $parser, string $name) use ($context, $strategy): void {
            $currentPath = $context->getCurrentPath();

            if ($strategy->shouldStopCollecting($currentPath)) {
                $strategy->finishCurrentItem();
                $context->stopCollecting();
            }

            $context->popElement();
        };

        $characterDataHandler = static function (
            \XMLParser $parser,
            string $data
        ) use (
            $context,
            $strategy
        ): void {
            $strategy->processCharacterData($data, $context);
        };

        xml_set_element_handler($xmlParser, $startHandler, $endHandler);
        xml_set_character_data_handler($xmlParser, $characterDataHandler);

        // Set parser options for better error handling
        xml_parser_set_option($xmlParser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($xmlParser, XML_OPTION_SKIP_WHITE, 1);
    }

    private function parseFile(\XMLParser $xmlParser, string $filePath): void
    {
        $handle = fopen($filePath, 'rb');
        if (!$handle) {
            throw new RuntimeException("Failed to open XML file: {$filePath}");
        }

        try {
            while (($data = fread($handle, 4096)) !== false && !empty($data)) {
                $isLastChunk = feof($handle);
                if (!xml_parse($xmlParser, $data, $isLastChunk)) {
                    $errorCode = xml_get_error_code($xmlParser);
                    // Only throw error if it's a real error (not XML_ERROR_NONE)
                    if ($errorCode !== XML_ERROR_NONE) {
                        $error = xml_error_string($errorCode);
                        $line = xml_get_current_line_number($xmlParser);
                        $column = xml_get_current_column_number($xmlParser);

                        throw new RuntimeException(
                            "XML parsing error: {$error} at line {$line}, column {$column}"
                        );
                    }
                }

                if ($isLastChunk) {
                    break;
                }
            }
        } finally {
            fclose($handle);
        }
    }
}
