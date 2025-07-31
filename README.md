# PHP SAX Parser

Simple and efficient XML streaming parser for PHP using SAX (Simple API for XML) approach. 
This library allows you to parse large XML files without loading entire document into memory.
This parser reads XML files chunk by chunk (streaming) so you can process huge XML files without memory issues.
You configure what data you want to extract using simple YAML configs or direct PHP arrays.

## Installation

```bash
composer require boryszielonka/php-sax-parser
```

## Requirements

- PHP 8.4 or higher
- ext-xml extension
- ext-libxml extension

## Usage

### With YAML Configuration File

Create config file `configs/products.yaml`:

```yaml
products:
  item_element: product
  fields:
    id: "@id"
    name: "name" 
    price: "price"
    description: "description"
    categories:
      type: array
      path: "category"
      fields:
        name: "name"
        code: "@code"
    attributes:
      type: object
      fields:
        weight: "weight"
        color: "@color"
```

Then:

```php
$parser = new SaxParser();
$parser->loadConfigFromFile('configs/products.yaml');
$result = $parser->parse('products.xml', 'products');
```

## Configuration Options

### Field Types

- `string` (default) - extracts text content
- `attribute` - extracts attribute value using `@attribute_name`
- `array` - collects multiple elements into array
- `object` - creates nested object structure

### Field Configuration Examples

```yaml
# Simple text content
name: "product_name"

# Attribute value  
id: "@product_id"

# Nested object
category:
  type: object
  fields:
    name: "name"
    id: "@cat_id"

# Array of elements
tags:
  type: array
  path: "tag"
  
# Complex array with objects
variants:
  type: array
  path: "variant"
  fields:
    size: "@size"
    price: "price"
    stock: "stock_count"
```

## Example XML Structure

```xml
<?xml version="1.0"?>
<products>
  <product id="123">
    <name>Cool T-Shirt</name>
    <price currency="USD">29.99</price>
    <category code="CLOTH">
      <name>Clothing</name>
    </category>
    <variant size="M">
      <price>29.99</price>
      <stock_count>10</stock_count>
    </variant>
    <variant size="L">
      <price>31.99</price>
      <stock_count>5</stock_count>
    </variant>
  </product>
</products>
```

## Development

### Testing

```bash
vendor/bin/phpunit tests
```

### Code Quality

```bash
vendor/bin/php-cs-fixer fix /src
vendor/bin/phpstan analyse src/ --level=5
```

## TODO

- [ ] **Streaming output**: Option to write results directly to file/database instead of memory
- [ ] Allow setting custom fread() buffer size instead of hardcoded 4KB
- [ ] Add benchmark tests comparing against other XML parsers
- [ ] Add command to analyze XML structure and generate YAML config automatically
- [ ] Built-in data validation during parsing (required fields, data types, etc.)
- [ ] Allow custom processing functions for specific elements
- [ ] Callback interface for tracking parsing progress on large files
- and more...

## Contributing

Feel free to submit issues and pull requests. 
I'd appreciate feedback!

                                      ▒░▒░░░░           ▒███▒ ▓█    █ ▒███▒                       
                                   ░░░░░▒░▒░▒░▒▓        ▓█  █ ██    █ ▓█  █                       
                                 ░░░▒░░░░   ▓▓▓▓▓▒      ████  ███████ ████                        
                                ░░░▒░░░░                █▒    ██    █ █░                          
                                ░░▒░░░░░                                                          
                               ░▒░░░░░▒                                                           
                             ░░░▒░░░░░                                                            
                            ░░░▒░░░░░                                                             
                            ░░▒░░░▒░░                      ▓█▒      █   █    ██                   
                  ▒▒         ░░▒░░░░                     █░        ██    ██ █                     
           ▒░▒▓▓▓▓▓▓▒░    ░░▒░▒░░░░                      ░████▒  ▒█ ██    ██                      
        ▒▒▒▒▓▓▓▓▓▓▓▒░   ░░░░▒░░░░░                      █    ▒█ ▒█░  █  ▓█  ██                    
      ░▒▒▒▒▒▓▓▓▓▓░░░   ░░▒▒░░░░░░░                       ▓███   ▓       ░    ░                    
     ▒░▒▒▒▓▓▒░░░░░░▒▒    ▒░░░░▒░░▒                                                                
         ░░░░░▒░░░░     ▒░░░▒▒░░░░                                                                
           ░░░▒░░░░░   ░░░░░▒▒░░░▒                                                                
           ▒░░░░░░░░  ▒░░░░░░░▒░                         ██▓      ░   ███       ░   ▒█████ ███░   
            ▒░░░░░░░░░░░░░░░░▒                          ▒█  █    ██   █   █░  █     ░█     █   █▒ 
            ░░░░░░░░░░░░░░░░░                           █████   █ ██  █  ██   █████ ▒█████ █  ██  
              ▒░░░░▒░░░░░░░░                            █▒     █████  █ ███  ▓    █▒▓█     █ ▓██  
               ▒░░░░░░░░░░░                             █░    █    ░█ █    █ ▓████   █████ █    █ 
                   ▒░░░░
