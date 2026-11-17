# Padrão Node/TypeScript

Padrão para projetos Node com TypeScript (NestJS, Express, Fastify, Next/React). Os identificadores de regra são os mesmos do padrão Laravel quando a intenção é a mesma, para que um relatório com várias stacks faça sentido.

O piso são as ferramentas oficiais (`presets/`): Prettier formata, ESLint com typescript-eslint estrito e `tsc --strict` verificam tipos e estilo, dependency-cruiser verifica camadas. O `corretor analisar` só cobre o que elas não cobrem.

## 1. Estrutura

| ID | Regra | Verificação |
|---|---|---|
| EST-01 | Código em `src/`, organizado por módulo de domínio (`src/pedidos/`), não por tipo técnico. | revisão |
| EST-02 | Arquivos com o papel no nome: `pedido.controller.ts`, `pedido.service.ts`, `pedido.repository.ts`, `criar-pedido.dto.ts`. | revisão |
| EST-03 | Nomes de arquivo em kebab-case. | revisão |

## 2. Nomes

| ID | Regra | Verificação |
|---|---|---|
| NOM-01 | Classes, tipos e interfaces em PascalCase, sem prefixo `I`. | ESLint `naming-convention` |
| NOM-07 | Variáveis e funções em camelCase; constantes de módulo podem usar MAIÚSCULAS. | `nomes.variavel-camel-case`, ESLint |

## 3. Camadas

| ID | Regra | Verificação |
|---|---|---|
| CTR-01 | Controllers recebem a requisição, chamam um service e devolvem a resposta. Sem acesso a banco. | `controller.sem-regra-de-negocio` |
| CTR-02 | Entrada validada por DTO (class-validator, zod), não por `if` no controller. | revisão |
| SRV-01 | Services não conhecem HTTP (`req`, `res`, controllers, `HttpException`). | dependency-cruiser, `camadas.sem-http` |

## 4. Rotas

| ID | Regra | Verificação |
|---|---|---|
| ROT-02 | Caminhos em kebab-case e no plural (`/ordens-de-servico`). | `rotas.convencoes` |

## 5. Erros

| ID | Regra | Verificação |
|---|---|---|
| ERR-01 | Erros de negócio são classes próprias (`PedidoJaFaturadoError`), não `Error` genérico. | `erros.excecao-generica` |
| ERR-02 | Erros tratados em um só lugar (exception filter do Nest, middleware de erro do Express). | `erros.try-catch-em-controller` |
| ERR-03 | Resposta ao cliente não expõe `error.message`, stack ou SQL. | `erros.mensagem-exposta` |
| ERR-04 | Nunca `catch` vazio. | `erros.catch-vazio` |

## 6. Geral

| ID | Regra | Verificação |
|---|---|---|
| GER-01 | Sem `console.log` ou `debugger` no código final. | `geral.depuracao-esquecida`, ESLint `no-console` |
| GER-02 | `process.env` só no módulo de configuração, com validação na inicialização. | `geral.env-fora-de-config` |
| GER-03 | Sem `any` explícito. | ESLint (`strictTypeChecked`) |

## Fontes

- TypeScript Handbook, opção `strict`: https://www.typescriptlang.org/tsconfig#strict
- typescript-eslint, configurações compartilhadas: https://typescript-eslint.io/users/configs
- NestJS, Exception filters: https://docs.nestjs.com/exception-filters
- Express, tratamento de erros: https://expressjs.com/en/guide/error-handling.html
