<?php

declare(strict_types=1);

namespace Corretor\Cli;

/** Copia as configurações da pasta presets/ para um projeto Laravel. */
final class Instalar
{
    /** @var list<string> */
    private const ARQUIVOS = ['pint.json', 'phpstan.neon', 'deptrac.yaml', 'rector.php', '.editorconfig'];

    /** @param list<string> $args */
    public static function executar(array $args): int
    {
        $destino = getcwd() ?: '.';
        $forcar = false;

        foreach ($args as $arg) {
            if ($arg === '--forcar') {
                $forcar = true;
            } else {
                $destino = $arg;
            }
        }

        if (!is_dir($destino)) {
            fwrite(STDERR, "Diretório não encontrado: {$destino}\n");

            return 2;
        }

        $origem = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'presets';

        foreach (self::ARQUIVOS as $arquivo) {
            $de = $origem . DIRECTORY_SEPARATOR . $arquivo;
            $para = rtrim($destino, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $arquivo;

            if (file_exists($para) && !$forcar) {
                echo "Pulado (já existe): {$arquivo}\n";

                continue;
            }

            if (!copy($de, $para)) {
                fwrite(STDERR, "Falha ao copiar {$arquivo}\n");

                return 2;
            }

            echo "Instalado: {$arquivo}\n";
        }

        echo "\nPróximos passos: composer require --dev laravel/pint larastan/larastan phpstan/phpstan "
            . "qossmic/deptrac driftingly/rector-laravel rector/rector\n";

        return 0;
    }
}
