<?php

declare(strict_types=1);

namespace Corretor\Stacks\Laravel;

use Corretor\Analysis\Analyzer;
use Corretor\Analysis\Rule;
use Corretor\Core\Config;
use Corretor\Core\Stack;
use Corretor\Core\Stacks;
use Corretor\Stacks\Laravel\Rules\CamadasSemHttp;
use Corretor\Stacks\Laravel\Rules\ControllerLimites;
use Corretor\Stacks\Laravel\Rules\ControllerSemRegraDeNegocio;
use Corretor\Stacks\Laravel\Rules\ErrosTratamento;
use Corretor\Stacks\Laravel\Rules\GeralProibidos;
use Corretor\Stacks\Laravel\Rules\NomesDeClassesEVariaveis;
use Corretor\Stacks\Laravel\Rules\RotasConvencoes;
use Corretor\Stacks\Laravel\Rules\RotasNomeadas;
use Corretor\Stacks\Laravel\Rules\ValidacaoEmFormRequest;

/** Laravel: regras em PHP sobre a AST do nikic/php-parser. */
final class LaravelStack implements Stack
{
    public function nome(): string
    {
        return 'laravel';
    }

    public function detectar(string $raiz): bool
    {
        return is_file($raiz . '/artisan');
    }

    public function analisar(string $raiz, Config $config): array
    {
        return (new Analyzer(self::regras($config->nomesCurtos)))->analisar($raiz);
    }

    public function pastaPresets(): string
    {
        return Stacks::pasta('laravel') . '/presets';
    }

    public function dependenciasSugeridas(): string
    {
        return 'composer require --dev laravel/pint larastan/larastan phpstan/phpstan '
            . 'deptrac/deptrac driftingly/rector-laravel rector/rector';
    }

    /**
     * @param list<string> $nomesCurtos
     * @return list<Rule>
     */
    public static function regras(array $nomesCurtos = []): array
    {
        return [
            new NomesDeClassesEVariaveis($nomesCurtos),
            new ControllerSemRegraDeNegocio(),
            new ValidacaoEmFormRequest(),
            new ControllerLimites(),
            new RotasNomeadas(),
            new RotasConvencoes(),
            new CamadasSemHttp(),
            new ErrosTratamento(),
            new GeralProibidos(),
        ];
    }
}
