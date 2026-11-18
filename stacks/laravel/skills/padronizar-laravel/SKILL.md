---
name: padronizar-laravel
description: Use quando o usuário pedir para padronizar, revisar ou corrigir um projeto Laravel segundo o padrão global (estrutura de pastas, nomes, rotas, camadas), usando o padrão em stacks/laravel/docs/PADRAO.md e as ferramentas do pacote corretor-php.
---

# Padronizar projeto Laravel

Não há correção sem teste: o projeto precisa ter testes rodando antes de qualquer mudança estrutural. Se não houver, pare e avise o usuário.

## Passo 0: preparação

Só leitura. Não instale nem altere nada neste passo.

### Regras do projeto

Leia `AGENTS.md` e `CLAUDE.md` da raiz, se existirem. Em caso de conflito com este skill ou com o padrão, valem as regras do projeto, inclusive restrições de banco de dados, de testes e de comandos proibidos.

### Ambiente de execução

Descubra onde o projeto roda antes do primeiro comando: `AGENTS.md`, `CLAUDE.md`, README, `Makefile`, `compose.yaml`, `docker-compose.yml`, `vendor/bin/sail`.

- **Sail:** use `vendor/bin/sail php`, `vendor/bin/sail composer` e `vendor/bin/sail artisan`.
- **Docker Compose:** use `docker compose exec <serviço> ...`, com o serviço da aplicação definido pelo projeto.
- **Sem contêiner:** rode direto no host.

Os comandos deste skill aparecem na forma curta. Aplique sempre o prefixo do ambiente a PHP, Composer, ferramentas de `vendor/bin` e testes. Se o ambiente não estiver claro, pergunte.

### Pacote corretor-php

Verifique se `bertrapp/corretor-php` está no `composer.json` e se `vendor/bin/corretor` existe.

- **Instalado:** use `vendor/bin/corretor`. O padrão está em `vendor/bertrapp/corretor-php/stacks/laravel/docs/PADRAO.md`.
- **Não instalado:** não adicione o pacote ao projeto por conta própria. Procure `corretor` no `PATH`. Se existir, use `corretor analisar <raiz>`; o padrão fica em `$(composer global config home)/vendor/bertrapp/corretor-php/stacks/laravel/docs/PADRAO.md`.
- **Nenhum dos dois:** pare e pergunte ao usuário qual opção prefere:
  - instalação global no host, que não altera o projeto: `composer global require bertrapp/corretor-php`;
  - dependência do projeto: `composer require --dev bertrapp/corretor-php`.

O pacote exige PHP 8.2 ou superior. Em projeto com PHP mais antigo, prefira a instalação global em um host com PHP 8.2 ou superior: a análise é estática e não executa o código do projeto.

### Ferramentas disponíveis

Confira quais existem em `vendor/bin`: `pint`, `phpstan`, `deptrac` e `rector`. Ferramenta ausente não é instalada sem aprovação: registre como "não executada" no resumo.

O Rector é opcional. Use-o só se `rector/rector` e `driftingly/rector-laravel` estiverem no `composer.json`. Sem eles, não crie `rector.php`; o `corretor instalar` já pula esse arquivo.

Se faltarem configurações do padrão (`pint.json`, `phpstan.neon`, `deptrac.yaml`, `.editorconfig`), proponha `corretor instalar` e espere a aprovação. Ele não sobrescreve arquivos existentes (só com `--forcar`, que exige aprovação explícita) e adiciona `padrao-relatorio.json` e `.deptrac.cache` ao `.gitignore`. Se o usuário recusar, não versione esses dois arquivos: proponha incluí-los no `.gitignore` ou remova-os ao terminar.

### Banco de testes

Antes de `php artisan test`, confirme que os testes apontam para um banco isolado:

- leia `DB_CONNECTION` e `DB_DATABASE` em `phpunit.xml` e `.env.testing`;
- aceite só SQLite em memória ou um banco claramente separado do de desenvolvimento e do de produção;
- confira se `bootstrap/cache/config.php` existe: configuração em cache pode fazer os testes ignorarem esses valores.

Se não puder confirmar o isolamento, não rode os testes. Pare e pergunte.

## Passo 1: diagnóstico

1. Confirme a raiz do projeto e a versão do Laravel (`composer.json`).
2. Rode a bateria com as ferramentas disponíveis, na ordem:

   ```bash
   vendor/bin/corretor analisar . --formato=json --saida=padrao-relatorio.json
   vendor/bin/pint --test
   vendor/bin/phpstan analyse --no-progress
   vendor/bin/deptrac analyse
   php artisan test
   ```

3. Guarde o resultado inicial de cada ferramenta. Ele é a linha de base do passo 4.
4. Defina o modo do Pint:
   - **projeto inteiro:** `pint --test` passou e o projeto não tem regra própria de formatação;
   - **só arquivos alterados:** `pint --test` falhou, ou o projeto tem regra própria. Formatar tudo esconderia a mudança real no diff.
5. Leia o relatório. Ele segue `schemas/relatorio.schema.json` do pacote: um objeto com `versao` (hoje 1), `stacks`, `violacoes` e, opcionalmente, `desligadas` (regra → motivo, vindas do `.padrao.yaml`). Cada violação tem `regra`, `arquivo` (relativo à raiz), `linha`, `mensagem`, `severidade` (`erro` ou `aviso`) e `stack`. Se `versao` for diferente de 1, avise o usuário antes de interpretar.
6. Monte uma tabela com a contagem pelo campo `regra`, acrescentando o identificador do padrão conforme a correspondência abaixo. Separe erros de avisos. Liste as regras desligadas e seus motivos.

O código de saída do `corretor analisar` é 1 só quando há violação de severidade `erro`; avisos não bloqueiam.

### Regras do relatório e identificadores do padrão

O relatório usa o nome da regra. O `PADRAO.md` usa identificadores (EST-, NOM-, CTR-, REQ-, SRV-, REP-, MOD-, ASY-, API-, ROT-, ERR-, BD-, SEG-, TST-, EST-STY-). Identificadores sem regra na tabela são verificados por revisão ou por ferramenta externa: cite-os só quando os encontrar na revisão.

| `regra` no relatório | Identificadores |
|---|---|
| `nomes.sufixo-da-classe` | NOM-01 |
| `nomes.metodo-camel-case` | NOM-06 |
| `nomes.variavel-camel-case` | NOM-07 |
| `nomes.variavel-abreviada` | NOM-07 |
| `controller.sem-regra-de-negocio` | CTR-01, EST-02 |
| `controller.validacao-em-form-request` | CTR-02, REQ-01, EST-02 |
| `controller.limite-metodos` | CTR-03, EST-02 |
| `controller.consulta-eloquent-direta` | CTR-04, EST-02 |
| `rotas.nomeadas` | NOM-11, NOM-12, ROT-02 |
| `rotas.convencoes` | NOM-13, NOM-14, ROT-03 |
| `camadas.sem-http` | SRV-01, MOD-01, EST-02 |
| `erros.excecao-generica` | ERR-01 |
| `erros.try-catch-em-controller` | ERR-02 |
| `erros.handler-central-vazio` | ERR-02 |
| `erros.mensagem-exposta` | ERR-03 |
| `erros.catch-vazio` | ERR-04 |
| `geral.env-fora-de-config` | SEG-01 |
| `geral.depuracao-esquecida` | SEG-06 |

Duas regras não pertencem ao padrão e devem ser resolvidas primeiro: `parse` (arquivo que não pôde ser analisado) e `config.*` (problema no `.padrao.yaml`).

## Passo 2: plano

Antes de editar, apresente ao usuário:

- o que será corrigido automaticamente (formatação com Pint e, se o Rector estiver instalado, refatorações seguras);
- o que exige mudança estrutural (mover lógica de controller para service, criar Form Request, renomear rota);
- o que exige decisão humana (nome de domínio, tratamento de erro, mudança de comportamento).

Espere aprovação para o segundo e o terceiro grupos.

## Passo 3: correção

Corrija uma regra por vez, em mudanças pequenas. Faça commits só se o usuário e as regras do projeto permitirem.

- **Formatação:** no modo "projeto inteiro", `vendor/bin/pint`. No modo "só arquivos alterados", passe os caminhos exatos: `vendor/bin/pint app/Services/PedidoService.php app/Http/Controllers/PedidoController.php`. Nunca rode sem caminhos nesse modo. Sem mudança de comportamento.
- **Refatoração mecânica (só com Rector instalado):** `vendor/bin/rector process <caminhos> --dry-run`, revise, depois aplique nos mesmos caminhos.
- **Estrutura:** mova a lógica de controller para service preservando a assinatura pública, a resposta HTTP e os nomes de rota. Cada movimento é seguido de `php artisan test`.
- **Rotas:** adicione `->name('recurso.acao')` sem alterar a URI. Não renomeie URIs já usadas por clientes ou integrações sem aprovação.
- **Nomes de classe ou variável:** renomeie apenas dentro de escopo local. Renomear classe pública ou método de API exige aprovação.

## Passo 4: validação

Repita a bateria do passo 1 com as ferramentas disponíveis e compare com a linha de base. O resultado esperado é:

- **Pint:** no modo "projeto inteiro", `vendor/bin/pint --test` passando. No modo "só arquivos alterados", `vendor/bin/pint --test <arquivos alterados>` passando, sem exigir o projeto inteiro;
- **`phpstan`, `deptrac` e `php artisan test`:** passando, ou, se já falhavam na linha de base, sem nenhuma falha nova;
- **`corretor analisar`:** sem erros (código de saída 0) ou, em correção parcial aprovada pelo usuário, com menos erros que na linha de base e nenhum novo. Avisos restantes listados no resumo.

## Passo 5: resumo

Entregue ao usuário:

- quantas ocorrências por regra e identificador foram resolvidas;
- ferramentas não executadas e o motivo;
- o que ficou pendente e por quê;
- decisões que dependem de revisão humana;
- risco de cada mudança estrutural (baixo, médio, alto), com a justificativa.

## Limites

- As regras do projeto (`AGENTS.md`, `CLAUDE.md`) têm prioridade sobre este skill.
- Nunca apague código sem aprovação explícita.
- Nunca mude comportamento para satisfazer uma regra. Se a regra conflitar com o comportamento, registre o conflito e pergunte.
- Repositórios não são criados por padrão (REP-01).
- Se um falso positivo aparecer, registre no resumo e não force a correção. Para silenciá-lo, proponha ao usuário uma entrada no `.padrao.yaml`, nunca sem a aprovação dele:
  - pasta que não é código do projeto (cópias de terceiros, código gerado): `ignorar: [overrides/]`;
  - regra que não vale para o projeto: `desligar: [{regra: ..., motivo: "..."}]`. O motivo é obrigatório; sem ele a regra continua ligada e o relatório acusa `config.excecao-sem-motivo`;
  - sigla curta legítima do domínio: `nomes-curtos-permitidos: [nf]`.
- `declare(strict_types=1)` não faz parte do preset do Pint: aplicá-lo de uma vez muda o comportamento de conversão de tipos. Se o usuário quiser, aplique por pasta, com testes, em commits separados.
