<?php

declare(strict_types=1);

namespace Corretor\Analysis\Rules;

use Corretor\Analysis\Caminho;
use Corretor\Analysis\Rule;
use Corretor\Analysis\Violation;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\NodeFinder;

/**
 * Validação deve ficar em Form Requests, não inline no controller.
 *
 * Exemplo de violação: $request->validate([...]) dentro de um método do controller.
 */
final class ValidacaoEmFormRequest implements Rule
{
    public function nome(): string
    {
        return 'controller.validacao-em-form-request';
    }

    public function descricao(): string
    {
        return 'Validação inline no controller; crie uma Form Request.';
    }

    public function aplicaA(string $arquivo): bool
    {
        return Caminho::contem($arquivo, 'app/Http/Controllers');
    }

    public function verificar(string $arquivo, array $stmts): array
    {
        $violacoes = [];

        /** @var list<MethodCall> $chamadas */
        $chamadas = (new NodeFinder())->findInstanceOf($stmts, MethodCall::class);

        foreach ($chamadas as $chamada) {
            if ($chamada->name instanceof Identifier && $chamada->name->toString() === 'validate') {
                $violacoes[] = new Violation(
                    $this->nome(),
                    $arquivo,
                    $chamada->getStartLine(),
                    'Validação feita inline; mova as regras para uma Form Request.',
                );
            }
        }

        return $violacoes;
    }
}
