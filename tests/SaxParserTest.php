<?php

declare(strict_types=1);

namespace BoZielonka\PhpSaxParser\Tests;

use BoZielonka\PhpSaxParser\Config;
use BoZielonka\PhpSaxParser\ConfigParser;
use BoZielonka\PhpSaxParser\SaxParser;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

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
        self::assertEquals('Living Room Furniture', $firstProduct['category']);
        self::assertEquals('IKEA', $firstProduct['brand']);
        self::assertEquals(['value' => '599.00', 'currency' => 'USD'], $firstProduct['price']);
        self::assertStringContainsString('comfortable', $firstProduct['description']);
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
        self::assertEquals('Quercus robur', $firstPlant['scientific_name']);
        self::assertEquals('Deciduous Tree', $firstPlant['plant_type']);
        self::assertEquals(['value' => '40', 'unit' => 'meters'], $firstPlant['height']);
        self::assertEquals(['Full Sun'], $firstPlant['sun_exposure']);
        self::assertEquals(['Europe', 'North Africa', 'Western Asia'], $firstPlant['regions']);
    }

    public function testParseWithYamlConfig(): void
    {
        $result = $this->parser->parse(__DIR__ . '/files/input_products_data_1.xml', 'products');

        $firstProduct = $result[0];
        self::assertIsArray($result);
        self::assertEquals('501', $firstProduct['id']);
        self::assertEquals('Ektorp Sofa', $firstProduct['name']);
        self::assertEquals('Living Room Furniture', $firstProduct['category']);
        self::assertEquals('IKEA', $firstProduct['brand']);
        self::assertIsArray($firstProduct['price']);
        self::assertStringContainsString('comfortable', $firstProduct['description']);
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

    public function testScalarTypeCasting(): void
    {
        $config = [
            'item_element' => 'item',
            'fields' => [
                'count'     => ['type' => 'integer', 'path' => 'count'],
                'price'     => ['type' => 'float',   'path' => 'price'],
                'available' => ['type' => 'boolean',  'path' => 'available'],
            ]
        ];

        $result = $this->parser->parseWithConfig(__DIR__ . '/files/input_typed_data.xml', $config);

        self::assertSame(42, $result[0]['count']);
        self::assertSame(9.99, $result[0]['price']);
        self::assertTrue($result[0]['available']);
    }

    public function testMalformedXmlThrows(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/XML parsing error/');

        $config = ['item_element' => 'item', 'fields' => []];
        $this->parser->parseWithConfig(__DIR__ . '/files/malformed.xml', $config);
    }

    public function testParseArrayConfig(): void
    {
        $rawConfig = [
            'parsers' => [
                'products' => [
                    'item_element' => 'product',
                    'fields' => ['id' => '@id', 'name' => 'name'],
                ]
            ]
        ];

        $parser = new SaxParser(new Config(
            (new ConfigParser())->parseArrayConfig($rawConfig)
        ));

        $result = $parser->parse(__DIR__ . '/files/input_products_data_1.xml', 'products');

        self::assertEquals('501', $result[0]['id']);
        self::assertEquals('Ektorp Sofa', $result[0]['name']);
    }

    public function testParseEachCallsCallbackPerItem(): void
    {
        $items = [];
        $this->parser->parseEach(
            __DIR__ . '/files/input_products_data_1.xml',
            'products',
            static function (array $item) use (&$items): void {
                $items[] = $item;
            }
        );

        self::assertCount(10, $items);
        self::assertEquals('501', $items[0]['id']);
    }

    public function testParseEachWithConfigCallsCallbackPerItem(): void
    {
        $config = ['item_element' => 'product', 'fields' => ['id' => '@id']];
        $count = 0;
        $this->parser->parseEachWithConfig(
            __DIR__ . '/files/input_products_data_1.xml',
            $config,
            static function (array $item) use (&$count): void {
                $count++;
            }
        );

        self::assertSame(10, $count);
    }

    public function testParseEachWithUnknownParserThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Parser config 'nonexistent' not found");

        $this->parser->parseEach(
            __DIR__ . '/files/input_products_data_1.xml',
            'nonexistent',
            static function (array $item): void {}
        );
    }
}
