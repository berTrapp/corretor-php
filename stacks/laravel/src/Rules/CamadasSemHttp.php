<?php

declare(strict_types=1);

namespace Corretor\Stacks\Laravel\Rules;

use Corretor\Analysis\Caminho;
use Corretor\Analysis\Rule;
use Corretor\Analysis\Violation;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\NodeFinder;

/**
 * Services e Models não conhecem HTTP: nada de request(), response(), redirect() ou session().
 * Assim a regra de negócio roda em jobs, comandos e testes sem uma requisição.
 */
final class CamadasSemHttp implements Rule
{
    /** @var list<string> */
    private const HELPERS_HTTP = ['request', 'response', 'redirect', 'session', 'back'];

    /** @var list<string> */
    private const PASTAS = ['app/Services', 'app/Models'];

    public function nome(): string
    {
        return 'camadas.sem-http';
    }

    public function descricao(): string
    {
        return 'Services e Models não usam helpers de HTTP (request, response, redirect, session).';
    }

    public function aplicaA(string $arquivo): bool
    {
        foreach (self::PASTAS as $pasta) {
            if (Caminho::contem($arquivo, $pasta)) {
                return true;
            }
        }

        return false;
    }

    public function verificar(string $arquivo, array $stmts): array
    {
        $violacoes = [];

        /** @var list<FuncCall> $chamadas */
        $chamadas = (new NodeFinder())->findInstanceOf($stmts, FuncCall::class);

        foreach ($chamadas as $chamada) {
            if (!$chamada->name instanceof Name) {
                continue;
            }

            $helper = $chamada->name->toString();

            if (in_array($helper, self::HELPERS_HTTP, true)) {
                $violacoes[] = new Violation(
                    $this->nome(),
                    $arquivo,
                    $chamada->getStartLine(),
                    "Helper {$helper}() em service/model; receba os dados como parâmetro e deixe o HTTP no controller.",
                );
            }
        }

        return $violacoes;
    }
}
