<?php

declare(strict_types=1);

namespace Corretor\Cli;

/** Lê argumentos no formato [caminho] --chave=valor --flag. */
final class Opcoes
{
    /** @param array<string, string|true> $valores */
    private function __construct(public readonly string $raiz, private readonly array $valores)
    {
    }

    /** @param list<string> $args */
    public static function ler(array $args): self
    {
        $raiz = getcwd() ?: '.';
        $valores = [];

        foreach ($args as $arg) {
            if (str_starts_with($arg, '--')) {
                [$chave, $valor] = array_pad(explode('=', substr($arg, 2), 2), 2, true);
                $valores[$chave] = $valor;
            } else {
                $raiz = $arg;
            }
        }

        return new self($raiz, $valores);
    }

    public function valor(string $chave): ?string
    {
        $valor = $this->valores[$chave] ?? null;

        return is_string($valor) ? $valor : null;
    }

    public function flag(string $chave): bool
    {
        return isset($this->valores[$chave]);
    }
}
