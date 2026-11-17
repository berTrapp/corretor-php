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
        public string $stack = '',
    ) {
    }

    public function comStack(string $stack): self
    {
        return new self($this->regra, $this->arquivo, $this->linha, $this->mensagem, $this->severidade, $stack);
    }

    public function relativaA(string $raiz): self
    {
        $raiz = rtrim(str_replace('\\', '/', $raiz), '/') . '/';
        $arquivo = str_replace('\\', '/', $this->arquivo);
        if (str_starts_with($arquivo, $raiz)) {
            $arquivo = substr($arquivo, strlen($raiz));
        }

        return new self($this->regra, $arquivo, $this->linha, $this->mensagem, $this->severidade, $this->stack);
    }
}
