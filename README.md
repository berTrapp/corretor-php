# corretor-php

Padrão de código verificável por ferramenta, aplicável a qualquer projeto. Stacks suportadas: **Laravel** e **Node/TypeScript**.

O piso são as ferramentas oficiais de cada stack (formatador, análise estática, camadas). O `corretor` só verifica o que elas não cobrem, e dá às regras o mesmo identificador entre stacks quando a intenção é a mesma.

| Stack | Padrão | Motor das regras | Ferramentas oficiais (presets) |
|---|---|---|---|
| `laravel` | [`stacks/laravel/docs/PADRAO.md`](stacks/laravel/docs/PADRAO.md) | PHP (nikic/php-parser) | Pint, PHPStan + Larastan, Deptrac, Rector |
| `node` | [`stacks/node/docs/PADRAO.md`](stacks/node/docs/PADRAO.md) | [ast-grep](https://ast-grep.github.io), regras em YAML | Prettier, ESLint + typescript-eslint, `tsc --strict`, dependency-cruiser |

## Uso

Laravel:

```bash
composer require --dev bertrapp/corretor-php
vendor/bin/corretor instalar          # copia os presets da stack para a raiz do projeto
vendor/bin/corretor analisar          # relatório no terminal
```

Node/TypeScript (o corretor é um pacote PHP; o ast-grep vem do npm):

```bash
composer global require bertrapp/corretor-php
npm i -D @ast-grep/cli
corretor instalar
corretor analisar
```

Opções:

- `--stack=laravel|node` força a stack. Sem ela, vale o `stack:` do `.padrao.yaml` ou a detecção (`artisan` → laravel, `package.json` → node).
- `--formato=json --saida=padrao-relatorio.json` gera o JSON que o skill e o CI leem. O formato está em [`schemas/relatorio.schema.json`](schemas/relatorio.schema.json).

Códigos de saída: `0` sem erros, `1` com violação de severidade **erro** (bloqueia o merge), `2` erro de uso. Avisos não mudam o código de saída: são para revisão.

## Exceções por projeto: `.padrao.yaml`

```yaml
stack: laravel
desligar:
  - regra: controller.consulta-eloquent-direta
    motivo: "Controllers de relatório só leem; migrar para services no módulo financeiro."
nomes-curtos-permitidos: [nf, cd]
```

Regra desligada sem `motivo` continua ligada e gera o erro `config.excecao-sem-motivo`. As regras desligadas aparecem no relatório.

## Regras automatizadas: Laravel

| Regra | O que verifica | Severidade |
|---|---|---|
| `nomes.sufixo-da-classe` | classe sem o sufixo do diretório (`Controller`, `Request`, `Resource`, `Service`, `Policy`, `Observer`); Jobs e Listeners seguem o Laravel e não exigem sufixo | aviso |
| `nomes.metodo-camel-case` | método fora de camelCase | erro |
| `nomes.variavel-camel-case` | variável fora de camelCase | erro |
| `nomes.variavel-abreviada` | variável de uma ou duas letras (exceto `$i`, `$j`, `$k`, `$e`, `$id`, `$uf`, `$ip`, `$db`, `$os` e as de `.padrao.yaml`); uma vez por arquivo | aviso |
| `controller.sem-regra-de-negocio` | `DB::`, `Http::`, `Mail::` ou `Schema::` em controller | erro |
| `controller.validacao-em-form-request` | `->validate()` inline em controller | erro |
| `controller.limite-metodos` | controller com mais de 7 métodos públicos | aviso |
| `controller.consulta-eloquent-direta` | `Model::where()`, `Model::find()` etc. em controller | aviso |
| `rotas.nomeadas` | rota sem `->name()` ou nome fora de `recurso.acao` | erro |
| `rotas.convencoes` | URI fora de kebab-case, com verbo, ou closure com mais de uma instrução | erro / aviso |
| `camadas.sem-http` | `request()`, `response()`, `redirect()`, `session()` ou `back()` em service ou model | erro |
| `erros.excecao-generica` | `throw new Exception/RuntimeException/LogicException/ErrorException` fora de `app/Exceptions` | aviso |
| `erros.try-catch-em-controller` | `try/catch` em controller | aviso |
| `erros.handler-central-vazio` | `withExceptions()` vazio em `bootstrap/app.php` | aviso |
| `erros.mensagem-exposta` | `$e->getMessage()` dentro de `return` em `app/Http` | erro |
| `erros.catch-vazio` | `catch` sem instruções | erro |
| `geral.depuracao-esquecida` | `dd`, `dump`, `var_dump`, `print_r`, `ray` | erro |
| `geral.env-fora-de-config` | `env()` fora de `config/` | erro |

## Regras automatizadas: Node/TypeScript

| Regra | O que verifica | Severidade |
|---|---|---|
| `geral.depuracao-esquecida` | `console.log/debug/dir`, `debugger` (fora de testes, `scripts/` e `bin/`) | erro |
| `geral.env-fora-de-config` | `process.env` fora de `config/` | erro |
| `erros.catch-vazio` | `catch` sem instruções | erro |
| `erros.excecao-generica` | `throw new Error(...)` fora de `errors/` ou `exceptions/` | aviso |
| `erros.mensagem-exposta` | `erro.message` em `res.json/send` | erro |
| `erros.try-catch-em-controller` | `try/catch` em `*.controller.ts` ou `controllers/` | aviso |
| `controller.sem-regra-de-negocio` | `@InjectRepository`, `new PrismaClient`, `this.prisma.x.y()` ou `.query()` em controller | erro |
| `rotas.convencoes` | caminho de `@Controller/@Get/@Post...` com maiúscula ou `_` | erro |
| `nomes.variavel-camel-case` | variável em snake_case | erro |
| `camadas.sem-http` | `throw new HttpException/NotFoundException...` em service | aviso |

O que não é automatizado (decisões de domínio, desenho de API, banco) está no `PADRAO.md` de cada stack e é verificado em revisão de código.

## CI e hooks

- `templates/ci/<stack>.yml`: GitHub Actions que roda formatador, análise estática, camadas, o corretor e os testes. Copie para `.github/workflows/padrao.yml`.
- `templates/hooks/pre-commit-<stack>`: verificação rápida dos arquivos do commit.

## Estrutura do pacote

```
bin/corretor                         executável
src/Core/                            contrato Stack, registro das stacks, .padrao.yaml
src/Cli/                             comandos analisar e instalar
src/Analysis/                        motor PHP: analisador, contrato Rule, Violation
src/Adapters/AstGrep.php             motor das stacks não PHP
src/Report/Relatorio.php             relatório em md e json
schemas/relatorio.schema.json        contrato do relatório (versionado)
stacks/laravel/                      docs/, presets/, skills/, src/ (regras PHP)
stacks/node/                         docs/, presets/, rules/ (YAML), tests/ (casos do ast-grep)
templates/ci/, templates/hooks/      por stack
tests/                               PHPUnit: regras Laravel, núcleo e stack node
```

## Como adicionar uma regra

**Laravel (PHP):** crie uma classe em `stacks/laravel/src/Rules/` que implemente `Rule`, registre em `LaravelStack::regras()` e escreva em `tests/RegrasTest.php` um caso que gera violação e outro que passa. Use `Caminho::contem()` para pastas.

**Node (YAML):** crie `stacks/node/rules/<id>.yml` e `stacks/node/tests/<id>-test.yml` com casos `valid` e `invalid`. Rode `ast-grep test --config stacks/node/sgconfig.yml --skip-snapshot-tests`.

Em ambos: `id` no formato `grupo.nome-da-regra`, igual ao de outra stack quando a intenção é a mesma; adicione-o ao `PADRAO.md` da stack, à tabela acima e ao `CHANGELOG.md`, com o motivo.

## Testes

```bash
composer install
vendor/bin/phpunit
npx -p @ast-grep/cli ast-grep test --config stacks/node/sgconfig.yml --skip-snapshot-tests
```

## Novas stacks

1. Crie `stacks/<nome>/` com `docs/PADRAO.md`, `presets/` e as regras (YAML do ast-grep sempre que possível).
2. Implemente `Corretor\Core\Stack` e registre em `Stacks::todas()`.
3. Termine com testes passando e uma análise de um projeto real da stack.
