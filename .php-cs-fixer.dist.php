<?php

declare(strict_types=1);

$finder = (new PhpCsFixer\Finder())
    ->in([__DIR__ . '/src', __DIR__ . '/tests', __DIR__ . '/database', __DIR__ . '/tools', __DIR__ . '/public'])
    ->append([__DIR__ . '/bin/technest', __FILE__]);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setCacheFile(__DIR__ . '/var/php-cs-fixer.cache')
    ->setRules([
        '@PER-CS2.0' => true,
        '@PER-CS2.0:risky' => true,
        'declare_strict_types' => true,
        'no_unused_imports' => true,
        'ordered_imports' => ['imports_order' => ['class', 'function', 'const'], 'sort_algorithm' => 'alpha'],
        'single_quote' => true,
        'trailing_comma_in_multiline' => ['elements' => ['arrays', 'arguments', 'parameters', 'match']],
        'no_superfluous_phpdoc_tags' => ['allow_mixed' => true],
        'phpdoc_trim' => true,
        'strict_comparison' => true,
        'native_function_invocation' => false,
    ])
    ->setFinder($finder);
