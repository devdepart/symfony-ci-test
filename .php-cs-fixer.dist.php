<?php

//$finder = (new PhpCsFixer\Finder())
//    ->in(__DIR__)
//    ->exclude('var')
//    ->notPath([
//        'config/bundles.php',
//        'config/reference.php',
//    ])
//;

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests')
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
    ])
    ->setFinder($finder)
;
