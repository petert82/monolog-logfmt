<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        '@PhpCsFixer' => true,
        'concat_space' => ['spacing' => 'none'],
        'global_namespace_import' => ['import_classes' => true, 'import_functions' => true],
        'phpdoc_align' => ['align' => 'left'],
        'phpdoc_annotation_without_dot' => false,
        'unary_operator_spaces' => ['only_dec_inc' => true],
        'yoda_style' => false,
    ])
    ->setFinder($finder)
;
