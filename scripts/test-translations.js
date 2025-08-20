#!/usr/bin/env node

const axios = require('axios');
const chalk = require('chalk');
const ora = require('ora');

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

// Função para testar traduções
async function testTranslations() {
  console.log(chalk.blue.bold('\n🌍 Testando Traduções da API Fuerza Theme\n'));
  console.log(chalk.gray(`Base URL: ${API_BASE}\n`));

  try {
    // Testar produtos
    console.log(chalk.yellow('📦 Testando Produtos...\n'));
    const produtosResponse = await axios.get(`${API_BASE}/Produtos`, axiosConfig);

    if (produtosResponse.data && produtosResponse.data.produtos) {
      const produto = produtosResponse.data.produtos[0];

      console.log(chalk.green('✅ Produto encontrado:'));
      console.log(chalk.gray(`  ID: ${produto.id}`));
      console.log(chalk.gray(`  Título: ${produto.titulo}`));
      console.log(chalk.gray(`  Idioma: ${produto.language}`));
      console.log(chalk.gray(`  É idioma padrão: ${produto.is_default_language}`));

      if (produto.translations && Object.keys(produto.translations).length > 0) {
        console.log(chalk.green('\n✅ Traduções encontradas:'));
        Object.keys(produto.translations).forEach(lang => {
          const translation = produto.translations[lang];
          console.log(chalk.gray(`  ${lang.toUpperCase()}:`));
          console.log(chalk.gray(`    Título: ${translation.titulo}`));
          console.log(chalk.gray(`    Slug: ${translation.slug}`));
          console.log(chalk.gray(`    Status: ${translation.status}`));

          // Verificar dados de SEO
          if (translation.seo && Object.keys(translation.seo).length > 0) {
            console.log(chalk.gray(`    SEO: ${Object.keys(translation.seo).length} campos`));
          } else {
            console.log(chalk.gray(`    SEO: Nenhum dado configurado`));
          }
        });
      } else {
        console.log(chalk.yellow('\n⚠️  Nenhuma tradução encontrada'));
      }

      if (produto.acf && produto.acf.translations) {
        console.log(chalk.green('\n✅ Traduções ACF encontradas:'));
        Object.keys(produto.acf.translations).forEach(lang => {
          console.log(chalk.gray(`  ${lang.toUpperCase()}: ${Object.keys(produto.acf.translations[lang]).length} campos`));
        });
      } else {
        console.log(chalk.yellow('\n⚠️  Nenhuma tradução ACF encontrada'));
      }

      console.log(chalk.gray('\n📊 Informações de idioma:'));
      if (produtosResponse.data.language_info) {
        console.log(chalk.gray(`  Idioma atual: ${produtosResponse.data.language_info.current_language}`));
        console.log(chalk.gray(`  Idiomas disponíveis: ${produtosResponse.data.language_info.available_languages.join(', ')}`));
      }
    }

    console.log(chalk.gray('\n' + '='.repeat(60) + '\n'));

    // Testar eventos
    console.log(chalk.yellow('🎉 Testando Eventos...\n'));
    const eventosResponse = await axios.get(`${API_BASE}/eventos`, axiosConfig);

    if (eventosResponse.data && eventosResponse.data.eventos) {
      const evento = eventosResponse.data.eventos[0];

      console.log(chalk.green('✅ Evento encontrado:'));
      console.log(chalk.gray(`  ID: ${evento.id}`));
      console.log(chalk.gray(`  Título: ${evento.titulo}`));
      console.log(chalk.gray(`  Idioma: ${evento.language}`));
      console.log(chalk.gray(`  É idioma padrão: ${evento.is_default_language}`));

      if (evento.translations && Object.keys(evento.translations).length > 0) {
        console.log(chalk.green('\n✅ Traduções encontradas:'));
        Object.keys(evento.translations).forEach(lang => {
          const translation = evento.translations[lang];
          console.log(chalk.gray(`  ${lang.toUpperCase()}:`));
          console.log(chalk.gray(`    Título: ${translation.titulo}`));
          console.log(chalk.gray(`    Slug: ${translation.slug}`));
          console.log(chalk.gray(`    Status: ${translation.status}`));

          // Verificar dados de SEO
          if (translation.seo && Object.keys(translation.seo).length > 0) {
            console.log(chalk.gray(`    SEO: ${Object.keys(translation.seo).length} campos`));
          } else {
            console.log(chalk.gray(`    SEO: Nenhum dado configurado`));
          }
        });
      } else {
        console.log(chalk.yellow('\n⚠️  Nenhuma tradução encontrada'));
      }

      if (evento.acf && evento.acf.translations) {
        console.log(chalk.green('\n✅ Traduções ACF encontradas:'));
        Object.keys(evento.acf.translations).forEach(lang => {
          console.log(chalk.gray(`  ${lang.toUpperCase()}: ${Object.keys(evento.acf.translations[lang]).length} campos`));
        });
      } else {
        console.log(chalk.yellow('\n⚠️  Nenhuma tradução ACF encontrada'));
      }

      console.log(chalk.gray('\n📊 Informações de idioma:'));
      if (eventosResponse.data.language_info) {
        console.log(chalk.gray(`  Idioma atual: ${eventosResponse.data.language_info.current_language}`));
        console.log(chalk.gray(`  Idiomas disponíveis: ${eventosResponse.data.language_info.available_languages.join(', ')}`));
      }
    }

    console.log(chalk.gray('\n' + '='.repeat(60) + '\n'));

    // Testar endpoint de idiomas
    console.log(chalk.yellow('🌐 Testando Endpoint de Idiomas...\n'));
    try {
      const languagesResponse = await axios.get(`${API_BASE}/languages`, axiosConfig);

      if (languagesResponse.data) {
        console.log(chalk.green('✅ Informações de idiomas:'));
        console.log(chalk.gray(`  Plugin ativo: ${languagesResponse.data.plugin}`));
        console.log(chalk.gray(`  Idioma padrão: ${languagesResponse.data.default}`));
        console.log(chalk.gray(`  Idioma atual: ${languagesResponse.data.current}`));

        if (languagesResponse.data.languages) {
          console.log(chalk.gray(`  Idiomas disponíveis: ${Object.keys(languagesResponse.data.languages).join(', ')}`));
        }
      }
    } catch (error) {
      console.log(chalk.yellow('⚠️  Endpoint de idiomas não disponível ou erro:'), error.message);
    }

    console.log(chalk.green.bold('\n🎉 Teste de traduções concluído!\n'));

  } catch (error) {
    console.error(chalk.red.bold('\n💥 Erro ao testar traduções:'), error.message);

    if (error.response) {
      console.log(chalk.gray(`Status: ${error.response.status}`));
      console.log(chalk.gray(`Dados: ${JSON.stringify(error.response.data, null, 2)}`));
    }
  }
}

// Executar teste
if (require.main === module) {
  testTranslations()
    .then(() => {
      process.exit(0);
    })
    .catch(error => {
      console.error(chalk.red.bold('\n💥 Erro fatal:'), error.message);
      process.exit(1);
    });
}

module.exports = { testTranslations };
