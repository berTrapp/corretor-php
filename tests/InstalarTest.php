<?php

declare(strict_types=1);

namespace Corretor\Tests;

use Corretor\Cli\Instalar;
use PHPUnit\Framework\TestCase;

final class InstalarTest extends TestCase
{
    private string $tmp;

    protected function setUp(): void
    {
        $this->tmp = sys_get_temp_dir() . '/corretor-instalar-' . bin2hex(random_bytes(4));
        mkdir($this->tmp);
        touch($this->tmp . '/artisan');
    }

    protected function tearDown(): void
    {
        foreach (scandir($this->tmp) ?: [] as $arquivo) {
            if ($arquivo !== '.' && $arquivo !== '..') {
                unlink($this->tmp . '/' . $arquivo);
            }
        }
        rmdir($this->tmp);
    }

    public function testNaoCriaRectorSemADependencia(): void
    {
        file_put_contents($this->tmp . '/composer.json', '{"require-dev":{"rector/rector":"^2.0"}}');

        $saida = $this->instalar();

        self::assertFileDoesNotExist($this->tmp . '/rector.php');
        self::assertStringContainsString('driftingly/rector-laravel', $saida);
        self::assertFileExists($this->tmp . '/pint.json');
    }

    public function testCriaRectorComADependencia(): void
    {
        file_put_contents(
            $this->tmp . '/composer.json',
            '{"require-dev":{"rector/rector":"^2.0","driftingly/rector-laravel":"^2.0"}}',
        );

        $this->instalar();

        self::assertFileExists($this->tmp . '/rector.php');
    }

    public function testGitignoreRecebeGeradosSemDuplicar(): void
    {
        file_put_contents($this->tmp . '/.gitignore', "/vendor\n.deptrac.cache");

        $this->instalar();
        $this->instalar();

        self::assertSame(
            "/vendor\n.deptrac.cache\n/padrao-relatorio.json\n",
            file_get_contents($this->tmp . '/.gitignore'),
        );
    }

    private function instalar(): string
    {
        ob_start();
        $codigo = Instalar::executar([$this->tmp, '--stack=laravel']);
        $saida = (string) ob_get_clean();
        self::assertSame(0, $codigo);

        return $saida;
    }
}
