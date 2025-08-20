#!/usr/bin/env node

const axios = require('axios');
const chalk = require('chalk');

// Configuração base
const BASE_URL = process.env.WP_URL || 'https://wordpress.local';
const API_BASE = `${BASE_URL}/wp-json/fuerza-theme/v1`;

// Configuração do axios para desenvolvimento
const axiosConfig = {
  timeout: 10000,
  httpsAgent: new (require('https').Agent)({
    rejectUnauthorized: false
  })
};

// Função para testar dados de SEO
async function testSEO() {
  console.log(chalk.blue.bold('\n🔍 Testando Dados de SEO da API Fuerza Theme\n'));
  console.log(chalk.gray(`Base URL: ${API_BASE}\n`));

  try {
    // Testar produtos
    console.log(chalk.yellow('📦 Testando SEO dos Produtos...\n'));
    const produtosResponse = await axios.get(`${API_BASE}/Produtos`, axiosConfig);

    if (produtosResponse.data && produtosResponse.data.produtos) {
      const produto = produtosResponse.data.produtos[0];

      console.log(chalk.green('✅ Produto encontrado:'));
      console.log(chalk.gray(`  ID: ${produto.id}`));
      console.log(chalk.gray(`  Título: ${produto.titulo}`));

      if (produto.seo && Object.keys(produto.seo).length > 0) {
        console.log(chalk.green('\n✅ Dados de SEO encontrados:'));
        Object.keys(produto.seo).forEach(key => {
          console.log(chalk.gray(`  ${key}: ${produto.seo[key]}`));
        });
      } else {
        console.log(chalk.yellow('\n⚠️  Nenhum dado de SEO encontrado'));
      }

      // Verificar SEO nas traduções
      if (produto.translations && Object.keys(produto.translations).length > 0) {
        console.log(chalk.green('\n✅ Verificando SEO nas traduções:'));
        Object.keys(produto.translations).forEach(lang => {
          const translation = produto.translations[lang];
          console.log(chalk.gray(`  ${lang.toUpperCase()}:`));

          if (translation.seo && Object.keys(translation.seo).length > 0) {
            console.log(chalk.gray(`    SEO: ${Object.keys(translation.seo).length} campos`));
            Object.keys(translation.seo).forEach(key => {
              console.log(chalk.gray(`      ${key}: ${translation.seo[key]}`));
            });
          } else {
            console.log(chalk.gray(`    SEO: Nenhum dado`));
          }
        });
      }
    }

    console.log(chalk.gray('\n' + '='.repeat(60) + '\n'));

    // Testar eventos
    console.log(chalk.yellow('🎉 Testando SEO dos Eventos...\n'));
    const eventosResponse = await axios.get(`${API_BASE}/eventos`, axiosConfig);

    if (eventosResponse.data && eventosResponse.data.eventos) {
      const evento = eventosResponse.data.eventos[0];

      console.log(chalk.green('✅ Evento encontrado:'));
      console.log(chalk.gray(`  ID: ${evento.id}`));
      console.log(chalk.gray(`  Título: ${evento.titulo}`));

      if (evento.seo && Object.keys(evento.seo).length > 0) {
        console.log(chalk.green('\n✅ Dados de SEO encontrados:'));
        Object.keys(evento.seo).forEach(key => {
          console.log(chalk.gray(`  ${key}: ${evento.seo[key]}`));
        });
      } else {
        console.log(chalk.yellow('\n⚠️  Nenhum dado de SEO encontrado'));
      }

      // Verificar SEO nas traduções
      if (evento.translations && Object.keys(evento.translations).length > 0) {
        console.log(chalk.green('\n✅ Verificando SEO nas traduções:'));
        Object.keys(evento.translations).forEach(lang => {
          const translation = evento.translations[lang];
          console.log(chalk.gray(`  ${lang.toUpperCase()}:`));

          if (translation.seo && Object.keys(translation.seo).length > 0) {
            console.log(chalk.gray(`    SEO: ${Object.keys(translation.seo).length} campos`));
            Object.keys(translation.seo).forEach(key => {
              console.log(chalk.gray(`      ${key}: ${translation.seo[key]}`));
            });
          } else {
            console.log(chalk.gray(`    SEO: Nenhum dado`));
          }
        });
      }
    }

    console.log(chalk.gray('\n' + '='.repeat(60) + '\n'));

    // Testar produto específico
    console.log(chalk.yellow('🎯 Testando Produto Específico...\n'));
    try {
      const produtoEspecificoResponse = await axios.get(`${API_BASE}/Produtos/87`, axiosConfig);

      if (produtoEspecificoResponse.data) {
        const produto = produtoEspecificoResponse.data;

        console.log(chalk.green('✅ Produto específico encontrado:'));
        console.log(chalk.gray(`  ID: ${produto.id}`));
        console.log(chalk.gray(`  Título: ${produto.titulo}`));

        if (produto.seo && Object.keys(produto.seo).length > 0) {
          console.log(chalk.green('\n✅ Dados de SEO completos:'));
          Object.keys(produto.seo).forEach(key => {
            console.log(chalk.gray(`  ${key}: ${produto.seo[key]}`));
          });
        }
      }
    } catch (error) {
      console.log(chalk.yellow('⚠️  Erro ao buscar produto específico:'), error.message);
    }

    console.log(chalk.green.bold('\n🎉 Teste de SEO concluído!\n'));

  } catch (error) {
    console.error(chalk.red.bold('\n💥 Erro ao testar SEO:'), error.message);

    if (error.response) {
      console.log(chalk.gray(`Status: ${error.response.status}`));
      console.log(chalk.gray(`Dados: ${JSON.stringify(error.response.data, null, 2)}`));
    }
  }
}

// Executar teste
if (require.main === module) {
  testSEO()
    .then(() => {
      process.exit(0);
    })
    .catch(error => {
      console.error(chalk.red.bold('\n💥 Erro fatal:'), error.message);
      process.exit(1);
    });
}

module.exports = { testSEO };
