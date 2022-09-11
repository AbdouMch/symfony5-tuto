<?php
$excludedDirectories = [
    'vendor',
    'node_modules',
    'docker',
    'public',
    'var'
];
$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude($excludedDirectories)
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        'ordered_class_elements' => true,
//        '@PSR12' => true,
//        '@PhpCsFixer' => true,
//        '@Symfony' => true,
//        'declare_strict_types' => false,
//        'ordered_interfaces' => true,
//        'global_namespace_import' => true,
//        'list_syntax' => ['syntax' => 'short'],
    ])
    ->setFinder($finder)
;
