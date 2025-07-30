<?php

$finder = new PhpCsFixer\Finder()
    ->in(__DIR__)
    ->exclude('var');

return new PhpCsFixer\Config()
    ->setRules([
        '@PSR12' => true,
        'no_unused_imports' => true,
        'method_chaining_indentation' => true,
        'no_superfluous_phpdoc_tags' => true,
        'ordered_imports' => [
            'imports_order' => ['class', 'function', 'const'],
            'sort_algorithm' => 'alpha',
        ],
        'blank_line_before_statement' => [
            'statements' => [
                'return',
                'throw',
                'try',
            ],
        ],
        'binary_operator_spaces' => [
            'default' => 'single_space',
        ],
    ])
    ->setFinder($finder);
