#!/usr/bin/env node

const inquirer = require('inquirer');
const fs = require('fs');
const path = require('path');
const chalk = require('chalk');
const ora = require('ora');

async function removeCPT() {
  console.log(chalk.red.bold('\n🗑️  Remover Custom Post Types e Taxonomias\n'));

  // Listar CPTs existentes
  const cptsDir = path.join(__dirname, '../inc/content-types/cpts');
  const taxonomiesDir = path.join(__dirname, '../inc/content-types/taxonomies');
  const apiRoutesDir = path.join(__dirname, '../inc/api/routes');
  const apiHandlersDir = path.join(__dirname, '../inc/api/handlers');
  const apiFormattersDir = path.join(__dirname, '../inc/api/formatters');

  let availableCPTs = [];
  let availableTaxonomies = [];

  // Verificar CPTs existentes
  if (fs.existsSync(cptsDir)) {
    const cptFiles = fs.readdirSync(cptsDir).filter(file => file.endsWith('.php'));
    availableCPTs = cptFiles.map(file => file.replace('.php', ''));
  }

  // Verificar taxonomias existentes
  if (fs.existsSync(taxonomiesDir)) {
    const taxonomyFiles = fs.readdirSync(taxonomiesDir).filter(file => file.endsWith('.php'));
    availableTaxonomies = taxonomyFiles.map(file => file.replace('.php', ''));
  }

  if (availableCPTs.length === 0 && availableTaxonomies.length === 0) {
    console.log(chalk.yellow('⚠️  Nenhum CPT ou taxonomia encontrado para remover.'));
    return;
  }

  const answers = await inquirer.prompt([
    {
      type: 'list',
      name: 'actionType',
      message: 'O que você deseja remover?',
      choices: [
        { name: '🗂️  Custom Post Type (e arquivos relacionados)', value: 'cpt' },
        { name: '🏷️  Taxonomia específica', value: 'taxonomy' },
        { name: '🧹 Limpeza completa (CPT + taxonomias + API)', value: 'complete' }
      ]
    }
  ]);

  let itemsToRemove = [];

  if (answers.actionType === 'cpt') {
    if (availableCPTs.length === 0) {
      console.log(chalk.yellow('⚠️  Nenhum CPT encontrado.'));
      return;
    }

    const cptAnswer = await inquirer.prompt([
      {
        type: 'list',
        name: 'selectedCPT',
        message: 'Qual CPT deseja remover?',
        choices: availableCPTs.map(cpt => ({
          name: `📝 ${cpt}`,
          value: cpt
        }))
      },
      {
        type: 'confirm',
        name: 'removeRelated',
        message: 'Remover também taxonomias e arquivos da API relacionados?',
        default: true
      }
    ]);

    itemsToRemove.push({
      type: 'cpt',
      name: cptAnswer.selectedCPT,
      removeRelated: cptAnswer.removeRelated
    });

  } else if (answers.actionType === 'taxonomy') {
    if (availableTaxonomies.length === 0) {
      console.log(chalk.yellow('⚠️  Nenhuma taxonomia encontrada.'));
      return;
    }

    const taxonomyAnswer = await inquirer.prompt([
      {
        type: 'list',
        name: 'selectedTaxonomy',
        message: 'Qual taxonomia deseja remover?',
        choices: availableTaxonomies.map(taxonomy => ({
          name: `🏷️  ${taxonomy}`,
          value: taxonomy
        }))
      }
    ]);

    itemsToRemove.push({
      type: 'taxonomy',
      name: taxonomyAnswer.selectedTaxonomy
    });

  } else if (answers.actionType === 'complete') {
    const completeAnswer = await inquirer.prompt([
      {
        type: 'checkbox',
        name: 'selectedItems',
        message: 'Selecione os itens para remover:',
        choices: [
          ...availableCPTs.map(cpt => ({
            name: `📝 CPT: ${cpt}`,
            value: { type: 'cpt', name: cpt },
            checked: true
          })),
          ...availableTaxonomies.map(taxonomy => ({
            name: `🏷️  Taxonomia: ${taxonomy}`,
            value: { type: 'taxonomy', name: taxonomy },
            checked: true
          }))
        ],
        validate: (choices) => {
          if (choices.length === 0) {
            return 'Selecione pelo menos um item para remover.';
          }
          return true;
        }
      }
    ]);

    itemsToRemove = completeAnswer.selectedItems;
  }

  // Confirmação final
  const confirmAnswer = await inquirer.prompt([
    {
      type: 'confirm',
      name: 'confirmRemoval',
      message: chalk.red('⚠️  ATENÇÃO: Esta ação não pode ser desfeita. Confirmar remoção?'),
      default: false
    }
  ]);

  if (!confirmAnswer.confirmRemoval) {
    console.log(chalk.yellow('❌ Operação cancelada.'));
    return;
  }

  const spinner = ora('Removendo arquivos...').start();
  const removedFiles = [];
  const errors = [];

  try {
    for (const item of itemsToRemove) {
      const baseName = item.name;
      const files = [];

      if (item.type === 'cpt') {
        // Arquivos do CPT
        files.push({
          path: path.join(cptsDir, `${baseName}.php`),
          description: `CPT: ${baseName}.php`
        });

        if (item.removeRelated !== false) {
          // Taxonomias relacionadas
          const relatedTaxonomies = availableTaxonomies.filter(tax =>
            tax.includes(baseName) || tax.startsWith(`categoria_${baseName}`)
          );

          relatedTaxonomies.forEach(taxonomy => {
            files.push({
              path: path.join(taxonomiesDir, `${taxonomy}.php`),
              description: `Taxonomia relacionada: ${taxonomy}.php`
            });
          });

          // Arquivos da API
          files.push(
            {
              path: path.join(apiRoutesDir, `${baseName}-routes.php`),
              description: `API Routes: ${baseName}-routes.php`
            },
            {
              path: path.join(apiHandlersDir, `class-${baseName}-handler.php`),
              description: `API Handler: class-${baseName}-handler.php`
            },
            {
              path: path.join(apiFormattersDir, `class-${baseName}-formatter.php`),
              description: `API Formatter: class-${baseName}-formatter.php`
            }
          );
        }

      } else if (item.type === 'taxonomy') {
        files.push({
          path: path.join(taxonomiesDir, `${baseName}.php`),
          description: `Taxonomia: ${baseName}.php`
        });
      }

      // Remover arquivos
      for (const file of files) {
        try {
          if (fs.existsSync(file.path)) {
            fs.unlinkSync(file.path);
            removedFiles.push(file.description);
          }
        } catch (error) {
          errors.push(`Erro ao remover ${file.description}: ${error.message}`);
        }
      }
    }

    spinner.succeed(chalk.green('Arquivos removidos com sucesso!'));

    if (removedFiles.length > 0) {
      console.log(chalk.yellow.bold('\n📁 Arquivos removidos:'));
      removedFiles.forEach(file => {
        console.log(chalk.gray(`  ✓ ${file}`));
      });
    }

    if (errors.length > 0) {
      console.log(chalk.red.bold('\n❌ Erros encontrados:'));
      errors.forEach(error => {
        console.log(chalk.red(`  ✗ ${error}`));
      });
    }

    console.log(chalk.blue.bold('\n🔄 Próximos passos:'));
    console.log(chalk.gray('  1. Acesse o admin do WordPress'));
    console.log(chalk.gray('  2. Vá em Configurações > Links Permanentes'));
    console.log(chalk.gray('  3. Clique em "Salvar alterações" para atualizar as URLs'));
    console.log(chalk.gray('  4. Limpe o cache se necessário'));

    console.log(chalk.yellow.bold('\n⚠️  Importante:'));
    console.log(chalk.yellow('  • Os dados (posts, termos) não foram removidos do banco'));
    console.log(chalk.yellow('  • Para remover dados, use o admin do WordPress'));
    console.log(chalk.yellow('  • Considere fazer backup antes de remover dados'));

  } catch (error) {
    spinner.fail(chalk.red('Erro ao remover arquivos'));
    console.error(chalk.red(error.message));
    process.exit(1);
  }
}

if (require.main === module) {
  removeCPT().catch(console.error);
}

module.exports = { removeCPT };
