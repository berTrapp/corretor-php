<?php

declare(strict_types=1);

namespace Corretor\Stacks\Laravel\Rules;

use Corretor\Analysis\Caminho;
use Corretor\Analysis\Rule;
use Corretor\Analysis\Violation;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeFinder;

/**
 * Nomes de classes, métodos e variáveis.
 *
 * - Classes devem terminar com o sufixo do seu diretório (ex.: Http/Controllers -> ...Controller).
 * - Métodos e variáveis em camelCase, sem abreviações (ver VARIAVEIS_CURTAS_OK).
 * - Cada variável é reportada uma vez por arquivo.
 */
final class NomesDeClassesEVariaveis implements Rule
{
    /** Diretório => sufixo obrigatório do nome da classe. */
    private const SUFIXOS = [
        'app/Http/Controllers' => 'Controller',
        'app/Http/Requests' => 'Request',
        'app/Http/Resources' => 'Resource',
        'app/Services' => 'Service',
        'app/Policies' => 'Policy',
        'app/Observers' => 'Observer',
        'app/Listeners' => 'Listener',
        'app/Jobs' => 'Job',
    ];

    /**
     * Variáveis curtas aceitas: índices, exceção e siglas que já são a palavra completa.
     * O projeto pode acrescentar outras em .padrao.yaml (nomes-curtos-permitidos).
     */
    public const VARIAVEIS_CURTAS_OK = ['i', 'j', 'k', 'e', 'id', 'uf', 'ip', 'db', 'os'];

    /** @var list<string> */
    private readonly array $curtasPermitidas;

    /** @param list<string> $curtasExtras */
    public function __construct(array $curtasExtras = [])
    {
        $this->curtasPermitidas = [...self::VARIAVEIS_CURTAS_OK, ...$curtasExtras];
    }

    public function nome(): string
    {
        return 'nomes.classes-variaveis-metodos';
    }

    public function descricao(): string
    {
        return 'Classes com sufixo do diretório; métodos e variáveis em camelCase e descritivos.';
    }

    public function aplicaA(string $arquivo): bool
    {
        return str_ends_with($arquivo, '.php');
    }

    public function verificar(string $arquivo, array $stmts): array
    {
        $violacoes = [];
        $finder = new NodeFinder();

        foreach ($finder->findInstanceOf($stmts, Class_::class) as $classe) {
            if ($classe->name === null) {
                continue;
            }

            $sufixo = $this->sufixoDoDiretorio($arquivo);
            $nomeClasse = $classe->name->toString();

            if ($sufixo !== null && !str_ends_with($nomeClasse, $sufixo)) {
                $violacoes[] = new Violation(
                    'nomes.sufixo-da-classe',
                    $arquivo,
                    $classe->getStartLine(),
                    "Classe {$nomeClasse} deveria terminar com {$sufixo}.",
                    'aviso',
                );
            }
        }

        foreach ($finder->findInstanceOf($stmts, ClassMethod::class) as $metodo) {
            $nome = $metodo->name->toString();

            if (str_starts_with($nome, '__')) {
                continue; // métodos mágicos (__construct, __invoke...)
            }

            if (!preg_match('/^[a-z][a-zA-Z0-9]*$/', $nome)) {
                $violacoes[] = new Violation(
                    'nomes.metodo-camel-case',
                    $arquivo,
                    $metodo->getStartLine(),
                    "Método {$nome} deveria estar em camelCase.",
                );
            }
        }

        // Cada variável é reportada uma vez por arquivo (na primeira ocorrência),
        // para o relatório contar nomes a corrigir, não usos.
        $vistas = [];

        foreach ($finder->findInstanceOf($stmts, Variable::class) as $variavel) {
            if (!is_string($variavel->name) || $variavel->name === 'this') {
                continue;
            }

            $nome = $variavel->name;

            if (isset($vistas[$nome])) {
                continue;
            }
            $vistas[$nome] = true;

            if (!preg_match('/^[a-z][a-zA-Z0-9]*$/', $nome)) {
                $violacoes[] = new Violation(
                    'nomes.variavel-camel-case',
                    $arquivo,
                    $variavel->getStartLine(),
                    "Variável \${$nome} deveria estar em camelCase.",
                );
            } elseif (strlen($nome) < 3 && !in_array($nome, $this->curtasPermitidas, true)) {
                $violacoes[] = new Violation(
                    'nomes.variavel-abreviada',
                    $arquivo,
                    $variavel->getStartLine(),
                    "Variável \${$nome} é abreviada demais; use um nome descritivo.",
                    'aviso',
                );
            }
        }

        return $violacoes;
    }

    private function sufixoDoDiretorio(string $arquivo): ?string
    {
        foreach (self::SUFIXOS as $pasta => $sufixo) {
            if (Caminho::contem($arquivo, $pasta)) {
                return $sufixo;
            }
        }

        return null;
    }
}
