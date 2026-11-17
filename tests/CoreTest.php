<?php

declare(strict_types=1);

namespace Corretor\Tests;

use Corretor\Cli\Application;
use Corretor\Core\Config;
use Corretor\Core\Stacks;
use Corretor\Stacks\Laravel\Rules\NomesDeClassesEVariaveis;
use Corretor\Stacks\Node\NodeStack;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;

final class CoreTest extends TestCase
{
    private string $tmp;

    protected function setUp(): void
    {
        $this->tmp = sys_get_temp_dir() . '/corretor-' . bin2hex(random_bytes(4));
        mkdir($this->tmp);
    }

    protected function tearDown(): void
    {
        @unlink($this->tmp . '/.padrao.yaml');
        @unlink($this->tmp . '/artisan');
        @rmdir($this->tmp);
    }

    public function testDesligarExigeMotivo(): void
    {
        file_put_contents($this->tmp . '/.padrao.yaml', <<<'YAML'
        desligar:
          - regra: nomes.variavel-abreviada
            motivo: Código legado.
          - regra: rotas.nomeadas
        nomes-curtos-permitidos: [nf]
        YAML);

        $config = Config::carregar($this->tmp);

        $this->assertTrue($config->desligada('nomes.variavel-abreviada'));
        $this->assertFalse($config->desligada('rotas.nomeadas'));
        $this->assertSame('config.excecao-sem-motivo', $config->problemas[0]->regra ?? null);
        $this->assertSame(['nf'], $config->nomesCurtos);
    }

    public function testSemArquivoDeConfigNadaMuda(): void
    {
        $config = Config::carregar($this->tmp);

        $this->assertSame([], $config->desligadas);
        $this->assertSame([], $config->problemas);
    }

    public function testDetectaStackPeloArquivo(): void
    {
        touch($this->tmp . '/artisan');

        $this->assertSame(['laravel'], array_map(fn ($s) => $s->nome(), Stacks::detectar($this->tmp)));
        $this->assertSame(['node'], array_map(fn ($s) => $s->nome(), Stacks::detectar(__DIR__ . '/fixtures/node-api')));
    }

    public function testNomesCurtosLegitimosEDeduplicacao(): void
    {
        $codigo = '<?php function f() { $id = 1; $uf = "SP"; $nf = 2; $q = 1; $q = 2; return $q; }';
        $stmts = (new ParserFactory())->createForHostVersion()->parse($codigo) ?? [];

        $padrao = (new NomesDeClassesEVariaveis())->verificar('/p/app/x.php', $stmts);
        $comExtra = (new NomesDeClassesEVariaveis(['nf']))->verificar('/p/app/x.php', $stmts);

        $this->assertSame(['$nf', '$q'], self::variaveis($padrao));
        $this->assertSame(['$q'], self::variaveis($comExtra));
    }

    public function testStackNodeComAstGrep(): void
    {
        try {
            $violacoes = (new NodeStack())->analisar(__DIR__ . '/fixtures/node-api', new Config());
        } catch (\RuntimeException $e) {
            $this->markTestSkipped($e->getMessage());
        }

        $regras = array_map(fn ($v) => basename($v->arquivo) . ' ' . $v->regra, $violacoes);
        sort($regras);

        $this->assertSame([
            'pedido.controller.ts controller.sem-regra-de-negocio',
            'pedido.controller.ts erros.mensagem-exposta',
            'pedido.controller.ts erros.try-catch-em-controller',
            'pedido.controller.ts rotas.convencoes',
            'pedido.service.ts camadas.sem-http',
            'pedido.service.ts erros.catch-vazio',
            'pedido.service.ts erros.excecao-generica',
            'pedido.service.ts geral.depuracao-esquecida',
            'pedido.service.ts geral.env-fora-de-config',
            'pedido.service.ts nomes.variavel-camel-case',
        ], $regras);
    }

    public function testStackDesconhecidaEErroDeUso(): void
    {
        $this->assertIsString(Application::stacksDoProjeto($this->tmp, 'cobol', new Config()));
        $this->assertIsString(Application::stacksDoProjeto($this->tmp, null, new Config()));
    }

    /** @param list<\Corretor\Analysis\Violation> $violacoes @return list<string> */
    private static function variaveis(array $violacoes): array
    {
        $nomes = [];
        foreach ($violacoes as $v) {
            if ($v->regra === 'nomes.variavel-abreviada' && preg_match('/(\$\w+)/', $v->mensagem, $m)) {
                $nomes[] = $m[1];
            }
        }

        return $nomes;
    }
}
