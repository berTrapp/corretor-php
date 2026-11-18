<?php

declare(strict_types=1);

namespace Corretor\Cli;

use Corretor\Core\Config;
use FilesystemIterator;

/** Copia as configurações da pasta presets/ da stack para o projeto e atualiza o .gitignore. */
final class Instalar
{
    /** Relatório sugerido pela documentação e pelos skills, em qualquer stack. */
    private const RELATORIO = 'padrao-relatorio.json';

    /** @param list<string> $args */
    public static function executar(array $args): int
    {
        $opcoes = Opcoes::ler($args);
        $destino = rtrim($opcoes->raiz, '/\\');

        if (!is_dir($destino)) {
            fwrite(STDERR, "Diretório não encontrado: {$destino}\n");

            return 2;
        }

        $stacks = Application::stacksDoProjeto($destino, $opcoes->valor('stack'), Config::carregar($destino));
        if (is_string($stacks)) {
            fwrite(STDERR, $stacks);

            return 2;
        }

        foreach ($stacks as $stack) {
            echo "[{$stack->nome()}]\n";

            foreach (new FilesystemIterator($stack->pastaPresets()) as $info) {
                /** @var \SplFileInfo $info */
                $arquivo = $info->getFilename();
                $para = $destino . DIRECTORY_SEPARATOR . $arquivo;
                $faltando = self::pacotesFaltando($destino, $stack->presetsCondicionais()[$arquivo] ?? []);

                if ($faltando !== []) {
                    $pacotes = implode(' ', $faltando);
                    echo "Pulado (opcional, falta {$pacotes}): {$arquivo}. "
                        . "Para usar: composer require --dev {$pacotes} e rode instalar de novo.\n";

                    continue;
                }

                if (file_exists($para) && !$opcoes->flag('forcar')) {
                    echo "Pulado (já existe): {$arquivo}\n";

                    continue;
                }

                if (!copy($info->getPathname(), $para)) {
                    fwrite(STDERR, "Falha ao copiar {$arquivo}\n");

                    return 2;
                }

                echo "Instalado: {$arquivo}\n";
            }

            if (!self::atualizarGitignore($destino, [self::RELATORIO, ...$stack->arquivosGerados()])) {
                fwrite(STDERR, "Falha ao atualizar o .gitignore\n");

                return 2;
            }

            echo "\nPróximo passo: {$stack->dependenciasSugeridas()}\n\n";
        }

        return 0;
    }

    /**
     * @param list<string> $pacotes
     * @return list<string>
     */
    private static function pacotesFaltando(string $destino, array $pacotes): array
    {
        if ($pacotes === []) {
            return [];
        }

        $conteudo = @file_get_contents($destino . '/composer.json');
        $composer = is_string($conteudo) ? json_decode($conteudo, true) : null;
        $instalados = is_array($composer)
            ? [...(array) ($composer['require'] ?? []), ...(array) ($composer['require-dev'] ?? [])]
            : [];

        return array_values(array_filter($pacotes, static fn (string $pacote): bool => !isset($instalados[$pacote])));
    }

    /** @param list<string> $arquivos */
    private static function atualizarGitignore(string $destino, array $arquivos): bool
    {
        $caminho = $destino . '/.gitignore';
        $atual = is_file($caminho) ? (string) file_get_contents($caminho) : '';
        $linhas = array_map(static fn (string $linha): string => ltrim(trim($linha), '/'), explode("\n", $atual));
        $novos = array_values(array_diff($arquivos, $linhas));

        if ($novos === []) {
            return true;
        }

        $quebra = str_contains($atual, "\r\n") ? "\r\n" : "\n";
        $prefixo = $atual === '' || str_ends_with($atual, "\n") ? '' : $quebra;
        $bloco = implode($quebra, array_map(static fn (string $arquivo): string => '/' . $arquivo, $novos)) . $quebra;

        if (file_put_contents($caminho, $prefixo . $bloco, FILE_APPEND) === false) {
            return false;
        }

        echo 'Adicionado ao .gitignore: ' . implode(', ', $novos) . "\n";

        return true;
    }
}
