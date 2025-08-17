#!/usr/bin/env node

const axios = require('axios');
const chalk = require('chalk');
const ora = require('ora');

// Configuração base
const BASE_URL = process.env.WP_URL || 'https://wordpress.local';
const API_BASE = `${BASE_URL}/wp-json/fuerza-theme/v1`;

// Rotas para testar
const routes = [
  { path: '/ping', description: 'API Health Check' },
  { path: '/eventos', description: 'Lista Eventos' },
  { path: '/Produtos', description: 'Lista Produtos' },
  { path: '/Teams', description: 'Lista Teams' }
];

async function testRoute(route) {
  try {
    const response = await axios.get(`${API_BASE}${route.path}`, {
      timeout: 10000,
      validateStatus: () => true // Aceita qualquer status
    });
    
    return {
      success: response.status >= 200 && response.status < 300,
      status: response.status,
      responseTime: response.headers['x-response-time'] || 'N/A',
      data: response.data
    };
  } catch (error) {
    return {
      success: false,
      status: 'ERROR',
      error: error.message,
      responseTime: 'N/A'
    };
  }
}

async function runTests() {
  console.log(chalk.blue.bold('\n🧪 Testando Rotas da API Fuerza Theme\n'));
  console.log(chalk.gray(`Base URL: ${API_BASE}\n`));
  
  const results = [];
  
  for (const route of routes) {
    const spinner = ora(`Testando ${route.description}...`).start();
    
    const result = await testRoute(route);
    results.push({ route, result });
    
    if (result.success) {
      spinner.succeed(chalk.green(`✅ ${route.description} - ${result.status} (${result.responseTime}ms)`));
    } else {
      spinner.fail(chalk.red(`❌ ${route.description} - ${result.status} ${result.error ? `- ${result.error}` : ''}`));
    }
  }
  
  // Resumo
  console.log(chalk.blue.bold('\n📊 Resumo dos Testes:\n'));
  
  const successful = results.filter(r => r.result.success).length;
  const total = results.length;
  
  console.log(`${chalk.green('✅ Sucessos:')} ${successful}/${total}`);
  console.log(`${chalk.red('❌ Falhas:')} ${total - successful}/${total}`);
  
  if (successful === total) {
    console.log(chalk.green.bold('\n🎉 Todas as rotas estão funcionando corretamente!\n'));
  } else {
    console.log(chalk.yellow.bold('\n⚠️  Algumas rotas precisam de atenção.\n'));
    
    // Mostrar falhas detalhadas
    const failures = results.filter(r => !r.result.success);
    if (failures.length > 0) {
      console.log(chalk.red.bold('🔍 Detalhes das Falhas:\n'));
      failures.forEach(({ route, result }) => {
        console.log(chalk.red(`• ${route.path} - ${result.status}`));
        if (result.error) {
          console.log(chalk.gray(`  Erro: ${result.error}`));
        }
        if (result.data && result.data.message) {
          console.log(chalk.gray(`  Mensagem: ${result.data.message}`));
        }
        console.log('');
      });
    }
  }
  
  return successful === total;
}

// Executar testes
if (require.main === module) {
  runTests()
    .then(success => {
      process.exit(success ? 0 : 1);
    })
    .catch(error => {
      console.error(chalk.red.bold('\n💥 Erro ao executar testes:'), error.message);
      process.exit(1);
    });
}

module.exports = { runTests, testRoute };
