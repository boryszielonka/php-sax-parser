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
        // Arrange
        $config = [
            'item_element' => 'product',
            'fields' => [
                'name' => 'name',
                'category' => 'category'
            ]
        ];

        $strategy = new ConfigurableParsingStrategy($config);
        $context = new ParsingContextStacks();

        // Act
        $context->pushElement('product', []);
        $strategy->processElement('product', '', [], $context);
        $context->pushElement('name', []);
        $strategy->processElement('product/name', '', [], $context);
        $strategy->processCharacterData('Test Product', $context);
        $context->popElement();
        $strategy->finishCurrentItem();

        // Assert
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
        // Arrange
        $config = [
            'item_element' => 'product',
            'fields' => [
                'id' => '@id',
                'name' => 'name'
            ]
        ];

        $strategy = new ConfigurableParsingStrategy($config);
        $context = new ParsingContextStacks();

        // Act
        $context->pushElement('product', ['id' => '123']);
        $strategy->processElement('product', '', ['id' => '123'], $context);

        $context->pushElement('name', []);
        $strategy->processCharacterData('Test', $context);
        $context->popElement();

        $strategy->finishCurrentItem();

        // Assert
        $result = $strategy->getCollectedData();

        $this->assertCount(1, $result);
        $this->assertEquals('123', $result[0]['id']);
        $this->assertEquals('Test', $result[0]['name']);
    }
}
