<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude('build');

$config = new PhpCsFixer\Config();
$config->setRules([
    '@PSR2' => true,
    'no_unused_imports' => true,
    'no_trailing_whitespace' => true,
    'single_blank_line_at_eof' => true,
    'encoding' => true,
    'full_opening_tag' => true,
    'no_closing_tag' => true,
    'concat_space' => ['spacing' => 'one'],

    'modifier_keywords' => [
        'elements' => [
            'method',
            'property',
        ],
    ],
]);
$config->setFinder($finder);
return $config;
