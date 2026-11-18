# Changelog

Segue [versionamento semântico](https://semver.org/lang/pt-BR/) aplicado ao padrão:

- **maior:** regra nova ou mudança que passa a gerar **erro** em código que antes passava (quebra o build);
- **menor:** regra nova de severidade **aviso**, stack nova, opção nova;
- **correção:** menos falsos positivos, mensagens, documentação.

Toda entrada de regra diz o identificador e o motivo.

## [Não publicado]

### Mudado
- `corretor instalar` só copia `rector.php` quando `rector/rector` e `driftingly/rector-laravel` estão no `composer.json`; o Rector passa a ser opcional e sai das dependências sugeridas.
- `corretor instalar` adiciona `padrao-relatorio.json` e `.deptrac.cache` ao `.gitignore`, sem duplicar entradas.
- Skill `padronizar-laravel` adaptado a projetos legados: pacote ausente, Pint só nos arquivos alterados, Rector opcional, prioridade das regras do projeto, Docker/Sail, banco de testes isolado e correspondência entre regras do relatório e identificadores do padrão.

## [0.2.0] - 2026-11-18

### Adicionado
- `.padrao.yaml`: `ignorar:` com pastas ou globs que ficam fora do relatório (ex.: `overrides/` com código de terceiros).
- Skill `padronizar-node`; o skill `padronizar-laravel` lê o novo formato do relatório e orienta o uso do `.padrao.yaml`.
- `camadas.sem-http` (node, aviso): exceção HTTP lançada em service.
- Stack **node** (Node/TypeScript), com regras em YAML executadas pelo ast-grep: `geral.depuracao-esquecida`, `geral.env-fora-de-config`, `erros.catch-vazio`, `erros.excecao-generica`, `erros.mensagem-exposta`, `erros.try-catch-em-controller`, `controller.sem-regra-de-negocio`, `rotas.convencoes`, `nomes.variavel-camel-case`. Os IDs são os mesmos do Laravel quando a intenção é a mesma.
- Presets node: ESLint (typescript-eslint estrito), `tsconfig.padrao.json`, Prettier, dependency-cruiser, EditorConfig.
- `.padrao.yaml`: escolha de stack, regras desligadas **com motivo obrigatório** (sem motivo vira `config.excecao-sem-motivo`) e `nomes-curtos-permitidos`.
- `schemas/relatorio.schema.json` (versão 1). O JSON agora é um objeto com `versao`, `stacks`, `desligadas` e `violacoes`; cada violação tem `stack`.
- Templates de GitHub Actions (`templates/ci/`) e de pre-commit (`templates/hooks/`) por stack.
- Laravel: `erros.excecao-generica`, `erros.try-catch-em-controller`, `erros.handler-central-vazio`, `erros.mensagem-exposta`, `erros.catch-vazio` (ERR-01 a ERR-04), antes só revisão.

### Mudado
- Pint: `declare_strict_types` saiu do preset (EST-STY-02 passa a ser revisão). Motivo: aplicado de uma vez em projeto existente, muda a conversão de tipos em tempo de execução e pode quebrar código sem aviso. Ative no `pint.json` quando o projeto já estiver todo com `strict_types`.
- Estrutura: núcleo em `src/`, cada stack em `stacks/<nome>/` (docs, presets, regras, skills).
- Caminhos no relatório são relativos à raiz analisada.
- Código de saída 1 só quando há violação de severidade **erro**; avisos não bloqueiam.
- `--stack` em `analisar` e `instalar`; sem ele a stack é detectada (`artisan`, `package.json`).
- Deptrac sugerido como `deptrac/deptrac` (nome do pacote a partir da versão 2).

### Corrigido
- `nomes.sufixo-da-classe` não exige mais `Job` nem `Listener` (NOM-01a). Motivo: a documentação do Laravel nomeia Jobs e Listeners pela ação, sem sufixo; a exigência gerava avisos em código que segue o framework (169 dos 330 avisos no akaunting). Classes que já têm o sufixo continuam aceitas. Services mantêm o sufixo por decisão da casa.
- Presets validados com as versões atuais em projetos públicos (koel, nestjs-boilerplate):
  - `phpstan.neon`: removido `checkMissingIterableValueType`, que não existe no PHPStan 2 e impedia a execução.
  - `deptrac.yaml`: collector `className` (removido no Deptrac 4) trocado por `directory`.
  - `rector.php`: regras do rector-laravel ativadas com `withComposerBased(laravel: true)`.
  - `eslint.config.mjs`: aceita classes vazias com decorator (`@Module` do NestJS) e propriedades de classe em MAIÚSCULAS (classes de variáveis de ambiente).
- `nomes.variavel-camel-case` (Laravel) ignora superglobais (`$_ENV`, `$_SERVER`...).
- `geral.depuracao-esquecida` (node) ignora `bin/`.
- `nomes.variavel-abreviada`: aceita `id`, `uf`, `ip`, `db`, `os`, e cada variável é reportada uma vez por arquivo, não a cada uso.

## [0.1.0]

- Padrão Laravel inicial: regras de nomes, controllers, rotas, camadas e gerais; presets Pint, PHPStan/Larastan, Deptrac, Rector; skill `padronizar-laravel`.
