<?php

declare(strict_types=1);

namespace Corretor\Report;

use Corretor\Analysis\Violation;

/** Gera o relatório em Markdown (para leitura humana) ou JSON (para o skill e para CI). */
final class Relatorio
{
    /** @param list<Violation> $violacoes */
    public static function markdown(array $violacoes, string $raiz): string
    {
        $linhas = [
            '# Relatório de conformidade',
            '',
            "Raiz analisada: `{$raiz}`",
            '',
            'Total de ocorrências: ' . count($violacoes),
            '',
        ];

        if ($violacoes === []) {
            $linhas[] = 'Nenhuma violação encontrada.';
        }

        foreach ($violacoes as $violacao) {
            $linhas[] = "- **[{$violacao->severidade}]** `{$violacao->regra}` em `{$violacao->arquivo}:{$violacao->linha}`: {$violacao->mensagem}";
        }

        return implode(PHP_EOL, $linhas) . PHP_EOL;
    }

    /** @param list<Violation> $violacoes */
    public static function json(array $violacoes): string
    {
        $dados = array_map(static fn (Violation $v): array => (array) $v, $violacoes);

        return json_encode(
            $dados,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        ) . PHP_EOL;
    }
}
