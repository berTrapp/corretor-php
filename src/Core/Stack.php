<?php

declare(strict_types=1);

namespace Corretor\Core;

use Corretor\Analysis\Violation;

/**
 * Uma stack (Laravel, Node/TypeScript...) com seu padrão, regras e presets.
 * O núcleo não conhece nenhuma stack em particular; só este contrato.
 */
interface Stack
{
    /** Identificador usado em --stack=, no .padrao.yaml e no campo "stack" do relatório. */
    public function nome(): string;

    /** Diz se o projeto na raiz usa esta stack. */
    public function detectar(string $raiz): bool;

    /** @return list<Violation> */
    public function analisar(string $raiz, Config $config): array;

    /** Pasta com os arquivos copiados por "corretor instalar". */
    public function pastaPresets(): string;

    /** Comando sugerido para instalar as ferramentas oficiais da stack. */
    public function dependenciasSugeridas(): string;
}
