# corretor-php (padrão Laravel)

Padrão global para projetos Laravel, com ferramenta para verificar e ajudar a corrigir. O guia completo, com as regras, identificadores e fontes, está em [`docs/PADRAO.md`](docs/PADRAO.md).

## Peças

1. **`docs/PADRAO.md`:** o padrão: estrutura de pastas, nomes (classes, métodos, variáveis, rotas, tabelas), camadas, controllers, services, models, erros, banco, segurança e testes.
2. **`presets/`:** configurações prontas para copiar em qualquer projeto: Pint (PER), PHPStan com Larastan (nível 8), Deptrac (dependências entre camadas), Rector e EditorConfig.
3. **`bin/corretor`:** CLI que verifica o código contra as regras automatizáveis e gera um relatório. Não altera arquivos.
4. **`skills/padronizar-laravel/`:** skill do Claude que aplica o padrão com diagnóstico, plano aprovado, correção em pequenos passos e validação.

## Instalação em um projeto

```bash
composer require --dev bertrapp/corretor-php
composer require --dev laravel/pint larastan/larastan phpstan/phpstan qossmic/deptrac driftingly/rector-laravel rector/rector

vendor/bin/corretor instalar          # copia presets/ para a raiz do projeto
vendor/bin/corretor analisar          # relatório no terminal
```

Use `vendor/bin/corretor analisar . --formato=json --saida=padrao-relatorio.json` para gerar o JSON que o skill lê.

Códigos de saída: `0` sem violações, `1` com violações, `2` erro de uso. Use `1` para bloquear merge no CI.

## Regras automatizadas

| Regra | O que verifica | Severidade |
|---|---|---|
| `nomes.sufixo-da-classe` | classe sem o sufixo do diretório (`Controller`, `Service`...) | aviso |
| `nomes.metodo-camel-case` | método fora de camelCase | erro |
| `nomes.variavel-camel-case` | variável fora de camelCase | erro |
| `nomes.variavel-abreviada` | variável de uma ou duas letras (exceto `$i`, `$j`, `$k`, `$e`) | aviso |
| `controller.sem-regra-de-negocio` | `DB::`, `Http::`, `Mail::` ou `Schema::` em controller | erro |
| `controller.validacao-em-form-request` | `->validate()` inline em controller | erro |
| `controller.limite-metodos` | controller com mais de 7 métodos públicos | aviso |
| `controller.consulta-eloquent-direta` | `Model::where()`, `Model::find()` etc. em controller | aviso |
| `rotas.nomeadas` | rota sem `->name()` ou nome fora de `recurso.acao` | erro |
| `rotas.convencoes` | URI fora de kebab-case, com verbo, ou closure com mais de uma instrução | erro / aviso |
| `camadas.sem-http` | `request()`, `response()`, `redirect()`, `session()` ou `back()` em service ou model | erro |
| `geral.depuracao-esquecida` | `dd`, `dump`, `var_dump`, `print_r`, `ray` | erro |
| `geral.env-fora-de-config` | `env()` fora de `config/` | erro |

O que não é automatizado (decisões de domínio, tratamento de erro, desenho de API, banco) está descrito em `docs/PADRAO.md` e é verificado em revisão de código.

## Estrutura do pacote

```
bin/corretor                          executável
docs/PADRAO.md                        o padrão, com identificadores e fontes
presets/                              configurações copiadas para os projetos
skills/padronizar-laravel/SKILL.md    skill do Claude
src/Cli/                              comandos analisar e instalar
src/Analysis/                         analisador, contrato das regras, auxiliares
src/Analysis/Rules/                   regras concretas
src/Report/Relatorio.php              relatório em md e json
tests/RegrasTest.php                  um caso que gera violação e um que passa, por regra
```

## Como adicionar uma regra

1. Crie uma classe em `src/Analysis/Rules/` que implemente `Rule`.
2. Defina `nome()` no formato `grupo.nome-da-regra`.
3. Em `verificar()`, use `NodeFinder` para achar os nós e devolva `Violation`s. Use `Caminho::contem()` para checar pastas, porque ele funciona em Windows e Linux.
4. Registre a regra em `Application::regrasPadrao()`.
5. Adicione o identificador à tabela de `docs/PADRAO.md` e à tabela acima.
6. Escreva um caso que deve gerar violação e outro que não deve em `tests/RegrasTest.php`.

## Status

Ainda não foi executado de ponta a ponta. Os testes e a CLI dependem de `composer install` (`nikic/php-parser` e `phpunit`). Os presets dependem das versões das ferramentas (Pint, Larastan, Deptrac, Rector): confira a sintaxe com a versão instalada antes de usar no CI.
