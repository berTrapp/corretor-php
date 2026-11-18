<?php

declare(strict_types=1);

namespace Corretor\Core;

use Corretor\Analysis\Violation;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Configuração do projeto, lida de .padrao.yaml na raiz (opcional).
 *
 *   stack: laravel                 # opcional; sem ela, as stacks são detectadas
 *   desligar:
 *     - regra: nomes.variavel-abreviada
 *       motivo: "Código legado; revisar no módulo de vendas."
 *   nomes-curtos-permitidos: [cd, nf]
 *   ignorar:                       # pastas ou globs, relativos à raiz
 *     - overrides/
 *     - "app/Legacy/**"
 *
 * Regra desligada sem motivo não é desligada e vira violação config.excecao-sem-motivo.
 */
final class Config
{
    public const ARQUIVO = '.padrao.yaml';

    /**
     * @param list<string> $stacks
     * @param array<string, string> $desligadas regra => motivo
     * @param list<string> $nomesCurtos
     * @param list<Violation> $problemas
     * @param list<string> $ignorar
     */
    public function __construct(
        public readonly array $stacks = [],
        public readonly array $desligadas = [],
        public readonly array $nomesCurtos = [],
        public readonly array $problemas = [],
        public readonly array $ignorar = [],
    ) {
    }

    public static function carregar(string $raiz): self
    {
        $arquivo = rtrim($raiz, '/\\') . DIRECTORY_SEPARATOR . self::ARQUIVO;
        if (!is_file($arquivo)) {
            return new self();
        }

        try {
            $dados = Yaml::parseFile($arquivo);
        } catch (ParseException $e) {
            return new self(problemas: [
                new Violation('config.invalida', self::ARQUIVO, (int) $e->getParsedLine(), $e->getMessage(), 'erro', 'core'),
            ]);
        }

        if (!is_array($dados)) {
            return new self();
        }

        $stacks = array_values(array_map('strval', (array) ($dados['stack'] ?? [])));
        $desligadas = [];
        $problemas = [];

        foreach ((array) ($dados['desligar'] ?? []) as $item) {
            $regra = is_array($item) ? (string) ($item['regra'] ?? '') : (string) $item;
            $motivo = is_array($item) ? trim((string) ($item['motivo'] ?? '')) : '';

            if ($regra === '') {
                continue;
            }
            if ($motivo === '') {
                $problemas[] = new Violation(
                    'config.excecao-sem-motivo',
                    self::ARQUIVO,
                    1,
                    "A regra {$regra} só pode ser desligada com um motivo escrito.",
                    'erro',
                    'core',
                );

                continue;
            }
            $desligadas[$regra] = $motivo;
        }

        $nomesCurtos = array_values(array_map('strval', (array) ($dados['nomes-curtos-permitidos'] ?? [])));

        $ignorar = array_values(array_map(
            static fn ($p): string => preg_replace('#^\./#', '', str_replace('\\', '/', (string) $p)),
            (array) ($dados['ignorar'] ?? []),
        ));

        return new self($stacks, $desligadas, $nomesCurtos, $problemas, $ignorar);
    }

    /** Caminho relativo à raiz, com /. "pasta/" ignora tudo abaixo; o resto é glob (fnmatch). */
    public function ignorado(string $arquivo): bool
    {
        foreach ($this->ignorar as $padrao) {
            if (str_ends_with($padrao, '/') ? str_starts_with($arquivo, $padrao) : fnmatch($padrao, $arquivo)) {
                return true;
            }
        }

        return false;
    }

    public function desligada(string $regra): bool
    {
        return isset($this->desligadas[$regra]);
    }
}
