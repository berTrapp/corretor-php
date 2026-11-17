<?php

declare(strict_types=1);

namespace Corretor\Adapters;

use Corretor\Analysis\Violation;
use RuntimeException;

/**
 * Roda o ast-grep (https://ast-grep.github.io) com um sgconfig.yml e converte a saída em violações.
 * As regras ficam em YAML, sem código: é o motor das stacks que não são PHP.
 *
 * O binário é procurado em AST_GREP_BIN, depois "ast-grep" no PATH, depois
 * node_modules/.bin/ast-grep do projeto analisado.
 */
final class AstGrep
{
    private const SEVERIDADES = ['error' => 'erro', 'warning' => 'aviso', 'info' => 'aviso', 'hint' => 'aviso'];

    /** @return list<Violation> */
    public static function analisar(string $sgconfig, string $raiz): array
    {
        $binario = self::binario($raiz);
        // Roda a partir da raiz com caminho ".", para que os globs das regras (files/ignores)
        // casem com caminhos relativos ao projeto, não com onde ele está no disco.
        $comando = [$binario, 'scan', '--config', $sgconfig, '--json=stream', '.'];

        $processo = proc_open($comando, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $raiz);
        if (!is_resource($processo)) {
            throw new RuntimeException('Não foi possível executar o ast-grep.');
        }

        $violacoes = [];
        while (($linha = fgets($pipes[1])) !== false) {
            $item = json_decode($linha, true);
            if (!is_array($item) || !isset($item['ruleId'])) {
                continue;
            }
            $violacoes[] = new Violation(
                (string) $item['ruleId'],
                $raiz . '/' . preg_replace('#^\./#', '', (string) $item['file']),
                (int) ($item['range']['start']['line'] ?? 0) + 1,
                trim((string) ($item['message'] ?? '')),
                self::SEVERIDADES[$item['severity'] ?? 'error'] ?? 'erro',
            );
        }
        $erro = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $codigo = proc_close($processo);

        // ast-grep sai com 1 quando acha regra de severidade error; acima disso é falha real.
        if ($codigo > 1 && $violacoes === []) {
            throw new RuntimeException("ast-grep falhou ({$codigo}): {$erro}");
        }

        return $violacoes;
    }

    private static function binario(string $raiz): string
    {
        $env = getenv('AST_GREP_BIN');
        if (is_string($env) && $env !== '') {
            return $env;
        }

        foreach (explode(PATH_SEPARATOR, (string) getenv('PATH')) as $pasta) {
            foreach (['ast-grep', 'ast-grep.exe'] as $nome) {
                if (is_executable($pasta . DIRECTORY_SEPARATOR . $nome)) {
                    return $pasta . DIRECTORY_SEPARATOR . $nome;
                }
            }
        }

        $local = rtrim($raiz, '/\\') . '/node_modules/.bin/ast-grep';
        if (is_executable($local)) {
            return $local;
        }

        throw new RuntimeException(
            "ast-grep não encontrado. Instale com: npm i -D @ast-grep/cli (ou defina AST_GREP_BIN).",
        );
    }
}
