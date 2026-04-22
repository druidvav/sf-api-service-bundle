<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/../src')
    ->exclude(['Resources'])
    ->name('*.php');

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setFinder($finder)
    ->setRules([
        '@PSR12' => true,
        '@Symfony' => true,
        '@Symfony:risky' => true,

        // Явно НЕ навязываем вещи, которые вы не хотите менять:
        'declare_strict_types' => false,
        'global_namespace_import' => false,
        'phpdoc_summary' => false,
        'phpdoc_annotation_without_dot' => false,
        'native_function_invocation' => false,
        'native_constant_invocation' => false,
        'void_return' => false,
        'array_syntax' => false,

        // Чтобы фиксер не вводил PHP8-синтаксис при таргете php>=7.4:
        'pow_to_exponentiation' => false,
    ]);

