<?php

declare(strict_types=1);

namespace Corretor\Cli;

use Corretor\Core\Config;
use FilesystemIterator;

/** Copia as configurações da pasta presets/ da stack para o projeto. */
final class Instalar
{
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

            echo "\nPróximo passo: {$stack->dependenciasSugeridas()}\n\n";
        }

        return 0;
    }
}
