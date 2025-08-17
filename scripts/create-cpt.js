#!/usr/bin/env node

const inquirer = require('inquirer');
const fs = require('fs');
const path = require('path');
const chalk = require('chalk');
const ora = require('ora');

// Templates
const cptTemplate = (data) => `<?php
/**
 * Custom Post Type: ${data.pluralName}
 * 
 * Gerado automaticamente em ${new Date().toISOString().slice(0, 19).replace('T', ' ')}
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

// Registrar CPT de ${data.pluralName} usando o sistema dinâmico
Content_Manager::register_cpt('${data.slug}', [
    'singular_name' => '${data.singularName}',
    'plural_name' => '${data.pluralName}',
    'description' => '${data.description}',
    'public' => true,
    'show_in_rest' => true,
    'rest_base' => '${data.slug}',
    'supports' => [
        'title',
        'editor',
        'thumbnail',
        'excerpt',
        'author',
        'comments',
        'revisions'${data.hierarchical ? ",\n        'page-attributes'" : ''}
    ],
    'has_archive' => true,
    'rewrite' => [
        'slug' => '${data.slug}',
        'with_front' => false,
    ],
    'menu_icon' => '${data.menuIcon}',
    'menu_position' => ${data.menuPosition},
    'hierarchical' => ${data.hierarchical ? 'true' : 'false'},
    'query_var' => true,
    'capability_type' => 'post',
    'map_meta_cap' => true,
    
    // Configurações administrativas personalizadas
    'admin_columns' => [
        '${data.slug}_status' => [
            'title' => 'Status',
            'callback' => function($column, $post_id) {
                if ($column === '${data.slug}_status') {
                    echo '<span style="color: green;">●</span> Ativo';
                }
            }
        ]
    ],
    
    // Hooks personalizados
    'hooks' => [
        'save_post_${data.slug}' => function($post_id) {
            // Lógica executada quando um ${data.singularName} é salvo
            if (function_exists('wp_cache_delete')) {
                wp_cache_delete('${data.slug}_list', 'fuerza_theme');
            }
        }
    ],
    
    // Labels personalizados específicos
    'labels' => [
        'featured_image' => 'Imagem do ${data.singularName}',
        'set_featured_image' => 'Definir imagem do ${data.singularName}',
        'remove_featured_image' => 'Remover imagem do ${data.singularName}',
        'use_featured_image' => 'Usar como imagem do ${data.singularName}',
    ]
]);`;

const handlerTemplate = (data) => `<?php
/**
 * Manipulador para rotas de ${data.pluralName}
 * 
 * Gerado automaticamente em ${new Date().toISOString().slice(0, 19).replace('T', ' ')}
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once get_template_directory() . '/inc/api/formatters/class-${data.slug}-formatter.php';

class ${data.className}_Handler {
    
    /**
     * Obter ${data.pluralName} formatados
     */
    public static function get_${data.pluralName}($request) {
        $per_page = $request->get_param('per_page');
        $page = $request->get_param('page');
        $orderby = $request->get_param('orderby');
        $order = $request->get_param('order');
        $categoria = $request->get_param('categoria');
        $busca = $request->get_param('busca');

        // Montar argumentos da query
        $args = [
            'post_type' => '${data.slug}',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'orderby' => $orderby,
            'order' => $order,
        ];

        // Adicionar filtros se especificados
        if ($categoria) {
            $args['tax_query'][] = [
                'taxonomy' => 'categoria_${data.slug}',
                'field' => 'slug',
                'terms' => $categoria,
            ];
        }

        if ($busca) {
            $args['s'] = $busca;
        }

        // Executar query
        $query = new WP_Query($args);
        
        if ($query->have_posts()) {
            $items = [];
            while ($query->have_posts()) {
                $query->the_post();
                $items[] = ${data.className}_Formatter::format_single(get_post());
            }
            wp_reset_postdata();
            
            return [
                '${data.slug}s' => $items,
                'pagination' => [
                    'total' => $query->found_posts,
                    'pages' => $query->max_num_pages,
                    'current_page' => $page,
                    'per_page' => $per_page,
                ],
            ];
        }
        
        wp_reset_postdata();
        return new WP_Error('no_${data.slug}s', 'Nenhum ${data.singularName.toLowerCase()} encontrado', ['status' => 404]);
    }
    
    /**
     * Obter ${data.singularName} específico
     */
    public static function get_${data.slug}($request) {
        $id = absint($request->get_param('id'));
        
        if (!$id) {
            return new WP_Error('invalid_id', 'ID do ${data.singularName.toLowerCase()} é obrigatório', ['status' => 400]);
        }
        
        $item = get_post($id);
        
        if (!$item || $item->post_type !== '${data.slug}' || $item->post_status !== 'publish') {
            return new WP_Error('${data.slug}_not_found', '${data.singularName} não encontrado', ['status' => 404]);
        }
        
        return ${data.className}_Formatter::format_${data.slug}($id);
    }
    
    /**
     * Validar parâmetros da requisição
     */
    public static function validate_${data.pluralName}_params() {
        return [
            'per_page' => [
                'default' => 10,
                'sanitize_callback' => 'absint',
            ],
            'page' => [
                'default' => 1,
                'sanitize_callback' => 'absint',
            ],
            'orderby' => [
                'default' => 'date',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'order' => [
                'default' => 'DESC',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'categoria' => [
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'busca' => [
                'sanitize_callback' => 'sanitize_text_field',
            ],
        ];
    }
    
    /**
     * Validar parâmetros de ${data.singularName} específico
     */
    public static function validate_${data.slug}_params() {
        return [
            'id' => [
                'required' => true,
                'sanitize_callback' => 'absint',
            ],
        ];
    }
}`;

const routesTemplate = (data) => `<?php
/**
 * Rotas da API para ${data.pluralName}
 * 
 * Gerado automaticamente em ${new Date().toISOString().slice(0, 19).replace('T', ' ')}
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once get_template_directory() . '/inc/api/handlers/class-${data.slug}-handler.php';

// Obter instância do gerenciador de API
$api_manager = API_Manager::get_instance();

// Registrar rota para listar ${data.pluralName}
$api_manager->add_route(
    '/${data.pluralName}',
    'GET',
    [${data.className}_Handler::class, 'get_${data.pluralName}'],
    ${data.className}_Handler::validate_${data.pluralName}_params(),
    [$api_manager, 'public_permissions'] // Permitir acesso público
);

// Registrar rota para obter ${data.singularName} específico
$api_manager->add_route(
    '/${data.pluralName}/(?P<id>\\d+)',
    'GET',
    [${data.className}_Handler::class, 'get_${data.slug}'],
    ${data.className}_Handler::validate_${data.slug}_params(),
    [$api_manager, 'public_permissions'] // Permitir acesso público
);`;

const taxonomyTemplate = (data) => `<?php
/**
 * Taxonomia: Categoria de ${data.pluralName}
 * 
 * Gerado automaticamente em ${new Date().toISOString().slice(0, 19).replace('T', ' ')}
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

// Registrar Taxonomia de Categoria de ${data.pluralName} usando o sistema dinâmico
Content_Manager::register_taxonomy('categoria_${data.slug}', ['${data.slug}'], [
    'singular_name' => 'Categoria de ${data.singularName}',
    'plural_name' => 'Categorias de ${data.pluralName}',
    'description' => 'Categorias para organizar ${data.pluralName.toLowerCase()}',
    'hierarchical' => true,
    'public' => true,
    'show_in_rest' => true,
    'rest_base' => 'categoria_${data.slug}',
    'show_ui' => true,
    'show_in_menu' => true,
    'show_in_nav_menus' => true,
    'show_tagcloud' => true,
    'show_in_quick_edit' => true,
    'show_admin_column' => true,
    'query_var' => true,
    'rewrite' => [
        'slug' => 'categoria-${data.slug}',
        'with_front' => false,
        'hierarchical' => true,
    ],
    
    // Campos meta personalizados para termos
    'term_meta_fields' => [
        [
            'key' => 'categoria_cor',
            'label' => 'Cor da Categoria',
            'type' => 'color',
            'description' => 'Cor que representa esta categoria'
        ],
        [
            'key' => 'categoria_destaque',
            'label' => 'Categoria em Destaque',
            'type' => 'select',
            'options' => [
                'nao' => 'Não',
                'sim' => 'Sim'
            ],
            'description' => 'Marcar como categoria em destaque'
        ]
    ],
    
    // Colunas administrativas personalizadas
    'admin_columns' => [
        'categoria_cor' => [
            'title' => 'Cor',
            'callback' => function($content, $column, $term_id) {
                if ($column === 'categoria_cor') {
                    $cor = get_term_meta($term_id, 'categoria_cor', true);
                    if ($cor) {
                        return '<span style="display:inline-block;width:20px;height:20px;background-color:' . esc_attr($cor) . ';border-radius:50%;border:1px solid #ddd;"></span> ' . esc_html($cor);
                    }
                    return '—';
                }
                return $content;
            }
        ]
    ],
    
    // Hooks personalizados
    'hooks' => [
        'created_categoria_${data.slug}' => function($term_id) {
            if (function_exists('wp_cache_delete')) {
                wp_cache_delete('categoria_${data.slug}_list', 'fuerza_theme');
            }
        }
    ]
]);`;

const formatterTemplate = (data) => `<?php
/**
 * Formatador para dados de ${data.pluralName}
 * 
 * Gerado automaticamente em ${new Date().toISOString().slice(0, 19).replace('T', ' ')}
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once get_template_directory() . '/inc/api/formatters/class-base-formatter.php';

class ${data.className}_Formatter extends Base_Formatter {
    
    /**
     * Formatar dados de um ${data.singularName}
     */
    public static function format_${data.slug}($post_id = null) {
        if ($post_id) {
            $post = get_post($post_id);
            setup_postdata($post);
        } else {
            $post_id = get_the_ID();
        }
        
        // Dados básicos do post
        $item = self::format_basic_post_data($post_id);
        
        // Adicionar dados específicos
        $item['imagem_destacada'] = self::format_featured_image($post_id);
        $item['autor'] = self::format_author($post_id);
        $item['categorias'] = self::format_${data.slug}_categories($post_id);
        $item['acf'] = self::format_acf_fields($post_id);
        
        if ($post_id !== get_the_ID()) {
            wp_reset_postdata();
        }
        
        return $item;
    }
    
    /**
     * Formatar categorias específicas
     */
    public static function format_${data.slug}_categories($post_id) {
        $taxonomies = ['categoria_${data.slug}', 'category'];
        return self::format_terms($post_id, $taxonomies);
    }
    
    /**
     * Formatar lista de ${data.pluralName}
     */
    public static function format_single($post) {
        return self::format_${data.slug}($post->ID);
    }
    
    /**
     * Formatar múltiplos ${data.pluralName}
     */
    public static function format_multiple($posts) {
        $items = [];
        
        foreach ($posts as $post) {
            $items[] = self::format_single($post);
        }
        
        return $items;
    }
}`;

async function createCPT() {
  console.log(chalk.blue.bold('\n🎯 Criador Interativo de Custom Post Types\n'));

  const answers = await inquirer.prompt([
    {
      type: 'input',
      name: 'singularName',
      message: 'Nome singular (ex: Produto):',
      validate: input => input.length > 0 || 'Nome singular é obrigatório'
    },
    {
      type: 'input',
      name: 'pluralName',
      message: 'Nome plural (ex: Produtos):',
      validate: input => input.length > 0 || 'Nome plural é obrigatório'
    },
    {
      type: 'input',
      name: 'slug',
      message: 'Slug (ex: produto):',
      default: (answers) => answers.singularName.toLowerCase().replace(/\s+/g, '-'),
      validate: input => /^[a-z0-9-]+$/.test(input) || 'Slug deve conter apenas letras minúsculas, números e hífens'
    },
    {
      type: 'input',
      name: 'description',
      message: 'Descrição:',
      default: (answers) => `Gerenciar ${answers.pluralName} do site`
    },
    {
      type: 'list',
      name: 'menuIcon',
      message: 'Ícone do menu:',
      choices: [
        { name: '📝 Post', value: 'dashicons-admin-post' },
        { name: '📄 Página', value: 'dashicons-admin-page' },
        { name: '🏷️ Tag', value: 'dashicons-tag' },
        { name: '📁 Categoria', value: 'dashicons-category' },
        { name: '👥 Usuários', value: 'dashicons-admin-users' },
        { name: '🛍️ Loja', value: 'dashicons-store' },
        { name: '🎯 Portfolio', value: 'dashicons-portfolio' },
        { name: '📊 Gráficos', value: 'dashicons-chart-bar' },
        { name: '⭐ Estrela', value: 'dashicons-star-filled' }
      ]
    },
    {
      type: 'number',
      name: 'menuPosition',
      message: 'Posição no menu (20-100):',
      default: 20,
      validate: input => (input >= 20 && input <= 100) || 'Posição deve estar entre 20 e 100'
    },
    {
      type: 'confirm',
      name: 'hierarchical',
      message: 'Hierárquico (como páginas)?',
      default: false
    },
    {
      type: 'confirm',
      name: 'createTaxonomy',
      message: 'Criar taxonomia associada?',
      default: false
    },
    {
      type: 'confirm',
      name: 'createAPI',
      message: 'Criar rotas da API?',
      default: true
    }
  ]);

  // Processar dados
  const data = {
    ...answers,
    className: answers.singularName.replace(/\s+/g, '_')
  };

  const spinner = ora('Criando arquivos do Custom Post Type...').start();

  try {
    // Criar diretórios se não existirem
    const dirs = [
      '../inc/content-types/cpts',
      '../inc/content-types/taxonomies',
      '../inc/api/handlers',
      '../inc/api/routes',
      '../inc/api/formatters'
    ];

    dirs.forEach(dir => {
      const fullPath = path.join(__dirname, dir);
      if (!fs.existsSync(fullPath)) {
        fs.mkdirSync(fullPath, { recursive: true });
      }
    });

    // Criar arquivos
    const files = [
      {
        path: `../inc/content-types/cpts/${data.slug}.php`,
        content: cptTemplate(data)
      }
    ];

    if (data.createTaxonomy) {
      files.push({
        path: `../inc/content-types/taxonomies/categoria_${data.slug}.php`,
        content: taxonomyTemplate(data)
      });
    }

    if (data.createAPI) {
      files.push(
        {
          path: `../inc/api/handlers/class-${data.slug}-handler.php`,
          content: handlerTemplate(data)
        },
        {
          path: `../inc/api/routes/${data.slug}-routes.php`,
          content: routesTemplate(data)
        },
        {
          path: `../inc/api/formatters/class-${data.slug}-formatter.php`,
          content: formatterTemplate(data)
        }
      );
    }

    files.forEach(file => {
      const fullPath = path.join(__dirname, file.path);
      fs.writeFileSync(fullPath, file.content);
    });

    spinner.succeed(chalk.green('Custom Post Type criado com sucesso!'));

    console.log(chalk.yellow.bold('\n📁 Arquivos criados:'));
    files.forEach(file => {
      console.log(chalk.gray(`  ✓ ${file.path}`));
    });

    console.log(chalk.blue.bold('\n🚀 Próximos passos:'));
    console.log(chalk.gray('  1. Acesse o admin do WordPress'));
    console.log(chalk.gray(`  2. Vá para "${data.pluralName}" no menu lateral`));
    if (data.createTaxonomy) {
      console.log(chalk.gray(`  3. Configure as categorias em "Categorias de ${data.pluralName}"`));
      console.log(chalk.gray('  4. Crie alguns posts de teste'));
    } else {
      console.log(chalk.gray('  3. Crie alguns posts de teste'));
    }
    if (data.createAPI) {
      const step = data.createTaxonomy ? '5' : '4';
      console.log(chalk.gray(`  ${step}. Teste a API: /wp-json/fuerza-theme/v1/${data.pluralName}`));
    }

  } catch (error) {
    spinner.fail(chalk.red('Erro ao criar Custom Post Type'));
    console.error(chalk.red(error.message));
    process.exit(1);
  }
}

if (require.main === module) {
  createCPT().catch(console.error);
}

module.exports = { createCPT };
