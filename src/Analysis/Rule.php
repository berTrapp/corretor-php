<?php

declare(strict_types=1);

namespace Corretor\Analysis;

use PhpParser\Node;

/**
 * Contrato de uma regra de conformidade.
 *
 * Cada regra decide para quais arquivos se aplica e, a partir da AST,
 * devolve as violações encontradas. Regras não alteram arquivos.
 */
interface Rule
{
    /** Identificador estável, usado no relatório (ex.: controller.sem-regra-de-negocio). */
    public function nome(): string;

    /** Explicação curta da regra, em linguagem de time. */
    public function descricao(): string;

    /** Define se a regra se aplica ao arquivo (normalmente pelo caminho). */
    public function aplicaA(string $arquivo): bool;

    /**
     * @param list<Node> $stmts AST do arquivo
     * @return list<Violation>
     */
    public function verificar(string $arquivo, array $stmts): array;
}
