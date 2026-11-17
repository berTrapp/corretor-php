<?php

declare(strict_types=1);

namespace Corretor\Tests;

use Corretor\Analysis\Rule;
use Corretor\Analysis\Rules\CamadasSemHttp;
use Corretor\Analysis\Rules\ControllerLimites;
use Corretor\Analysis\Rules\ControllerSemRegraDeNegocio;
use Corretor\Analysis\Rules\ErrosTratamento;
use Corretor\Analysis\Rules\GeralProibidos;
use Corretor\Analysis\Rules\NomesDeClassesEVariaveis;
use Corretor\Analysis\Rules\RotasConvencoes;
use Corretor\Analysis\Rules\RotasNomeadas;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;

final class RegrasTest extends TestCase
{
    /** @return list<\Corretor\Analysis\Violation> */
    private function verificar(Rule $regra, string $arquivo, string $codigo): array
    {
        $stmts = (new ParserFactory())->createForHostVersion()->parse($codigo) ?? [];

        return $regra->verificar($arquivo, $stmts);
    }

    private const CONTROLLER = '/projeto/app/Http/Controllers/PedidoController.php';
    private const ROTAS = '/projeto/routes/web.php';

    public function testControllerNaoPodeUsarDb(): void
    {
        $regra = new ControllerSemRegraDeNegocio();
        $codigo = '<?php class PedidoController { public function index() { return DB::table("pedidos")->get(); } }';

        $this->assertTrue($regra->aplicaA(self::CONTROLLER));
        $this->assertCount(1, $this->verificar($regra, self::CONTROLLER, $codigo));
    }

    public function testControllerPodeUsarCache(): void
    {
        $regra = new ControllerSemRegraDeNegocio();
        $codigo = '<?php class PedidoController { public function index() { return Cache::get("x"); } }';

        $this->assertCount(0, $this->verificar($regra, self::CONTROLLER, $codigo));
    }

    public function testRotaSemNomeGeraViolacao(): void
    {
        $regra = new RotasNomeadas();
        $codigo = "<?php Route::get('/pedidos', [PedidoController::class, 'index']);";

        $this->assertCount(1, $this->verificar($regra, self::ROTAS, $codigo));
    }

    public function testRotaComNomeValidoPassa(): void
    {
        $regra = new RotasNomeadas();
        $codigo = "<?php Route::get('/pedidos', [PedidoController::class, 'index'])->name('pedidos.index');";

        $this->assertCount(0, $this->verificar($regra, self::ROTAS, $codigo));
    }

    public function testRotaComNomeForaDoPadraoGeraViolacao(): void
    {
        $regra = new RotasNomeadas();
        $codigo = "<?php Route::get('/pedidos', [PedidoController::class, 'index'])->name('Pedidos-Index');";

        $this->assertCount(1, $this->verificar($regra, self::ROTAS, $codigo));
    }

    public function testUriComVerboGeraViolacao(): void
    {
        $regra = new RotasConvencoes();
        $codigo = "<?php Route::post('/pedidos/criar', [PedidoController::class, 'store']);";

        $this->assertCount(1, $this->verificar($regra, self::ROTAS, $codigo));
    }

    public function testUriEmPadraoPassa(): void
    {
        $regra = new RotasConvencoes();
        $codigo = "<?php Route::get('/pedidos/{pedido}/itens', [ItemController::class, 'index']);";

        $this->assertCount(0, $this->verificar($regra, self::ROTAS, $codigo));
    }

    public function testDdEsquecidoGeraViolacao(): void
    {
        $regra = new GeralProibidos();
        $codigo = '<?php dd($pedido);';

        $this->assertCount(1, $this->verificar($regra, '/projeto/app/Models/Pedido.php', $codigo));
    }

    public function testEnvForaDeConfigGeraViolacao(): void
    {
        $regra = new GeralProibidos();
        $codigo = '<?php $x = env("APP_KEY");';

        $this->assertCount(1, $this->verificar($regra, '/projeto/app/Models/Pedido.php', $codigo));
        $this->assertCount(0, $this->verificar($regra, '/projeto/config/app.php', $codigo));
    }

    public function testServiceNaoUsaRequest(): void
    {
        $regra = new CamadasSemHttp();
        $codigo = '<?php class PedidoService { public function criar() { return request("nome"); } }';

        $this->assertCount(1, $this->verificar($regra, '/projeto/app/Services/PedidoService.php', $codigo));
    }

    public function testClasseSemSufixoDoDiretorioGeraAviso(): void
    {
        $regra = new NomesDeClassesEVariaveis();
        $codigo = '<?php class Pedido {}';

        $violacoes = $this->verificar($regra, '/projeto/app/Services/Pedido.php', $codigo);
        $this->assertCount(1, $violacoes);
        $this->assertSame('nomes.sufixo-da-classe', $violacoes[0]->regra);
    }

    public function testVariavelAbreviadaGeraAviso(): void
    {
        $regra = new NomesDeClassesEVariaveis();
        $codigo = '<?php $x = 1; $pedidoAtual = 2;';

        $violacoes = $this->verificar($regra, self::CONTROLLER, $codigo);
        $this->assertCount(1, $violacoes);
        $this->assertSame('nomes.variavel-abreviada', $violacoes[0]->regra);
    }

    public function testControllerComMaisDeSeteMetodosGeraAviso(): void
    {
        $regra = new ControllerLimites();
        $metodos = implode(' ', array_map(static fn (int $i): string => "public function m{$i}() {}", range(1, 8)));
        $codigo = "<?php class PedidoController { {$metodos} }";

        $violacoes = $this->verificar($regra, self::CONTROLLER, $codigo);
        $this->assertCount(1, $violacoes);
        $this->assertSame('controller.limite-metodos', $violacoes[0]->regra);
    }

    /** @return list<string> */
    private function regrasDeErro(string $arquivo, string $codigo): array
    {
        return array_map(fn ($v) => $v->regra, $this->verificar(new ErrosTratamento(), $arquivo, $codigo));
    }

    public function testExcecaoGenericaGeraAviso(): void
    {
        $codigo = '<?php class PedidoService { public function f() { throw new \\RuntimeException("x"); } }';
        $this->assertSame(['erros.excecao-generica'], $this->regrasDeErro('/projeto/app/Services/PedidoService.php', $codigo));
    }

    public function testExcecaoDeDominioPassa(): void
    {
        $codigo = '<?php class PedidoService { public function f() { throw new PedidoJaFaturado(); } }';
        $this->assertSame([], $this->regrasDeErro('/projeto/app/Services/PedidoService.php', $codigo));
    }

    public function testExcecaoGenericaEmAppExceptionsPassa(): void
    {
        $codigo = '<?php class Base { public function f() { throw new Exception("x"); } }';
        $this->assertSame([], $this->regrasDeErro('/projeto/app/Exceptions/Base.php', $codigo));
    }

    public function testTryCatchEmControllerEMensagemExposta(): void
    {
        $codigo = '<?php class PedidoController { public function f() { try { x(); } catch (\\Throwable $e) { return response()->json(["erro" => $e->getMessage()]); } } }';
        $this->assertSame(['erros.try-catch-em-controller', 'erros.mensagem-exposta'], $this->regrasDeErro(self::CONTROLLER, $codigo));
    }

    public function testTryCatchForaDeControllerComLogPassa(): void
    {
        $codigo = '<?php class Job { public function f() { try { x(); } catch (\\Throwable $e) { report($e); } } }';
        $this->assertSame([], $this->regrasDeErro('/projeto/app/Jobs/Job.php', $codigo));
    }

    public function testCatchVazioGeraErro(): void
    {
        $codigo = '<?php class Job { public function f() { try { x(); } catch (\\Throwable $e) { /* nada */ } } }';
        $this->assertSame(['erros.catch-vazio'], $this->regrasDeErro('/projeto/app/Jobs/Job.php', $codigo));
    }

    public function testWithExceptionsVazioGeraAviso(): void
    {
        $codigo = '<?php return Application::configure()->withExceptions(function (Exceptions $exceptions) { })->create();';
        $this->assertSame(['erros.handler-central-vazio'], $this->regrasDeErro('/projeto/bootstrap/app.php', $codigo));
    }

    public function testWithExceptionsComTratamentoPassa(): void
    {
        $codigo = '<?php return Application::configure()->withExceptions(function (Exceptions $exceptions) { $exceptions->render(fn (PedidoJaFaturado $e) => null); })->create();';
        $this->assertSame([], $this->regrasDeErro('/projeto/bootstrap/app.php', $codigo));
    }
}
