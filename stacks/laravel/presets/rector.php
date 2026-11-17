<?php

declare(strict_types=1);

// Refatorações automáticas (Rector 2 + driftingly/rector-laravel 2).
// Rode primeiro com --dry-run e revise; depois sem --dry-run, em um commit separado.
// withComposerBased(laravel: true) aplica as regras do rector-laravel que valem
// para a versão do Laravel instalada no projeto.

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/app',
        __DIR__ . '/routes',
        __DIR__ . '/config',
    ])
    ->withComposerBased(laravel: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        earlyReturn: true,
    );
