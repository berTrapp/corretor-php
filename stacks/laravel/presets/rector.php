<?php

declare(strict_types=1);

// Refatorações automáticas. Rode com: vendor/bin/rector --dry-run (primeiro) e depois sem --dry-run.
// Para os conjuntos específicos do Laravel, instale driftingly/rector-laravel e confira
// o namespace do LaravelSetList na versão instalada antes de adicioná-lo com ->withSets().

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/app',
        __DIR__ . '/routes',
        __DIR__ . '/config',
    ])
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        earlyReturn: true,
    );
