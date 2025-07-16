<?php

use PhpCsFixer\Finder;
use PhpCsFixer\Config;

// Create a finder instance
$finder = Finder::create()
    ->in(__DIR__)
    ->exclude('vendor')
    ->exclude('storage')
    ->exclude('bootstrap/cache')
    ->name('*.php')
    ->notName('*.blade.php');

// Create and return the configuration instance
return (new Config())
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'binary_operator_spaces' => [
            'default' => 'align_single_space',
        ],
        'single_quote' => true,
        'no_unused_imports' => true,
        'ordered_imports' => true,
        'phpdoc_scalar' => true,
        'trim_array_spaces' => true,
        'cast_spaces' => true,
    ])
    ->setFinder($finder);
