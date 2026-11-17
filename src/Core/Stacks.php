<?php

declare(strict_types=1);

namespace Corretor\Core;

use Corretor\Stacks\Laravel\LaravelStack;
use Corretor\Stacks\Node\NodeStack;

/** Registro das stacks disponíveis. Para adicionar uma, inclua-a em todas(). */
final class Stacks
{
    /** @return list<Stack> */
    public static function todas(): array
    {
        return [new LaravelStack(), new NodeStack()];
    }

    public static function porNome(string $nome): ?Stack
    {
        foreach (self::todas() as $stack) {
            if ($stack->nome() === $nome) {
                return $stack;
            }
        }

        return null;
    }

    /** @return list<Stack> */
    public static function detectar(string $raiz): array
    {
        return array_values(array_filter(self::todas(), static fn (Stack $s): bool => $s->detectar($raiz)));
    }

    /** Raiz do diretório stacks/ do pacote. */
    public static function pasta(string $stack): string
    {
        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'stacks' . DIRECTORY_SEPARATOR . $stack;
    }
}
