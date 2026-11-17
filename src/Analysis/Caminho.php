<?php

declare(strict_types=1);

namespace Corretor\Analysis;

/** Testa se um arquivo está em determinada pasta, independente do separador do sistema operacional. */
final class Caminho
{
    public static function contem(string $arquivo, string $pasta): bool
    {
        $arquivo = str_replace('\\', '/', $arquivo);

        return str_contains($arquivo, '/' . trim($pasta, '/') . '/');
    }
}
