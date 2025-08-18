#!/usr/bin/env node

const chalk = require('chalk');
const boxen = require('boxen');

const helpText = `
${chalk.bold.blue('🚀 Fuerza Theme API - Comandos NPM Disponíveis')}

${chalk.yellow.bold('📦 DESENVOLVIMENTO:')}
  ${chalk.green('npm start')}           - Inicia modo de desenvolvimento
  ${chalk.green('npm run dev')}         - Modo desenvolvimento com watch
  ${chalk.green('npm run build')}       - Build para produção
  ${chalk.green('npm run watch')}       - Watch files para mudanças

${chalk.yellow.bold('🎨 BUILD & ASSETS:')}
  ${chalk.green('npm run build:css')}   - Compila SCSS para CSS
  ${chalk.green('npm run build:js')}    - Minifica JavaScript
  ${chalk.green('npm run build:assets')} - Otimiza imagens e assets

${chalk.yellow.bold('🧪 TESTES:')}
  ${chalk.green('npm test')}            - Executa todos os testes
  ${chalk.green('npm run test:api')}    - Testa rotas da API
  ${chalk.green('npm run test:php')}    - Testa código PHP

${chalk.yellow.bold('🔍 LINTING:')}
  ${chalk.green('npm run lint')}        - Executa todos os linters
  ${chalk.green('npm run lint:css')}    - Lint CSS/SCSS
  ${chalk.green('npm run lint:js')}     - Lint JavaScript
  ${chalk.green('npm run lint:php')}    - Lint PHP

${chalk.yellow.bold('🌐 API:')}
  ${chalk.green('npm run api:test')}    - Testa todas as rotas da API
  ${chalk.green('npm run api:routes')}  - Lista todas as rotas

${chalk.yellow.bold('🗄️  BANCO DE DADOS:')}
  ${chalk.green('npm run db:migrate')}  - Executa migrações
  ${chalk.green('npm run db:seed')}     - Popula banco com dados de teste
  ${chalk.green('npm run db:backup')}   - Backup do banco de dados
  ${chalk.green('npm run db:restore')}  - Restaura backup do banco

${chalk.yellow.bold('📝 CUSTOM POST TYPES:')}
  ${chalk.green('npm run cpt:create')}  - Cria novo CPT interativo
  ${chalk.green('npm run cpt:remove')}  - Remove CPT existente
  ${chalk.green('npm run cpt:list')}    - Lista todos os CPTs

${chalk.yellow.bold('🎯 TEMA:')}
  ${chalk.green('npm run theme:install')} - Instalação completa do tema
  ${chalk.green('npm run theme:update')}  - Atualiza tema existente
  ${chalk.green('npm run theme:reset')}   - Reset completo do tema

${chalk.yellow.bold('⚙️  SISTEMA:')}
  ${chalk.green('npm run system:check')} - Verifica status do sistema
  ${chalk.green('npm run system:info')}  - Informações do ambiente

${chalk.yellow.bold('🚀 DEPLOY:')}
  ${chalk.green('npm run deploy')}      - Deploy para produção
  ${chalk.green('npm run release')}     - Cria nova release

${chalk.yellow.bold('📚 DOCUMENTAÇÃO:')}
  ${chalk.green('npm run help')}        - Mostra esta ajuda

${chalk.cyan('💡 Dica: Execute qualquer comando com --help para mais detalhes')}
`;

console.log(boxen(helpText, {
  padding: 1,
  margin: 1,
  borderStyle: 'double',
  borderColor: 'blue'
}));

console.log(chalk.gray('\n📖 Para mais informações, visite: https://github.com/your-org/fuerza-theme-api\n'));
