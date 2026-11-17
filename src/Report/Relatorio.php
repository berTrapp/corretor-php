<?php

declare(strict_types=1);

namespace Corretor\Report;

use Corretor\Analysis\Violation;

/**
 * Gera o relatório em Markdown (leitura humana) ou JSON (skill e CI).
 * O formato JSON segue schemas/relatorio.schema.json; mudança incompatível sobe VERSAO.
 */
final class Relatorio
{
    public const VERSAO = 1;

    /**
     * @param list<Violation> $violacoes
     * @param list<string> $stacks
     * @param array<string, string> $desligadas
     */
    public static function markdown(array $violacoes, string $raiz, array $stacks = [], array $desligadas = []): string
    {
        $erros = count(array_filter($violacoes, static fn (Violation $v): bool => $v->severidade === 'erro'));

        $linhas = [
            '# Relatório de conformidade',
            '',
            "Raiz analisada: `{$raiz}`",
            '',
            'Stacks: ' . ($stacks === [] ? '-' : implode(', ', $stacks)),
            '',
            'Total de ocorrências: ' . count($violacoes) . " ({$erros} erro(s), " . (count($violacoes) - $erros) . ' aviso(s))',
            '',
        ];

        foreach ($desligadas as $regra => $motivo) {
            $linhas[] = "> Regra desligada: `{$regra}` ({$motivo})";
        }
        if ($desligadas !== []) {
            $linhas[] = '';
        }

        if ($violacoes === []) {
            $linhas[] = 'Nenhuma violação encontrada.';
        }

        foreach ($violacoes as $violacao) {
            $linhas[] = "- **[{$violacao->severidade}]** `{$violacao->regra}` em `{$violacao->arquivo}:{$violacao->linha}`: {$violacao->mensagem}";
        }

        return implode(PHP_EOL, $linhas) . PHP_EOL;
    }

    /**
     * @param list<Violation> $violacoes
     * @param list<string> $stacks
     * @param array<string, string> $desligadas
     */
    public static function json(array $violacoes, array $stacks = [], array $desligadas = []): string
    {
        $dados = [
            'versao' => self::VERSAO,
            'stacks' => $stacks,
            'desligadas' => (object) $desligadas,
            'violacoes' => array_map(static fn (Violation $v): array => (array) $v, $violacoes),
        ];

        return json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    }
}
