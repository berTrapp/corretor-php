<?php

declare(strict_types=1);

namespace Corretor\Analysis\Rules;

use Corretor\Analysis\Caminho;
use Corretor\Analysis\Rotas;
use Corretor\Analysis\Rule;
use Corretor\Analysis\Violation;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Stmt\Expression;
use PhpParser\NodeFinder;

/**
 * Convenções de URI e de corpo das rotas:
 * - URI em kebab-case, sem verbos (substantivos), parâmetros entre chaves.
 * - Sem closures com mais de uma instrução: a lógica vai para um controller.
 */
final class RotasConvencoes implements Rule
{
    /** @var list<string> */
    private const VERBOS_PROIBIDOS = [
        'criar', 'editar', 'excluir', 'deletar', 'atualizar', 'buscar', 'listar', 'salvar',
        'create', 'store', 'update', 'delete', 'destroy', 'show', 'edit', 'list', 'get',
    ];

    public function nome(): string
    {
        return 'rotas.convencoes';
    }

    public function descricao(): string
    {
        return 'URIs em kebab-case, substantivos e sem verbos; closures sem lógica de negócio.';
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

            if ($rota['uri'] !== null) {
                $violacoes = array_merge($violacoes, $this->verificarUri($arquivo, $rota['uri'], $rota['linha']));
            }

            foreach ($rota['args'] as $argumento) {
                if ($this->ehClosureComLogica($argumento)) {
                    $violacoes[] = new Violation(
                        $this->nome(),
                        $arquivo,
                        $rota['linha'],
                        "Closure na rota '{$rota['uri']}' com mais de uma instrução; mova a lógica para um controller.",
                        'aviso',
                    );
                }
            }
        }

        return $violacoes;
    }

    /** @return list<Violation> */
    private function verificarUri(string $arquivo, string $uri, int $linha): array
    {
        $violacoes = [];
        $segmentos = array_filter(explode('/', trim($uri, '/')), static fn (string $s): bool => $s !== '');

        foreach ($segmentos as $segmento) {
            if (preg_match('/^\{[a-zA-Z_]+\??\}$/', $segmento)) {
                continue; // parâmetro de rota
            }

            if (!preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $segmento)) {
                $violacoes[] = new Violation($this->nome(), $arquivo, $linha, "Segmento '{$segmento}' da URI '{$uri}' não está em kebab-case.");
            } elseif (in_array($segmento, self::VERBOS_PROIBIDOS, true)) {
                $violacoes[] = new Violation($this->nome(), $arquivo, $linha, "URI '{$uri}' usa verbo ('{$segmento}'); use substantivo e o verbo HTTP.");
            }
        }

        return $violacoes;
    }

    private function ehClosureComLogica(Arg $argumento): bool
    {
        return $argumento->value instanceof Closure && count($argumento->value->stmts) > 1;
    }
}
