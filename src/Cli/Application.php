<?php

declare(strict_types=1);

namespace Corretor\Cli;

use Corretor\Analysis\Violation;
use Corretor\Core\Config;
use Corretor\Core\Stack;
use Corretor\Core\Stacks;
use Corretor\Report\Relatorio;
use RuntimeException;

final class Application
{
    /**
     * Ponto de entrada. Retorna o código de saída do processo:
     * 0 = nenhuma violação de severidade erro, 1 = há erros, 2 = erro de uso.
     * Avisos não mudam o código de saída: são para revisão.
     *
     * @param list<string> $argv
     */
    public static function run(array $argv): int
    {
        $comando = $argv[1] ?? 'ajuda';

        return match ($comando) {
            'analisar' => self::analisar(array_slice($argv, 2)),
            'instalar' => Instalar::executar(array_slice($argv, 2)),
            'ajuda', '--help', '-h' => self::ajuda(),
            default => self::erro("Comando desconhecido: {$comando}\n\n" . self::textoAjuda()),
        };
    }

    /** @param list<string> $args */
    private static function analisar(array $args): int
    {
        $opcoes = Opcoes::ler($args);
        $raiz = rtrim($opcoes->raiz, '/\\');
        $formato = $opcoes->valor('formato') ?? 'md';
        $saida = $opcoes->valor('saida');

        if (!in_array($formato, ['md', 'json'], true)) {
            return self::erro("Formato inválido: {$formato} (use md ou json)\n");
        }
        if (!is_dir($raiz)) {
            return self::erro("Diretório não encontrado: {$raiz}\n");
        }

        $config = Config::carregar($raiz);
        $stacks = self::stacksDoProjeto($raiz, $opcoes->valor('stack'), $config);
        if (is_string($stacks)) {
            return self::erro($stacks);
        }

        $violacoes = $config->problemas;
        foreach ($stacks as $stack) {
            try {
                foreach ($stack->analisar($raiz, $config) as $violacao) {
                    $violacoes[] = $violacao->comStack($stack->nome());
                }
            } catch (RuntimeException $e) {
                return self::erro("[{$stack->nome()}] {$e->getMessage()}\n");
            }
        }

        $violacoes = array_values(array_filter(
            array_map(static fn (Violation $v): Violation => $v->relativaA($raiz), $violacoes),
            static fn (Violation $v): bool => !$config->desligada($v->regra),
        ));

        $nomes = array_map(static fn (Stack $s): string => $s->nome(), $stacks);
        $conteudo = $formato === 'json'
            ? Relatorio::json($violacoes, $nomes, $config->desligadas)
            : Relatorio::markdown($violacoes, $raiz, $nomes, $config->desligadas);

        if ($saida !== null) {
            file_put_contents($saida, $conteudo);
            fwrite(STDERR, "Relatório salvo em {$saida} (" . count($violacoes) . " ocorrência(s)).\n");
        } else {
            echo $conteudo;
        }

        foreach ($violacoes as $violacao) {
            if ($violacao->severidade === 'erro') {
                return 1;
            }
        }

        return 0;
    }

    /** @return list<Stack>|string lista de stacks ou mensagem de erro */
    public static function stacksDoProjeto(string $raiz, ?string $opcao, Config $config): array|string
    {
        $nomes = $opcao !== null ? explode(',', $opcao) : $config->stacks;

        if ($nomes === []) {
            $detectadas = Stacks::detectar($raiz);

            return $detectadas !== []
                ? $detectadas
                : "Nenhuma stack detectada em {$raiz}. Use --stack=" . self::nomesDisponiveis() . "\n";
        }

        $stacks = [];
        foreach ($nomes as $nome) {
            $stack = Stacks::porNome(trim($nome));
            if ($stack === null) {
                return "Stack desconhecida: {$nome} (disponíveis: " . self::nomesDisponiveis() . ")\n";
            }
            $stacks[] = $stack;
        }

        return $stacks;
    }

    private static function nomesDisponiveis(): string
    {
        return implode(', ', array_map(static fn (Stack $s): string => $s->nome(), Stacks::todas()));
    }

    private static function ajuda(): int
    {
        echo self::textoAjuda();

        return 0;
    }

    private static function erro(string $mensagem): int
    {
        fwrite(STDERR, $mensagem);

        return 2;
    }

    private static function textoAjuda(): string
    {
        $stacks = self::nomesDisponiveis();

        return <<<TEXTO
        corretor — verifica e padroniza projetos ({$stacks})

        Uso:
          corretor analisar [caminho] [--stack=nome[,nome]] [--formato=md|json] [--saida=arquivo]
          corretor instalar [caminho] [--stack=nome] [--forcar]

        analisar   gera o relatório de violações (não altera arquivos)
        instalar   copia as configurações da stack (formatador, análise estática,
                   camadas, EditorConfig) para o projeto; --forcar sobrescreve

        Sem --stack, a stack vem de .padrao.yaml ou é detectada (artisan, package.json).
        Regras podem ser desligadas em .padrao.yaml, sempre com motivo.

        Códigos de saída:
          0  nenhum erro (avisos não bloqueiam)
          1  há violações de severidade erro
          2  erro de uso

        TEXTO;
    }
}
