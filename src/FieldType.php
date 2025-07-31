<?php

declare(strict_types=1);

namespace BoZielonka\PhpSaxParser;

enum FieldType: string
{
    case STRING = 'string';
    case ARRAY = 'array';
    case OBJECT = 'object';
    case ATTRIBUTE = 'attribute';
    case INTEGER = 'integer';
    case FLOAT = 'float';
    case BOOLEAN = 'boolean';
}
