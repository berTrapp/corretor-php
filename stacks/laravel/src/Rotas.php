<?php

declare(strict_types=1);

namespace Corretor\Stacks\Laravel;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;

/**
 * Lê uma declaração de rota (Route::get(...)->name(...)) e devolve suas partes.
 * Rotas de resource (Route::resource, apiResource) não são lidas: o Laravel já as nomeia.
 */
final class Rotas
{
    /** @var list<string> */
    public const VERBOS = ['get', 'post', 'put', 'patch', 'delete', 'options', 'any', 'match'];

    /**
     * @return array{verbo: string, uri: ?string, nome: ?string, args: list<Arg>, linha: int}|null
     */
    public static function ler(Expr $expressao): ?array
    {
        $nome = null;
        $atual = $expressao;

        while ($atual instanceof MethodCall) {
            if ($atual->name instanceof Identifier && $atual->name->toString() === 'name') {
                $primeiro = $atual->args[0]->value ?? null;
                if ($nome === null && $primeiro instanceof String_) {
                    $nome = $primeiro->value;
                }
            }
            $atual = $atual->var;
        }

        if (
            !$atual instanceof StaticCall
            || !$atual->class instanceof Name
            || $atual->class->getLast() !== 'Route'
            || !$atual->name instanceof Identifier
            || !in_array($atual->name->toString(), self::VERBOS, true)
        ) {
            return null;
        }

        $uri = $atual->args[0]->value ?? null;

        return [
            'verbo' => $atual->name->toString(),
            'uri' => $uri instanceof String_ ? $uri->value : null,
            'nome' => $nome,
            'args' => $atual->args,
            'linha' => $atual->getStartLine(),
        ];
    }
}
