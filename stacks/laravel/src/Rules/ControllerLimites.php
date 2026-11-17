<?php

declare(strict_types=1);

namespace Corretor\Stacks\Laravel\Rules;

use Corretor\Analysis\Caminho;
use Corretor\Analysis\Rule;
use Corretor\Analysis\Violation;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeFinder;

/**
 * Limites para controllers:
 * - no máximo 7 métodos públicos (as 7 ações de um resource); mais que isso pede divisão;
 * - consultas Eloquent diretas (Model::where, Model::find...) devem ir para um service.
 */
final class ControllerLimites implements Rule
{
    private const MAX_METODOS_PUBLICOS = 7;

    /** Métodos de consulta/escrita do Eloquent. */
    private const METODOS_ELOQUENT = [
        'where', 'query', 'find', 'findOrFail', 'first', 'firstOrFail', 'all',
        'create', 'update', 'delete', 'destroy', 'with', 'orderBy', 'paginate',
    ];

    /** Classes que não são Models, mesmo tendo métodos parecidos. */
    private const NAO_MODELS = [
        'DB', 'Route', 'Auth', 'Cache', 'Http', 'Storage', 'Mail', 'Log', 'Str', 'Arr',
        'Hash', 'Gate', 'Session', 'View', 'Redirect', 'URL', 'Validator', 'Config', 'Event',
        'Queue', 'Bus', 'Schema', 'Carbon', 'Collection', 'File', 'Response', 'Request',
    ];

    public function nome(): string
    {
        return 'controller.limites';
    }

    public function descricao(): string
    {
        return 'Controllers com até 7 métodos públicos e sem consultas Eloquent diretas.';
    }

    public function aplicaA(string $arquivo): bool
    {
        return Caminho::contem($arquivo, 'app/Http/Controllers');
    }

    public function verificar(string $arquivo, array $stmts): array
    {
        $violacoes = [];
        $finder = new NodeFinder();

        foreach ($finder->findInstanceOf($stmts, Class_::class) as $classe) {
            $publicos = array_filter(
                $classe->getMethods(),
                static fn (ClassMethod $m): bool => $m->isPublic() && !str_starts_with($m->name->toString(), '__'),
            );

            if (count($publicos) > self::MAX_METODOS_PUBLICOS) {
                $violacoes[] = new Violation(
                    'controller.limite-metodos',
                    $arquivo,
                    $classe->getStartLine(),
                    'Controller com ' . count($publicos) . ' métodos públicos; divida por recurso ou use um controller invocável.',
                    'aviso',
                );
            }
        }

        /** @var list<StaticCall> $chamadas */
        $chamadas = $finder->findInstanceOf($stmts, StaticCall::class);

        foreach ($chamadas as $chamada) {
            if (!$chamada->class instanceof Name || !$chamada->name instanceof Identifier) {
                continue;
            }

            $classe = $chamada->class->getLast();
            $metodo = $chamada->name->toString();

            if (!in_array($metodo, self::METODOS_ELOQUENT, true) || in_array($classe, self::NAO_MODELS, true)) {
                continue;
            }

            if (!preg_match('/^[A-Z][A-Za-z0-9]*$/', $classe)) {
                continue;
            }

            $violacoes[] = new Violation(
                'controller.consulta-eloquent-direta',
                $arquivo,
                $chamada->getStartLine(),
                "{$classe}::{$metodo}() em controller; mova a consulta para um service.",
                'aviso',
            );
        }

        return $violacoes;
    }
}
