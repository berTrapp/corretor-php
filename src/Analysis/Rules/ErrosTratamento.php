<?php

declare(strict_types=1);

namespace Corretor\Analysis\Rules;

use Corretor\Analysis\Caminho;
use Corretor\Analysis\Rule;
use Corretor\Analysis\Violation;
use PhpParser\Node;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\Throw_ as ThrowExpr;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Nop;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Stmt\TryCatch;
use PhpParser\NodeFinder;

/**
 * Tratamento de erros (ERR-01 a ERR-04):
 * - exceção genérica lançada fora de app/Exceptions (ERR-01);
 * - try/catch em controller, sinal de tratamento espalhado (ERR-02);
 * - withExceptions() vazio em bootstrap/app.php (ERR-02);
 * - $e->getMessage() devolvido ao usuário em app/Http (ERR-03);
 * - catch sem instruções (ERR-04).
 */
final class ErrosTratamento implements Rule
{
    /** @var list<string> */
    private const GENERICAS = ['exception', 'runtimeexception', 'logicexception', 'errorexception'];

    public function nome(): string
    {
        return 'erros.tratamento';
    }

    public function descricao(): string
    {
        return 'Exceções de domínio, tratadas em um lugar só, sem expor mensagem interna nem engolir erro.';
    }

    public function aplicaA(string $arquivo): bool
    {
        return str_ends_with($arquivo, '.php');
    }

    public function verificar(string $arquivo, array $stmts): array
    {
        $finder = new NodeFinder();
        $violacoes = [];

        if (!Caminho::contem($arquivo, 'app/Exceptions')) {
            foreach ($finder->findInstanceOf($stmts, ThrowExpr::class) as $throw) {
                $expr = $throw->expr;
                if ($expr instanceof New_ && $expr->class instanceof Name) {
                    $classe = $expr->class->getLast();
                    if (in_array(strtolower($classe), self::GENERICAS, true)) {
                        $violacoes[] = new Violation(
                            'erros.excecao-generica',
                            $arquivo,
                            $throw->getStartLine(),
                            "throw new {$classe}: crie uma exceção de domínio em app/Exceptions.",
                            'aviso',
                        );
                    }
                }
            }
        }

        $ehController = Caminho::contem($arquivo, 'app/Http/Controllers');

        /** @var list<TryCatch> $blocos */
        $blocos = $finder->findInstanceOf($stmts, TryCatch::class);
        foreach ($blocos as $bloco) {
            if ($ehController) {
                $violacoes[] = new Violation(
                    'erros.try-catch-em-controller',
                    $arquivo,
                    $bloco->getStartLine(),
                    'try/catch em controller; trate a exceção no handler central (bootstrap/app.php).',
                    'aviso',
                );
            }

            foreach ($bloco->catches as $catch) {
                if (self::semInstrucoes($catch->stmts)) {
                    $violacoes[] = new Violation(
                        'erros.catch-vazio',
                        $arquivo,
                        $catch->getStartLine(),
                        'catch vazio engole a exceção; registre, relance ou trate.',
                    );
                }
            }
        }

        if (Caminho::contem($arquivo, 'app/Http')) {
            /** @var list<Return_> $retornos */
            $retornos = $finder->findInstanceOf($stmts, Return_::class);
            foreach ($retornos as $retorno) {
                if ($retorno->expr === null) {
                    continue;
                }
                $expoe = $finder->findFirst(
                    $retorno->expr,
                    static fn (Node $n): bool => $n instanceof MethodCall
                        && $n->name instanceof Node\Identifier
                        && $n->name->toString() === 'getMessage',
                );
                if ($expoe !== null) {
                    $violacoes[] = new Violation(
                        'erros.mensagem-exposta',
                        $arquivo,
                        $retorno->getStartLine(),
                        'getMessage() devolvido ao usuário pode expor detalhes internos; use mensagem própria.',
                    );
                }
            }
        }

        if (str_ends_with(str_replace('\\', '/', $arquivo), 'bootstrap/app.php')) {
            /** @var list<MethodCall> $chamadas */
            $chamadas = $finder->findInstanceOf($stmts, MethodCall::class);
            foreach ($chamadas as $chamada) {
                if (!$chamada->name instanceof Node\Identifier || $chamada->name->toString() !== 'withExceptions') {
                    continue;
                }
                $arg = $chamada->getArgs()[0]->value ?? null;
                if ($arg instanceof Closure && self::semInstrucoes($arg->stmts)) {
                    $violacoes[] = new Violation(
                        'erros.handler-central-vazio',
                        $arquivo,
                        $chamada->name->getStartLine(),
                        'withExceptions() vazio; registre aqui o tratamento das exceções de domínio.',
                        'aviso',
                    );
                }
            }
        }

        return $violacoes;
    }

    /** @param array<Node\Stmt> $stmts */
    private static function semInstrucoes(array $stmts): bool
    {
        foreach ($stmts as $stmt) {
            if (!$stmt instanceof Nop) {
                return false;
            }
        }

        return true;
    }
}
