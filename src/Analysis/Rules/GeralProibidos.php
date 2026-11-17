<?php

declare(strict_types=1);

namespace Corretor\Analysis\Rules;

use Corretor\Analysis\Caminho;
use Corretor\Analysis\Rule;
use Corretor\Analysis\Violation;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\NodeFinder;

/**
 * Coisas que não devem ir para o código final:
 * - depuração esquecida (dd, dump, var_dump, print_r, ray);
 * - env() fora de config/, porque o cache de configuração (config:cache) quebra esse uso.
 */
final class GeralProibidos implements Rule
{
    /** @var list<string> */
    private const DEPURACAO = ['dd', 'dump', 'var_dump', 'print_r', 'ray'];

    public function nome(): string
    {
        return 'geral.proibidos';
    }

    public function descricao(): string
    {
        return 'Sem depuração esquecida; env() só dentro de config/.';
    }

    public function aplicaA(string $arquivo): bool
    {
        return str_ends_with($arquivo, '.php');
    }

    public function verificar(string $arquivo, array $stmts): array
    {
        $violacoes = [];
        $dentroDeConfig = Caminho::contem($arquivo, 'config');

        /** @var list<FuncCall> $chamadas */
        $chamadas = (new NodeFinder())->findInstanceOf($stmts, FuncCall::class);

        foreach ($chamadas as $chamada) {
            if (!$chamada->name instanceof Name) {
                continue;
            }

            $funcao = $chamada->name->toString();

            if (in_array($funcao, self::DEPURACAO, true)) {
                $violacoes[] = new Violation(
                    'geral.depuracao-esquecida',
                    $arquivo,
                    $chamada->getStartLine(),
                    "Chamada {$funcao}() deixada no código; remova antes de subir.",
                );
            }

            if ($funcao === 'env' && !$dentroDeConfig) {
                $violacoes[] = new Violation(
                    'geral.env-fora-de-config',
                    $arquivo,
                    $chamada->getStartLine(),
                    'env() fora de config/; use config() para que o cache de configuração funcione.',
                );
            }
        }

        return $violacoes;
    }
}
