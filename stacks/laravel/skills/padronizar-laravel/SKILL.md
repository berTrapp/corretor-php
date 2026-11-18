---
name: padronizar-laravel
description: Use quando o usuário pedir para padronizar, revisar ou corrigir um projeto Laravel segundo o padrão global (estrutura de pastas, nomes, rotas, camadas), usando o padrão em stacks/laravel/docs/PADRAO.md e as ferramentas do pacote corretor-php.
---

# Padronizar projeto Laravel

O padrão está em `vendor/bertrapp/corretor-php/stacks/laravel/docs/PADRAO.md` (no repositório do pacote, `stacks/laravel/docs/PADRAO.md`). Cada regra tem um identificador (EST-, NOM-, CTR-, REQ-, SRV-, MOD-, ASY-, API-, ROT-, ERR-, BD-, SEG-, TST-, EST-STY-). Use esses identificadores ao reportar.

Não há correção sem teste: o projeto precisa ter testes rodando antes de qualquer mudança estrutural. Se não houver, pare e avise o usuário.

## Passo 1: diagnóstico

1. Confirme a raiz do projeto e a versão do Laravel (`composer.json`).
2. Verifique se as configurações do padrão existem (`pint.json`, `phpstan.neon`, `deptrac.yaml`, `rector.php`, `.editorconfig`). Se faltarem, rode `vendor/bin/corretor instalar` e confirme com o usuário antes de sobrescrever qualquer arquivo.
3. Rode a bateria, na ordem:

   ```bash
   vendor/bin/corretor analisar . --formato=json --saida=padrao-relatorio.json
   vendor/bin/pint --test
   vendor/bin/phpstan analyse --no-progress
   vendor/bin/deptrac analyse
   php artisan test
   ```

4. Leia o relatório. Ele segue `schemas/relatorio.schema.json` do pacote: um objeto com `versao` (hoje 1), `stacks`, `desligadas` (regra → motivo, vindas do `.padrao.yaml`) e `violacoes`, cada uma com `regra`, `arquivo` (relativo à raiz), `linha`, `mensagem`, `severidade` (`erro` ou `aviso`) e `stack`. Se `versao` for diferente de 1, avise o usuário antes de interpretar.
5. Monte uma tabela com a contagem por identificador de regra. Separe o que é erro do que é aviso. Liste as regras desligadas e seus motivos.

O código de saída do `corretor analisar` é 1 só quando há violação de severidade `erro`; avisos não bloqueiam.

## Passo 2: plano

Antes de editar, apresente ao usuário:

- o que será corrigido automaticamente (formatação com Pint, refatorações seguras com Rector);
- o que exige mudança estrutural (mover lógica de controller para service, criar Form Request, renomear rota);
- o que exige decisão humana (nome de domínio, tratamento de erro, mudança de comportamento).

Espere aprovação para o segundo e o terceiro grupos.

## Passo 3: correção

Corrija uma regra por vez, com commits pequenos:

- **Formatação:** `vendor/bin/pint`. Sem mudança de comportamento.
- **Refatoração mecânica:** `vendor/bin/rector --dry-run`, revise, depois aplique.
- **Estrutura:** mova a lógica de controller para service preservando a assinatura pública, a resposta HTTP e os nomes de rota. Cada movimento é seguido de `php artisan test`.
- **Rotas:** adicione `->name('recurso.acao')` sem alterar a URI. Não renomeie URIs já usadas por clientes ou integrações sem aprovação.
- **Nomes de classe ou variável:** renomeie apenas dentro de escopo local. Renomear classe pública ou método de API exige aprovação.

## Passo 4: validação

Repita a bateria do passo 1. Compare a contagem inicial com a final. O resultado esperado é:

- `pint --test`, `phpstan`, `deptrac` e `php artisan test` passando;
- `corretor analisar` sem erros (código de saída 0); avisos restantes listados no resumo.

## Passo 5: resumo

Entregue ao usuário:

- quantas ocorrências por identificador foram resolvidas;
- o que ficou pendente e por quê;
- decisões que dependem de revisão humana;
- risco de cada mudança estrutural (baixo, médio, alto), com a justificativa.

## Limites

- Nunca apague código sem aprovação explícita.
- Nunca mude comportamento para satisfazer uma regra. Se a regra conflitar com o comportamento, registre o conflito e pergunte.
- Repositórios não são criados por padrão (REP-01).
- Se um falso positivo aparecer, registre no resumo e não force a correção. Para silenciá-lo, proponha ao usuário uma entrada no `.padrao.yaml`, nunca sem a aprovação dele:
  - pasta que não é código do projeto (cópias de terceiros, código gerado): `ignorar: [overrides/]`;
  - regra que não vale para o projeto: `desligar: [{regra: ..., motivo: "..."}]`. O motivo é obrigatório; sem ele a regra continua ligada e o relatório acusa `config.excecao-sem-motivo`;
  - sigla curta legítima do domínio: `nomes-curtos-permitidos: [nf]`.
- `declare(strict_types=1)` não faz parte do preset do Pint: aplicá-lo de uma vez muda o comportamento de conversão de tipos. Se o usuário quiser, aplique por pasta, com testes, em commits separados.
