<?php

declare(strict_types=1);

namespace BoZielonka\PhpSaxParser\Tests;

use BoZielonka\PhpSaxParser\Config;
use BoZielonka\PhpSaxParser\ConfigParser;
use BoZielonka\PhpSaxParser\SaxParser;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SaxParserTest extends TestCase
{
    private SaxParser $parser;

    protected function setUp(): void
    {
        $this->parser = new SaxParser(
            new Config(
                new ConfigParser()->parseYamlFile(__DIR__ . '/files/configs/parsers.yaml')
            )
        );
    }

    public function testParseProductsWithConfig(): void
    {
        $config = [
            'item_element' => 'product',
            'fields' => [
                'id' => '@id',
                'name' => 'name',
                'category' => 'category',
                'brand' => 'brand',
                'price' => [
                    'type' => 'attribute',
                    'path' => 'price',
                    'attribute' => 'currency'
                ],
                'description' => 'description'
            ]
        ];

        $result = $this->parser->parseWithConfig(__DIR__ . '/files/input_products_data_1.xml', $config);

        $firstProduct = $result[0];
        self::assertIsArray($result);
        self::assertEquals('501', $firstProduct['id']);
        self::assertEquals('Ektorp Sofa', $firstProduct['name']);
    }

    public function testParsePlantsWithConfig(): void
    {
        $config = [
            'item_element' => 'plant',
            'fields' => [
                'id' => '@id',
                'common_name' => 'common_name',
                'scientific_name' => 'scientific_name',
                'taxonomy' => [
                    'type' => 'object'
                ],
                'plant_type' => 'characteristics/type',
                'height' => [
                    'type' => 'attribute',
                    'path' => 'characteristics/height',
                    'attribute' => 'unit'
                ],
                'sun_exposure' => [
                    'type' => 'array',
                    'path' => 'characteristics/sun_exposure/exposure'
                ],
                'regions' => [
                    'type' => 'array',
                    'path' => 'regional_info/region'
                ]
            ]
        ];

        $result = $this->parser->parseWithConfig(__DIR__ . '/files/input_platns_data_2.xml', $config);

        $firstPlant = $result[0];
        self::assertIsArray($result);
        self::assertEquals('T-001', $firstPlant['id']);
        self::assertEquals('Oak Tree', $firstPlant['common_name']);
    }

    public function testParseWithYamlConfig(): void
    {
        $result = $this->parser->parse(__DIR__ . '/files/input_products_data_1.xml', 'products');

        $firstProduct = $result[0];
        self::assertIsArray($result);
        self::assertEquals('501', $firstProduct['id']);
        self::assertEquals('Ektorp Sofa', $firstProduct['name']);
    }

    public function testParseInvalidFile(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('XML file not found');

        $config = ['item_element' => 'product', 'fields' => []];
        $this->parser->parseWithConfig('/non/existent/file.xml', $config);
    }

    public function testParseWithInvalidConfig(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Configuration must specify item_element');

        $config = ['fields' => []];
        $this->parser->parseWithConfig(__DIR__ . '/files/input_products_data_1.xml', $config);
    }

    public function testParseWithUnknownParser(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Parser config 'unknown' not found");

        $this->parser->parse(__DIR__ . '/files/input_products_data_1.xml', 'unknown');
    }
}
