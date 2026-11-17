<?php

declare(strict_types=1);

namespace Corretor\Analysis;

use FilesystemIterator;
use Generator;
use PhpParser\Error;
use PhpParser\ParserFactory;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Percorre os arquivos PHP do projeto, gera a AST de cada um e aplica as regras.
 * Não modifica nenhum arquivo.
 */
final class Analyzer
{
    /** Pastas que nunca são analisadas (relativas à raiz do projeto). */
    private const IGNORADAS = ['vendor', 'node_modules', 'storage', 'bootstrap/cache'];

    /** @param list<Rule> $regras */
    public function __construct(private readonly array $regras)
    {
    }

    /** @return list<Violation> */
    public function analisar(string $raiz): array
    {
        $parser = (new ParserFactory())->createForHostVersion();
        $violacoes = [];

        foreach ($this->arquivosPhp($raiz) as $arquivo) {
            try {
                $stmts = $parser->parse((string) file_get_contents($arquivo)) ?? [];
            } catch (Error $e) {
                $violacoes[] = new Violation('parse', $arquivo, $e->getStartLine(), $e->getMessage(), 'aviso');

                continue;
            }

            foreach ($this->regras as $regra) {
                if ($regra->aplicaA($arquivo)) {
                    array_push($violacoes, ...$regra->verificar($arquivo, $stmts));
                }
            }
        }

        return $violacoes;
    }

    /** @return Generator<string> */
    private function arquivosPhp(string $raiz): Generator
    {
        $raiz = rtrim($raiz, DIRECTORY_SEPARATOR);
        // Pastas ignoradas são podadas antes de entrar nelas: não são lidas
        // (evita cópias em worktrees e pastas sem permissão, como storage/).
        $iterador = new RecursiveIteratorIterator(
            new RecursiveCallbackFilterIterator(
                new RecursiveDirectoryIterator($raiz, FilesystemIterator::SKIP_DOTS),
                function (SplFileInfo $info) use ($raiz): bool {
                    if (!$info->isDir()) {
                        return true;
                    }
                    $relativo = substr($info->getPathname(), strlen($raiz) + 1);

                    return !$this->estaIgnorado($relativo . DIRECTORY_SEPARATOR . 'x');
                },
            ),
        );

        /** @var SplFileInfo $info */
        foreach ($iterador as $info) {
            if (!$info->isFile() || $info->getExtension() !== 'php') {
                continue;
            }

            $caminho = $info->getPathname();
            $relativo = substr($caminho, strlen($raiz) + 1);

            if ($this->estaIgnorado($relativo)) {
                continue;
            }

            yield $caminho;
        }
    }

    private function estaIgnorado(string $relativo): bool
    {
        foreach (self::IGNORADAS as $pasta) {
            if (str_starts_with($relativo, $pasta . DIRECTORY_SEPARATOR)) {
                return true;
            }
        }

        // Pastas ocultas (.git, .claude, .idea...) e worktrees ficam de fora,
        // senão o mesmo código é analisado várias vezes.
        $pastas = explode(DIRECTORY_SEPARATOR, $relativo);
        array_pop($pastas);

        foreach ($pastas as $pasta) {
            if (str_starts_with($pasta, '.')) {
                return true;
            }
        }

        return false;
    }
}
