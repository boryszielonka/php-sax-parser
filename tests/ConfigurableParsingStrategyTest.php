<?php

declare(strict_types=1);

namespace BoZielonka\PhpSaxParser\Tests;

use BoZielonka\PhpSaxParser\ConfigurableParsingStrategy;
use BoZielonka\PhpSaxParser\ParsingContextStacks;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ConfigurableParsingStrategyTest extends TestCase
{
    public function testSimpleStringFieldParsing(): void
    {
        $config = [
            'item_element' => 'product',
            'fields' => [
                'name' => 'name',
                'category' => 'category'
            ]
        ];

        $strategy = new ConfigurableParsingStrategy($config);
        $context = new ParsingContextStacks();

        $context->pushElement('product', []);
        $strategy->processElement('product', '', []);
        $context->pushElement('name', []);
        $strategy->processCharacterData('Test Product', $context->getCurrentPath());
        $context->popElement();
        $strategy->finishCurrentItem();

        $result = $strategy->getCollectedData();

        $this->assertCount(1, $result);
        $this->assertEquals('Test Product', $result[0]['name']);
    }

    public function testInvalidConfigThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Configuration must specify item_element');

        new ConfigurableParsingStrategy(['fields' => []]);
    }

    public function testAttributeFieldParsing(): void
    {
        $config = [
            'item_element' => 'product',
            'fields' => [
                'id' => '@id',
                'name' => 'name'
            ]
        ];

        $strategy = new ConfigurableParsingStrategy($config);
        $context = new ParsingContextStacks();

        $context->pushElement('product', ['id' => '123']);
        $strategy->processElement('product', '', ['id' => '123']);

        $context->pushElement('name', []);
        $strategy->processCharacterData('Test', $context->getCurrentPath());
        $context->popElement();

        $strategy->finishCurrentItem();

        $result = $strategy->getCollectedData();

        $this->assertCount(1, $result);
        $this->assertEquals('123', $result[0]['id']);
        $this->assertEquals('Test', $result[0]['name']);
    }

    public function testResetClearsState(): void
    {
        $config = ['item_element' => 'product', 'fields' => ['name' => 'name']];
        $strategy = new ConfigurableParsingStrategy($config);
        $context = new ParsingContextStacks();

        $context->pushElement('product', []);
        $strategy->processElement('product', '', []);
        $context->pushElement('name', []);
        $strategy->processCharacterData('Test', $context->getCurrentPath());
        $context->popElement();
        $strategy->finishCurrentItem();

        $this->assertCount(1, $strategy->getCollectedData());

        $strategy->reset();

        $this->assertCount(0, $strategy->getCollectedData());
    }

    public function testSourceAliasForPath(): void
    {
        $config = [
            'item_element' => 'product',
            'fields' => [
                'name' => ['type' => 'string', 'source' => 'name'],
            ]
        ];
        $strategy = new ConfigurableParsingStrategy($config);
        $context = new ParsingContextStacks();

        $context->pushElement('product', []);
        $strategy->processElement('product', '', []);
        $context->pushElement('name', []);
        $strategy->processCharacterData('SourceAlias', $context->getCurrentPath());
        $context->popElement();
        $strategy->finishCurrentItem();

        $this->assertEquals('SourceAlias', $strategy->getCollectedData()[0]['name']);
    }

    public function testAttributeFieldOnChildElement(): void
    {
        $config = [
            'item_element' => 'product',
            'fields' => [
                'currency' => ['type' => 'attribute', 'path' => 'price', 'attribute' => 'currency'],
            ]
        ];
        $strategy = new ConfigurableParsingStrategy($config);
        $context = new ParsingContextStacks();

        $context->pushElement('product', []);
        $strategy->processElement('product', '', []);
        $context->pushElement('price', ['currency' => 'USD']);
        $strategy->processElement('product/price', '', ['currency' => 'USD']);
        $strategy->finishCurrentItem();

        $result = $strategy->getCollectedData();

        $this->assertCount(1, $result);
        $this->assertEquals('USD', $result[0]['currency']['currency']);
    }
}
