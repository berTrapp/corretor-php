<?php

declare(strict_types=1);

namespace Corretor\Stacks\Laravel\Rules;

use Corretor\Analysis\Caminho;
use Corretor\Stacks\Laravel\Rotas;
use Corretor\Analysis\Rule;
use Corretor\Analysis\Violation;
use PhpParser\Node\Stmt\Expression;
use PhpParser\NodeFinder;

/**
 * Toda rota deve ter nome, no formato recurso.acao (ex.: pedidos.index, pedidos.show).
 * Nomes permitem gerar URLs com route() e não quebrar quando a URI mudar.
 */
final class RotasNomeadas implements Rule
{
    private const PADRAO_NOME = '/^[a-z][a-z0-9_]*(\.[a-z][a-z0-9_]*)+$/';

    public function nome(): string
    {
        return 'rotas.nomeadas';
    }

    public function descricao(): string
    {
        return 'Rotas precisam de nome no formato recurso.acao, usado em route().';
    }

    public function aplicaA(string $arquivo): bool
    {
        return Caminho::contem($arquivo, 'routes') && str_ends_with($arquivo, '.php');
    }

    public function verificar(string $arquivo, array $stmts): array
    {
        $violacoes = [];

        /** @var list<Expression> $expressoes */
        $expressoes = (new NodeFinder())->findInstanceOf($stmts, Expression::class);

        foreach ($expressoes as $expressao) {
            $rota = Rotas::ler($expressao->expr);

            if ($rota === null) {
                continue;
            }

            if ($rota['nome'] === null) {
                $violacoes[] = new Violation(
                    $this->nome(),
                    $arquivo,
                    $rota['linha'],
                    "Rota {$rota['verbo']} '{$rota['uri']}' sem nome; use ->name('recurso.acao').",
                );
            } elseif (!preg_match(self::PADRAO_NOME, $rota['nome'])) {
                $violacoes[] = new Violation(
                    $this->nome(),
                    $arquivo,
                    $rota['linha'],
                    "Nome de rota '{$rota['nome']}' fora do padrão recurso.acao em minúsculas.",
                );
            }
        }

        return $violacoes;
    }
}
