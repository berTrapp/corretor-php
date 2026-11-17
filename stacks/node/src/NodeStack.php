<?php

declare(strict_types=1);

namespace Corretor\Stacks\Node;

use Corretor\Adapters\AstGrep;
use Corretor\Core\Config;
use Corretor\Core\Stack;
use Corretor\Core\Stacks;

/** Node/TypeScript (NestJS, Express, React): regras em YAML executadas pelo ast-grep. */
final class NodeStack implements Stack
{
    public function nome(): string
    {
        return 'node';
    }

    public function detectar(string $raiz): bool
    {
        return is_file($raiz . '/package.json') && !is_file($raiz . '/artisan');
    }

    public function analisar(string $raiz, Config $config): array
    {
        return AstGrep::analisar(Stacks::pasta('node') . '/sgconfig.yml', $raiz);
    }

    public function pastaPresets(): string
    {
        return Stacks::pasta('node') . '/presets';
    }

    public function dependenciasSugeridas(): string
    {
        return 'npm i -D @ast-grep/cli eslint @eslint/js typescript-eslint prettier dependency-cruiser typescript';
    }
}
