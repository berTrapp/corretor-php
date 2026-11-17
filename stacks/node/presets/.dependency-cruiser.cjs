/**
 * Camadas e dependências permitidas (equivalente ao Deptrac do Laravel).
 * Ajuste os caminhos se o projeto não usar src/.
 * Rode: npx depcruise src --config .dependency-cruiser.cjs
 */
module.exports = {
  forbidden: [
    {
      name: 'sem-ciclos',
      severity: 'error',
      from: {},
      to: { circular: true },
    },
    {
      name: 'service-nao-depende-de-controller',
      comment: 'Services não conhecem a camada HTTP.',
      severity: 'error',
      from: { path: '\\.service\\.ts$|/services/' },
      to: { path: '\\.controller\\.ts$|/controllers/' },
    },
    {
      name: 'dominio-nao-depende-de-http',
      severity: 'error',
      from: { path: '/(domain|entities)/' },
      to: { path: '/(controllers|http|routes)/|\\.controller\\.ts$' },
    },
    {
      name: 'sem-dependencia-de-dev-em-producao',
      severity: 'error',
      from: { path: '^src', pathNot: '\\.(spec|test)\\.ts$' },
      to: { dependencyTypes: ['npm-dev'] },
    },
  ],
  options: {
    doNotFollow: { path: 'node_modules' },
    tsConfig: { fileName: 'tsconfig.json' },
  },
};
