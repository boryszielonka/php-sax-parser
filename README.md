# PHP SAX Parser
[![CI](https://github.com/boryszielonka/php-sax-parser/actions/workflows/ci.yml/badge.svg)](https://github.com/boryszielonka/php-sax-parser/actions)
[![Coverage](https://raw.githubusercontent.com/boryszielonka/php-sax-parser/main/output/coverage.svg)](https://github.com/boryszielonka/php-sax-parser)

Simple and efficient XML streaming parser for PHP using SAX (Simple API for XML).
Reads XML files chunk by chunk (4 KB at a time), so large files never load into memory during parsing.
You configure what data to extract using YAML or PHP array config.

## Installation

```bash
composer require boryszielonka/php-sax-parser
```

## Requirements

- PHP 8.4 or higher
- ext-xml
- ext-libxml

## Quick start

```php
use BoZielonka\PhpSaxParser\Config;
use BoZielonka\PhpSaxParser\ConfigParser;
use BoZielonka\PhpSaxParser\SaxParser;

$parser = new SaxParser(
    new Config(
        (new ConfigParser())->parseYamlFile('configs/parsers.yaml')
    )
);

// Returns all items as an array (convenient for small-to-medium files)
$products = $parser->parse('products.xml', 'products');

// Streams items one-by-one via callback (recommended for large files)
$parser->parseEach('products.xml', 'products', function (array $product): void {
    // process or write to DB immediately — only one item in memory at a time
    echo $product['name'] . PHP_EOL;
});
```

## Usage

### YAML configuration

Create `configs/parsers.yaml`:

```yaml
parsers:
  products:
    root_element: "products"
    item_element: "product"
    fields:
      id: "@id"
      name: "name"
      category: "category"
      price:
        type: "attribute"
        path: "price"
        attribute: "currency"
      tags:
        type: "array"
        path: "tag"
      meta:
        type: "object"
```

Then:

```php
$parser = new SaxParser(
    new Config(
        (new ConfigParser())->parseYamlFile('configs/parsers.yaml')
    )
);

$items = $parser->parse('products.xml', 'products');
```

### PHP array configuration

```php
$config = [
    'item_element' => 'product',
    'fields' => [
        'id'       => '@id',
        'name'     => 'name',
        'quantity' => ['type' => 'integer', 'path' => 'quantity'],
        'price'    => ['type' => 'float',   'path' => 'price'],
        'active'   => ['type' => 'boolean', 'path' => 'active'],
        'tags'     => ['type' => 'array',   'path' => 'tag'],
    ]
];

$items = $parser->parseWithConfig('products.xml', $config);
```

### Callback API (large files)

Use `parseEach` / `parseEachWithConfig` when the full result set would not fit in memory.
The callback receives each item immediately as it is completed — `$collectedItems` never grows.

```php
// Named parser from YAML config
$parser->parseEach('large.xml', 'products', function (array $item): void {
    $db->insert($item);
});

// Inline PHP array config
$parser->parseEachWithConfig('large.xml', $config, function (array $item): void {
    $db->insert($item);
});
```

## Field types

| Type        | Description                                       | PHP return type  |
|-------------|---------------------------------------------------|------------------|
| `string`    | Text content of an element (default)              | `string`         |
| `integer`   | Text content cast to int                          | `int`            |
| `float`     | Text content cast to float                        | `float`          |
| `boolean`   | Text content cast to bool (`true`/`false`/`1`/`0`/`yes`/`no`) | `bool` |
| `attribute` | Attribute value from an element                   | `array`          |
| `array`     | Collects repeated elements into an array          | `array`          |
| `object`    | Collects child element values as a keyed array    | `array`          |

### Field definition syntax

```yaml
# Simple path (string shorthand)
name: "element_name"

# Root-element attribute
id: "@attribute_name"

# Typed field
quantity:
  type: integer
  path: element_name

# Attribute on a child element
price:
  type: attribute
  path: price          # path to the element
  attribute: currency  # attribute name to extract

# Array of repeated elements
tags:
  type: array
  path: tag

# Object (keyed by child element name)
dimensions:
  type: object
  path: dimensions
```

## API reference

```php
// Parse using a named parser from the loaded YAML config — returns array
$parser->parse(string $filePath, string $parserName = 'default'): array

// Parse with an inline PHP array config — returns array
$parser->parseWithConfig(string $filePath, array $config): array

// Stream items via callback using a named parser
$parser->parseEach(string $filePath, string $parserName, callable $callback): void

// Stream items via callback with an inline PHP array config
$parser->parseEachWithConfig(string $filePath, array $config, callable $callback): void
```

## Development

```bash
# Run tests
vendor/bin/phpunit tests

# Tests with coverage
./vendor/bin/phpunit tests --coverage-clover=clover.xml --coverage-filter=src --bootstrap=vendor/autoload.php

# Static analysis
vendor/bin/phpstan analyse src/ --level=5

# Code style (fix)
vendor/bin/php-cs-fixer fix src/

# Code style (check only)
./vendor/bin/phpcs --standard=PSR12 src/
```

## TODO

- [ ] Allow setting custom fread() buffer size instead of hardcoded 4 KB
- [ ] Nested field definitions for `object` type (explicit child field mapping in config)
- [ ] Add benchmark tests comparing against other XML parsers
- [ ] Add command to analyze XML structure and auto-generate YAML config

## Contributing

Feel free to submit issues and pull requests.
I'd appreciate feedback!

```
                               @        
                     @@@@@@@@@*@**@@@@@ 
                   @*:::*}@@@@*@**@@@@@ 
                   @:::@       @        
                  @:::@@@               
                  @:::@:@               
               @@@:::@@@@               
              @::@:::@::                
             @@@@@ @@@@@                
       @@@@@ @::@: @@:*@                
 @@@@:::::@  ::@::::@@@                 
   @::@@:::@@}:@@@@@                    
   @@::::::@@:@@  @@                    
    @:::@@@@+:@:@@@@                    
   @@::@   @:@@@@:@                     
   @):::@@@*:@  +@@                     
   @:::@@:@:@[@@@@                      
   @::@  @@@@::::@                      
   @::@@@@ @}:::@                       
   @@:::::::::::@                       
     @::::::::@@                        
       @@@@@@@                          
```
