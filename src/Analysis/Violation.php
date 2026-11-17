<?php

declare(strict_types=1);

namespace Corretor\Analysis;

/** Uma ocorrência de violação encontrada em um arquivo. */
final readonly class Violation
{
    public function __construct(
        public string $regra,
        public string $arquivo,
        public int $linha,
        public string $mensagem,
        public string $severidade = 'erro',
    ) {
    }
}
