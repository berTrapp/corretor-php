<?php

declare(strict_types=1);

namespace Corretor\Cli;

use Corretor\Analysis\Analyzer;
use Corretor\Analysis\Rule;
use Corretor\Analysis\Rules\CamadasSemHttp;
use Corretor\Analysis\Rules\ControllerLimites;
use Corretor\Analysis\Rules\ControllerSemRegraDeNegocio;
use Corretor\Analysis\Rules\ErrosTratamento;
use Corretor\Analysis\Rules\GeralProibidos;
use Corretor\Analysis\Rules\NomesDeClassesEVariaveis;
use Corretor\Analysis\Rules\RotasConvencoes;
use Corretor\Analysis\Rules\RotasNomeadas;
use Corretor\Analysis\Rules\ValidacaoEmFormRequest;
use Corretor\Report\Relatorio;

final class Application
{
    /**
     * Ponto de entrada. Retorna o código de saída do processo:
     * 0 = sem violações (ou instalação feita), 1 = há violações, 2 = erro de uso.
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
        $raiz = getcwd() ?: '.';
        $formato = 'md';
        $saida = null;

        foreach ($args as $arg) {
            if (str_starts_with($arg, '--formato=')) {
                $formato = substr($arg, strlen('--formato='));
            } elseif (str_starts_with($arg, '--saida=')) {
                $saida = substr($arg, strlen('--saida='));
            } else {
                $raiz = $arg;
            }
        }

        if (!in_array($formato, ['md', 'json'], true)) {
            return self::erro("Formato inválido: {$formato} (use md ou json)\n");
        }

        if (!is_dir($raiz)) {
            return self::erro("Diretório não encontrado: {$raiz}\n");
        }

        $violacoes = (new Analyzer(self::regrasPadrao()))->analisar($raiz);

        $conteudo = $formato === 'json'
            ? Relatorio::json($violacoes)
            : Relatorio::markdown($violacoes, $raiz);

        if ($saida !== null) {
            file_put_contents($saida, $conteudo);
            fwrite(STDERR, "Relatório salvo em {$saida} (" . count($violacoes) . " ocorrência(s)).\n");
        } else {
            echo $conteudo;
        }

        return $violacoes === [] ? 0 : 1;
    }

    /** @return list<Rule> */
    public static function regrasPadrao(): array
    {
        return [
            // Estrutura e nomes
            new NomesDeClassesEVariaveis(),
            // Controllers
            new ControllerSemRegraDeNegocio(),
            new ValidacaoEmFormRequest(),
            new ControllerLimites(),
            // Rotas
            new RotasNomeadas(),
            new RotasConvencoes(),
            // Services e models
            new CamadasSemHttp(),
            // Erros
            new ErrosTratamento(),
            // Geral
            new GeralProibidos(),
        ];
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
        return <<<TEXTO
        corretor — verifica e padroniza projetos Laravel

        Uso:
          corretor analisar [caminho] [--formato=md|json] [--saida=arquivo]
          corretor instalar [caminho] [--forcar]

        analisar   gera o relatório de violações (não altera arquivos)
        instalar   copia as configurações (Pint, PHPStan, Deptrac, Rector, EditorConfig)
                   para o projeto; --forcar sobrescreve as que já existem

        Códigos de saída:
          0  sem violações
          1  há violações
          2  erro de uso

        TEXTO;
    }
}
