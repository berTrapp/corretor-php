---
name: padronizar-node
description: Use quando o usuário pedir para padronizar, revisar ou corrigir um projeto Node/TypeScript (NestJS, Express, Fastify, Next) segundo o padrão global, usando o padrão em stacks/node/docs/PADRAO.md e as ferramentas do pacote corretor-php.
---

# Padronizar projeto Node/TypeScript

O padrão está em `stacks/node/docs/PADRAO.md` do pacote `bertrapp/corretor-php` (instalado com `composer global require bertrapp/corretor-php`). Os identificadores (EST-, NOM-, CTR-, SRV-, ROT-, ERR-, GER-) e os IDs de regra (`erros.catch-vazio` etc.) são os mesmos do padrão Laravel quando a intenção é a mesma. Use-os ao reportar.

Não há correção sem teste: o projeto precisa ter testes rodando (`npm test`) antes de qualquer mudança estrutural. Se não houver, pare e avise o usuário.

## Passo 1: diagnóstico

1. Confirme a raiz, o framework (`package.json`) e o gerenciador de pacotes (npm, pnpm, yarn).
2. Verifique se as configurações do padrão existem (`eslint.config.mjs`, `tsconfig.padrao.json`, `.prettierrc.json`, `.dependency-cruiser.cjs`, `.editorconfig`). Se faltarem, rode `corretor instalar --stack=node` e confirme com o usuário antes de sobrescrever qualquer arquivo. O projeto pode já ter configuração própria de ESLint ou Prettier: compare antes e pergunte qual vale.
3. O `tsconfig.json` do projeto deve ter `"extends": "./tsconfig.padrao.json"`. Se não tiver, proponha a mudança: ela costuma revelar muitos erros de tipo (`noUncheckedIndexedAccess`, `exactOptionalPropertyTypes`).
4. Rode a bateria, na ordem:

   ```bash
   npm i -D @ast-grep/cli          # se ainda não estiver instalado
   corretor analisar . --stack=node --formato=json --saida=padrao-relatorio.json
   npx prettier --check .
   npx eslint .
   npx tsc --noEmit
   npx depcruise src --config .dependency-cruiser.cjs
   npm test
   ```

5. Leia o relatório (formato em `schemas/relatorio.schema.json`: objeto com `versao`, `stacks`, `desligadas` e `violacoes`, cada uma com `regra`, `arquivo`, `linha`, `mensagem`, `severidade` e `stack`). Monte uma tabela por regra, separando erro de aviso, e outra com os erros de ESLint e tsc por regra.

## Passo 2: plano

Antes de editar, apresente ao usuário:

- o que é automático e sem mudança de comportamento (`prettier --write`, `eslint --fix`);
- o que exige mudança estrutural (acesso a banco do controller para o service, `HttpException` do service para um erro de domínio convertido no exception filter, `process.env` para o módulo de configuração);
- o que exige decisão humana (nomes de domínio, contratos de API, mudança de tipos públicos).

Espere aprovação para o segundo e o terceiro grupos.

## Passo 3: correção

Uma regra por vez, com commits pequenos e `npm test` depois de cada passo:

- **Formatação:** `npx prettier --write .`, em commit próprio.
- **Lint automático:** `npx eslint . --fix`, revise o diff.
- **Erros (ERR-01 a ERR-04):** crie classes de erro de domínio (`PedidoJaFaturadoError extends Error`) em `errors/`, trate-as em um exception filter (NestJS) ou middleware de erro (Express) e tire os `try/catch` dos controllers. A resposta ao cliente nunca leva `error.message`.
- **Configuração:** concentre `process.env` em `config/`, com validação na inicialização (zod, class-validator, `@nestjs/config`).
- **Rotas:** caminhos em kebab-case. Não mude caminhos já usados por clientes sem aprovação; prefira manter o antigo como alias.
- **Tipos estritos:** corrija por pasta. Não use `any`, `as` ou `!` para calar o compilador; se for inevitável, deixe comentário com o motivo.

## Passo 4: validação

Repita a bateria do passo 1 e compare as contagens. Esperado: Prettier, ESLint, tsc, dependency-cruiser e testes passando; `corretor analisar` com código de saída 0 (sem erros), avisos listados no resumo.

## Passo 5: resumo

Entregue: ocorrências resolvidas por regra, o que ficou pendente e por quê, decisões que dependem de revisão humana e o risco de cada mudança estrutural (baixo, médio, alto), com justificativa.

## Limites

- Nunca apague código sem aprovação explícita.
- Nunca mude comportamento para satisfazer uma regra. Se houver conflito, registre e pergunte.
- Falso positivo: registre no resumo. Para silenciar, proponha ao usuário uma entrada no `.padrao.yaml` (`ignorar:` para pastas que não são do projeto; `desligar:` com `motivo:` obrigatório para regras), nunca sem aprovação.
