<?php

declare(strict_types=1);

namespace Corretor\Stacks\Laravel\Rules;

use Corretor\Analysis\Caminho;
use Corretor\Analysis\Rule;
use Corretor\Analysis\Violation;
use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PhpParser\NodeFinder;

/**
 * Controllers não devem acessar o banco, HTTP externo ou e-mail diretamente.
 * Essas chamadas devem ficar em services.
 *
 * Exemplo de violação: DB::table('pedidos')->... dentro de um controller.
 * Cache e Storage ficaram de fora de propósito: ler cache ou arquivo num controller costuma ser aceitável.
 */
final class ControllerSemRegraDeNegocio implements Rule
{
    /** @var list<string> */
    private const FACADES_PROIBIDAS = ['DB', 'Http', 'Mail', 'Schema'];

    public function nome(): string
    {
        return 'controller.sem-regra-de-negocio';
    }

    public function descricao(): string
    {
        return 'Controllers não devem usar DB, Http, Mail ou Schema diretamente; mova para um service.';
    }

    public function aplicaA(string $arquivo): bool
    {
        return Caminho::contem($arquivo, 'app/Http/Controllers');
    }

    public function verificar(string $arquivo, array $stmts): array
    {
        $violacoes = [];

        /** @var list<StaticCall> $chamadas */
        $chamadas = (new NodeFinder())->findInstanceOf($stmts, StaticCall::class);

        foreach ($chamadas as $chamada) {
            if (!$chamada->class instanceof Name) {
                continue;
            }

            $facade = $chamada->class->getLast();

            if (in_array($facade, self::FACADES_PROIBIDAS, true)) {
                $violacoes[] = new Violation(
                    $this->nome(),
                    $arquivo,
                    $chamada->getStartLine(),
                    "Uso de {$facade}:: em controller; mova essa lógica para um service.",
                );
            }
        }

        return $violacoes;
    }
}
