# Padrão Laravel

Padrão global para projetos Laravel. Cada regra tem um identificador estável, que aparece no relatório do `corretor analisar` quando é verificável por ferramenta. As regras sem identificador dependem de revisão humana ou do skill.

## 1. Fontes

| Fonte | O que orienta |
|---|---|
| *Clean Code* (Robert C. Martin) | nomes, tamanho de funções, responsabilidade única |
| *Clean Architecture* (Robert C. Martin) | dependências entre camadas; regra de negócio sem depender de framework |
| *Patterns of Enterprise Application Architecture* (Martin Fowler) | services, transações, Unit of Work, quando repositório faz sentido |
| *Refactoring* (Martin Fowler) | como melhorar código existente em pequenos passos com testes |
| *Domain-Driven Design* (Eric Evans) | organização por domínio, linguagem ubíqua, agregados |
| *Modern PHP* (Josh Lockhart) | tipagem, composer, PSR-4, práticas modernas da linguagem |
| *Laravel: Up & Running* (Matt Stauffer) | convenções e uso idiomático do Laravel |
| PER Coding Style 2.0 | formatação do código PHP (substitui o PSR-12) |
| Documentação oficial do Laravel | Form Requests, Policies, Eloquent, rotas, configuração |

Quando um livro e a documentação oficial divergirem, vale a documentação oficial, porque é a que acompanha as versões do framework.

## 2. Estrutura de pastas

Base: a estrutura nativa do Laravel, com subpastas por domínio dentro de `Services` e `Models` quando o projeto crescer.

```
app/
├── Enums/
├── Events/
├── Http/
│   ├── Controllers/        controllers web
│   │   └── Api/            controllers de API
│   ├── Middleware/
│   ├── Requests/           Form Requests
│   └── Resources/          API Resources
├── Jobs/
├── Listeners/
├── Models/
│   └── Pedidos/            (opcional) agrupamento por domínio
├── Observers/
├── Policies/
├── Providers/
└── Services/
    └── Pedidos/            agrupamento por domínio
config/
database/
├── factories/
├── migrations/
└── seeders/
routes/
resources/views/
tests/
├── Feature/
└── Unit/
```

| ID | Regra | Verificação |
|---|---|---|
| EST-01 | Controllers ficam em `app/Http/Controllers`; API em `app/Http/Controllers/Api`. | revisão |
| EST-02 | Lógica de negócio fica em `app/Services`. Nunca em controllers, rotas ou models. | `controller.*`, `camadas.sem-http` |
| EST-03 | Não criar pastas novas fora dessa estrutura sem registro no próprio padrão. | revisão |
| EST-04 | Agrupar por domínio (`Services/Pedidos/`) só quando houver mais de um domínio. | revisão |

## 3. Nomes

### 3.1 Classes

| ID | Regra | Verificação |
|---|---|---|
| NOM-01 | Classes terminam com o sufixo do diretório: `Controller`, `Request`, `Resource`, `Service`, `Policy`, `Observer`. | `nomes.sufixo-da-classe` |
| NOM-01a | Jobs e Listeners **não** precisam de sufixo: são nomeados pela ação, como na documentação do Laravel (`ProcessarPodcast`, `EnviarNotificacaoDeEnvio`). Os que já têm sufixo (`EnviarEmailJob`) continuam aceitos e não precisam ser renomeados; em código novo, prefira o nome da ação. | revisão |
| NOM-02 | Classes em PascalCase, substantivos no singular (`Pedido`, `PedidoService`). | revisão |
| NOM-03 | Models são singulares (`Pedido`); a tabela é plural (`pedidos`). | revisão |
| NOM-04 | Enums em PascalCase, casos em PascalCase (`StatusPedido::Aberto`). | revisão |
| NOM-05 | Interfaces terminam em `Interface` apenas quando houver mais de uma implementação. | revisão |

### 3.2 Métodos e variáveis

| ID | Regra | Verificação |
|---|---|---|
| NOM-06 | Métodos em camelCase, começando com verbo (`criarPedido`, `calcularTotal`). | `nomes.metodo-camel-case` |
| NOM-07 | Variáveis em camelCase e descritivas (`$pedidoAtual`, não `$p` ou `$x`). Exceções: `$i`, `$j`, `$k`, `$e`. | `nomes.variavel-camel-case`, `nomes.variavel-abreviada` |
| NOM-08 | Booleanos começam com `is`, `has`, `can` ou `should` (`$isAtivo`). | revisão |
| NOM-09 | Constantes em UPPER_SNAKE_CASE. | revisão |
| NOM-10 | Métodos de Eloquent e helpers do framework não são reescritos com nomes próprios sem necessidade. | revisão |

### 3.3 Rotas

| ID | Regra | Verificação |
|---|---|---|
| NOM-11 | Nome da rota no formato `recurso.acao` em minúsculas: `pedidos.index`, `pedidos.show`. | `rotas.nomeadas` |
| NOM-12 | Toda rota declarada à mão tem `->name()`. Rotas de `resource` já são nomeadas pelo Laravel. | `rotas.nomeadas` |

### 3.4 URIs

| ID | Regra | Verificação |
|---|---|---|
| NOM-13 | URIs em kebab-case: `/ordens-de-servico`, não `/ordensDeServico` ou `/ordens_de_servico`. | `rotas.convencoes` |
| NOM-14 | URIs usam substantivos no plural; a ação fica no verbo HTTP (`POST /pedidos`, não `/pedidos/criar`). | `rotas.convencoes` (verbos) |
| NOM-15 | Parâmetros de rota em camelCase, iguais ao nome da variável do controller (`{pedido}`). | revisão |
| NOM-16 | Versão de API no prefixo: `/api/v1`. | revisão |

### 3.5 Outros nomes

| ID | Regra | Verificação |
|---|---|---|
| NOM-17 | Tabelas em snake_case e plural (`itens_pedido`). | revisão |
| NOM-18 | Colunas em snake_case; chaves estrangeiras `<singular>_id` (`pedido_id`). | revisão |
| NOM-19 | Tabela pivot em ordem alfabética e singular (`pedido_produto`). | revisão |
| NOM-20 | Migrations com nome descritivo (`create_pedidos_table`, `add_status_to_pedidos_table`). | revisão |
| NOM-21 | Chaves de configuração em snake_case, agrupadas por serviço. | revisão |
| NOM-22 | Views em kebab-case (`resources/views/pedidos/lista-itens.blade.php`). | revisão |
| NOM-23 | Testes terminam em `Test` (`PedidoServiceTest`). | revisão |

## 4. Camadas e responsabilidades

### 4.1 Controllers

**Devem:** receber a requisição, delegar validação a uma Form Request, checar autorização (Policy ou Gate), chamar um service e devolver a resposta (view, JSON, redirect ou Resource).

**Não devem:** conter regra de negócio, transações, consultas Eloquent complexas, chamadas a APIs externas ou envio de e-mail.

| ID | Regra | Verificação |
|---|---|---|
| CTR-01 | Sem `DB::`, `Http::`, `Mail::` ou `Schema::` em controllers. | `controller.sem-regra-de-negocio` |
| CTR-02 | Validação em Form Request, nunca com `->validate()` inline. | `controller.validacao-em-form-request` |
| CTR-03 | No máximo 7 métodos públicos (as 7 ações de um resource). | `controller.limite-metodos` |
| CTR-04 | Sem consulta Eloquent direta (`Pedido::where`). Use o service. | `controller.consulta-eloquent-direta` |
| CTR-05 | Autorização com `$this->authorize()` ou `Gate`, usando Policy. | revisão |
| CTR-06 | Controller não retorna dados de model diretamente; usa API Resource. | revisão |

### 4.2 Form Requests

| ID | Regra | Verificação |
|---|---|---|
| REQ-01 | Toda validação de entrada fica em `app/Http/Requests`. | `controller.validacao-em-form-request` |
| REQ-02 | Mensagens de erro customizadas ficam no `messages()` da própria Request. | revisão |
| REQ-03 | `authorize()` faz só a checagem simples; autorização de recurso vai para Policy. | revisão |

### 4.3 Services

**Devem:** concentrar regra de negócio, orquestrar operações, controlar transações e receber dependências por construtor.

**Não devem:** depender de `request()`, `response()`, `redirect()`, `session()` ou `back()`. Assim rodam em jobs, comandos e testes sem HTTP.

| ID | Regra | Verificação |
|---|---|---|
| SRV-01 | Sem helpers de HTTP em services e models. | `camadas.sem-http` |
| SRV-02 | Dependências recebidas por construtor, nunca criadas com `new` dentro do método. | revisão |
| SRV-03 | Operações de escrita com mais de um passo ficam em `DB::transaction`. | revisão |
| SRV-04 | Services não são depósitos genéricos (`UtilService`, `HelperService`). | revisão |
| SRV-05 | Services recebem e devolvem tipos claros (DTOs ou models), nunca `array` genérico em fronteira pública. | revisão |

### 4.4 Actions e Repositories

Este padrão adota **services**, e não actions. Repositórios não são obrigatórios.

| ID | Regra | Verificação |
|---|---|---|
| ACT-01 | Não criar classes `*Action` em paralelo a services sem decisão registrada. | revisão |
| REP-01 | Repositório só existe quando isola acesso a dados com necessidade real (troca de fonte, testes sem banco). | revisão |
| REP-02 | Nunca um `BaseRepository` com `all()`, `find()`, `create()` apenas repetindo o Eloquent. | revisão |

### 4.5 Models

**Devem:** relacionamentos, casts, scopes, accessors e mutators ligados aos dados.

**Não devem:** conhecer HTTP nem concentrar regra de negócio pesada.

| ID | Regra | Verificação |
|---|---|---|
| MOD-01 | Models sem helpers de HTTP. | `camadas.sem-http` |
| MOD-02 | Atributos de atribuição em massa declarados (`$fillable`), sem `$guarded = []`. | revisão |
| MOD-03 | Casts declarados em `casts()` (datas, enums, booleanos, JSON). | revisão |
| MOD-04 | Enums de status usam `enum` do PHP com `casts`, não strings soltas. | revisão |
| MOD-05 | Scopes para filtros reutilizáveis (`scopeAtivos`), não consultas repetidas. | revisão |

### 4.6 Jobs, Events e Listeners

Decisão (NOM-01a): Jobs e Listeners seguem a convenção do Laravel e são nomeados pela ação, sem sufixo `Job` ou `Listener`. A pasta (`app/Jobs`, `app/Listeners`) já diz o papel, e o `make:job`/`make:listener` não acrescenta sufixo. Services, ao contrário, mantêm o sufixo `Service` por decisão da casa: o Laravel não define convenção para eles, e o sufixo distingue o service das classes de apoio na mesma pasta.

| ID | Regra | Verificação |
|---|---|---|
| ASY-01 | Trabalho demorado (e-mail, integração, relatório) vai para Job em fila. | revisão |
| ASY-02 | Jobs são idempotentes ou têm `$tries` e `backoff` definidos. | revisão |
| ASY-03 | Events descrevem algo que aconteceu (`PedidoCriado`), no passado. | revisão |
| ASY-04 | Listeners fazem uma coisa só. | revisão |

### 4.7 Resources (API)

| ID | Regra | Verificação |
|---|---|---|
| API-01 | Respostas de API saem por API Resource, nunca `toArray()` ou `$model` direto. | revisão |
| API-02 | Coleções paginadas com `Resource::collection($paginator)`. | revisão |
| API-03 | Códigos HTTP corretos: 201 para criação, 204 para remoção, 422 para validação, 404 para não encontrado. | revisão |

## 5. Rotas

| ID | Regra | Verificação |
|---|---|---|
| ROT-01 | Rotas ficam em `routes/web.php` ou `routes/api.php`. | revisão |
| ROT-02 | Toda rota declarada à mão tem nome (`NOM-11`). | `rotas.nomeadas` |
| ROT-03 | Rotas apontam para controllers, não para closures com lógica. Closure com mais de uma instrução gera aviso. | `rotas.convencoes` |
| ROT-04 | Agrupar por prefixo e middleware com `Route::prefix()->middleware()->group()`. | revisão |
| ROT-05 | Usar `Route::resource` ou `apiResource` para CRUD padrão. | revisão |
| ROT-06 | Route model binding no lugar de buscar o model pelo id no controller. | revisão |

## 6. Erros e respostas

| ID | Regra | Verificação |
|---|---|---|
| ERR-01 | Erros de regra de negócio são exceções de domínio (`PedidoJaFaturado`), não `Exception` genérica. | `erros.excecao-generica` |
| ERR-02 | Exceções de domínio são tratadas em um único lugar (`bootstrap/app.php` ou o `Handler`). | `erros.try-catch-em-controller`, `erros.handler-central-vazio` |
| ERR-03 | Mensagens de erro para o usuário não expõem stack trace, SQL ou nomes internos. | `erros.mensagem-exposta` |
| ERR-04 | Nunca engolir exceção com `catch` vazio. | `erros.catch-vazio` |

## 7. Banco e Eloquent

| ID | Regra | Verificação |
|---|---|---|
| BD-01 | Sem consulta dentro de loop (N+1). Usar `with()` para carregar relacionamentos. | revisão (e análise do Larastan) |
| BD-02 | Migrations têm `down()` que reverte a alteração. | revisão |
| BD-03 | Migration já publicada não é editada; cria-se uma nova. | revisão |
| BD-04 | Chaves estrangeiras com `constrained()` e ação em `cascadeOnDelete()` ou `restrictOnDelete()` definida explicitamente. | revisão |
| BD-05 | Índices em colunas usadas em `where` e `join` frequentes. | revisão |
| BD-06 | Seeders e factories para dados de teste, nunca dados de produção em migrations. | revisão |

## 8. Segurança

| ID | Regra | Verificação |
|---|---|---|
| SEG-01 | `env()` só em `config/`; o resto do código usa `config()`. | `geral.env-fora-de-config` |
| SEG-02 | Nenhum segredo no código ou no repositório. | revisão |
| SEG-03 | Autorização em toda ação que mexe em dados de outro usuário (Policy). | revisão |
| SEG-04 | Entrada sempre validada (Form Request). Saída escapada na view (sintaxe `{{ }}`, não `{!! !!}` sem motivo). | revisão |
| SEG-05 | Consultas com parâmetros (Eloquent ou bindings). Nunca concatenar entrada em SQL. | revisão |
| SEG-06 | Sem depuração em produção: `dd`, `dump`, `var_dump`, `print_r`, `ray`. | `geral.depuracao-esquecida` |

## 9. Testes

| ID | Regra | Verificação |
|---|---|---|
| TST-01 | Feature tests para rotas e fluxos completos (controller até banco). | revisão |
| TST-02 | Unit tests para services e regras de negócio, sem HTTP. | revisão |
| TST-03 | Toda correção de bug começa com um teste que reproduz o bug. | revisão |
| TST-04 | Cobertura mínima definida pelo projeto, verificada no CI. | CI |
| TST-05 | Testes não dependem de ordem nem de dados deixados por outro teste (`RefreshDatabase`). | revisão |

## 10. Estilo e análise estática

| ID | Regra | Ferramenta |
|---|---|---|
| EST-STY-01 | Formatação conforme PER Coding Style 2.0, aplicada pelo Pint. | `pint.json` |
| EST-STY-02 | `declare(strict_types=1);` em todo arquivo PHP. | Pint (`declare_strict_types`) |
| EST-STY-03 | Tipos declarados em parâmetros, retornos e propriedades. | PHPStan nível 8 |
| EST-STY-04 | Imports ordenados e sem imports não usados. | Pint |
| EST-STY-05 | Dependências entre camadas respeitam o grafo do `deptrac.yaml`. | Deptrac |
| EST-STY-06 | Refatorações mecânicas aplicadas com Rector, em lote e revisadas. | Rector |
| EST-STY-07 | `.editorconfig` na raiz do projeto. | EditorConfig |

## 11. Como o padrão é verificado

1. **Formatação, tipos e dependências** são verificados pelas ferramentas de `presets/` (Pint, PHPStan, Deptrac). Elas rodam no CI e bloqueiam o merge.
2. **Regras de nome, rota, camada e proibições** são verificadas pelo `corretor analisar`. Erros bloqueiam; avisos são revisados.
3. **O restante** (decisões de domínio, tratamento de erro, nomes com sentido de negócio) é verificado em revisão de código e pelo skill `padronizar-laravel`, que aponta os casos para decisão humana.

Uma regra que o time não consegue verificar nem revisar não deve estar aqui. Se ela não puder ser testada, ela sai do padrão.
