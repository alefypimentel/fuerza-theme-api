#!/usr/bin/env php
<?php
/**
 * CPTs and Taxonomies Generator
 * 
 * Script to automatically generate Custom Post Types and Taxonomies
 * 
 * Usage:
 * php bin/generate-content-type.php <name> [options]
 * 
 * Examples:
 * php bin/generate-content-type.php product
 * php bin/generate-content-type.php event --with-taxonomy
 * php bin/generate-content-type.php news --singular="News" --plural="News"
 * 
 * @package FuerzaThemeAPI
 */

// Check if running via CLI
if (php_sapi_name() !== 'cli') {
    die("This script must be run via command line.\n");
}

class ContentTypeGenerator {
    
    private $theme_path;
    private $options;
    private $name;
    private $singular;
    private $plural;
    
    public function __construct() {
        $this->theme_path = dirname(__DIR__);
        $this->options = $this->parseArguments();
        
        if (empty($this->options['name'])) {
            $this->showHelp();
            exit(1);
        }
        
        $this->name = $this->sanitizeName($this->options['name']);
        $this->singular = $this->options['singular'] ?? $this->generateSingular($this->name);
        $this->plural = $this->options['plural'] ?? $this->generatePlural($this->singular);
        
        $this->run();
    }
    
    /**
     * Run the generator
     */
    private function run() {
        $this->printHeader();
        
        try {
            // Check if directories exist
            $this->ensureDirectoriesExist();
            
            // Generate CPT
            if (!isset($this->options['taxonomy-only'])) {
                $this->generateCPT();
                $this->success("✅ CPT '{$this->name}' created successfully!");
            }
            
            // Generate taxonomy if requested
            if (isset($this->options['with-taxonomy']) || isset($this->options['taxonomy-only'])) {
                $this->generateTaxonomy();
                $this->success("✅ Taxonomy 'categoria_{$this->name}' created successfully!");
            }
            
            // Generate API routes if requested
            if (isset($this->options['with-api']) && !isset($this->options['taxonomy-only'])) {
                $this->generateAPIFiles();
                $this->success("✅ API files created successfully!");
            }
            
            $this->printSummary();
            
        } catch (Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            exit(1);
        }
    }
    
    /**
     * Parse command line arguments
     */
    private function parseArguments() {
        global $argv;
        $options = [];
        
        // Primeiro argumento é o nome (obrigatório)
        if (isset($argv[1]) && !str_starts_with($argv[1], '--')) {
            $options['name'] = $argv[1];
        }
        
        // Processar opções
        for ($i = 1; $i < count($argv); $i++) {
            $arg = $argv[$i];
            
            if (str_starts_with($arg, '--')) {
                if (str_contains($arg, '=')) {
                    [$key, $value] = explode('=', substr($arg, 2), 2);
                    $options[$key] = $value;
                } else {
                    $options[substr($arg, 2)] = true;
                }
            }
        }
        
        return $options;
    }
    
    /**
     * Ensure directories exist
     */
    private function ensureDirectoriesExist() {
        $dirs = [
            $this->theme_path . '/inc/content-types/cpts',
            $this->theme_path . '/inc/content-types/taxonomies',
            $this->theme_path . '/inc/api/routes',
            $this->theme_path . '/inc/api/handlers',
            $this->theme_path . '/inc/api/formatters'
        ];
        
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                if (!mkdir($dir, 0755, true)) {
                    throw new Exception("Could not create directory: {$dir}");
                }
            }
        }
    }
    
    /**
     * Generate CPT
     */
    private function generateCPT() {
        $filename = $this->theme_path . "/inc/content-types/cpts/{$this->name}.php";
        
        if (file_exists($filename) && !isset($this->options['force'])) {
            throw new Exception("CPT '{$this->name}' already exists. Use --force to overwrite.");
        }
        
        $template = $this->getCPTTemplate();
        $content = $this->replacePlaceholders($template);
        
        if (file_put_contents($filename, $content) === false) {
            throw new Exception("Could not create file: {$filename}");
        }
    }
    
    /**
     * Generate taxonomy
     */
    private function generateTaxonomy() {
        $taxonomy_name = "categoria_{$this->name}";
        $filename = $this->theme_path . "/inc/content-types/taxonomies/{$taxonomy_name}.php";
        
        if (file_exists($filename) && !isset($this->options['force'])) {
            throw new Exception("Taxonomia '{$taxonomy_name}' already exists. Use --force to overwrite.");
        }
        
        $template = $this->getTaxonomyTemplate();
        $content = $this->replacePlaceholders($template);
        
        if (file_put_contents($filename, $content) === false) {
            throw new Exception("Could not create file: {$filename}");
        }
    }
    
    /**
     * Generate API files
     */
    private function generateAPIFiles() {
        // Formatter
        $formatter_file = $this->theme_path . "/inc/api/formatters/class-{$this->name}-formatter.php";
        if (!file_exists($formatter_file) || isset($this->options['force'])) {
            $template = $this->getFormatterTemplate();
            $content = $this->replacePlaceholders($template);
            file_put_contents($formatter_file, $content);
        }
        
        // Handler
        $handler_file = $this->theme_path . "/inc/api/handlers/class-{$this->name}-handler.php";
        if (!file_exists($handler_file) || isset($this->options['force'])) {
            $template = $this->getHandlerTemplate();
            $content = $this->replacePlaceholders($template);
            file_put_contents($handler_file, $content);
        }
        
        // Routes
        $routes_file = $this->theme_path . "/inc/api/routes/{$this->name}-routes.php";
        if (!file_exists($routes_file) || isset($this->options['force'])) {
            $template = $this->getRoutesTemplate();
            $content = $this->replacePlaceholders($template);
            file_put_contents($routes_file, $content);
        }
    }
    
    /**
     * Replace placeholders in templates
     */
    private function replacePlaceholders($template) {
        $replacements = [
            '{{NAME}}' => $this->name,
            '{{SINGULAR}}' => $this->singular,
            '{{PLURAL}}' => $this->plural,
            '{{NAME_UPPER}}' => ucfirst($this->name),
            '{{SINGULAR_UPPER}}' => ucfirst($this->singular),
            '{{PLURAL_UPPER}}' => ucfirst($this->plural),
            '{{CLASS_NAME}}' => $this->generateClassName($this->name),
            '{{TAXONOMY_NAME}}' => "categoria_{$this->name}",
            '{{DATE}}' => date('Y-m-d H:i:s'),
        ];
        
        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }
    
    /**
     * CPT Template
     */
    private function getCPTTemplate() {
        return '<?php
/**
 * Custom Post Type: {{PLURAL_UPPER}}
 * 
 * Auto-generated on {{DATE}}
 * 
 * @package FuerzaThemeAPI
 */

if (!defined(\'ABSPATH\')) {
    exit;
}

// Register CPT for {{PLURAL_UPPER}} using the dynamic system
Content_Manager::register_cpt(\'{{NAME}}\', [
    \'singular_name\' => \'{{SINGULAR_UPPER}}\',
    \'plural_name\' => \'{{PLURAL_UPPER}}\',
    \'description\' => \'Manage site\',
    \'public\' => true,
    \'show_in_rest\' => true,
    \'rest_base\' => \'{{NAME}}\',
    \'supports\' => [
        \'title\',
        \'editor\',
        \'thumbnail\',
        \'excerpt\',
        \'author\',
        \'comments\',
        \'revisions\'
    ],
    \'has_archive\' => true,
    \'rewrite\' => [
        \'slug\' => \'{{NAME}}\',
        \'with_front\' => false,
    ],
    \'menu_icon\' => \'dashicons-admin-post\',
    \'menu_position\' => 20,
    \'hierarchical\' => false,
    \'query_var\' => true,
    \'capability_type\' => \'post\',
    \'map_meta_cap\' => true,
    
    // Custom administrative settings
    \'admin_columns\' => [
        \'{{NAME}}_status\' => [
            \'title\' => \'Status\',
            \'callback\' => function($column, $post_id) {
                if ($column === \'{{NAME}}_status\') {
                    echo \'<span style="color: green;">●</span> Ativo\';
                }
            }
        ]
    ],
    
    // Custom hooks
    \'hooks\' => [
        \'save_post_{{NAME}}\' => function($post_id) {
            // Logic executed when um {{SINGULAR}} is saved
            if (function_exists(\'wp_cache_delete\')) {
                wp_cache_delete(\'{{NAME}}_list\', \'fuerza_theme\');
            }
        }
    ],
    
    // Specific custom labels
    \'labels\' => [
        \'featured_image\' => \'Image for {{SINGULAR_UPPER}}\',
        \'set_featured_image\' => \'Set image for {{SINGULAR}}\',
        \'remove_featured_image\' => \'Remove image for {{SINGULAR}}\',
        \'use_featured_image\' => \'Use as image for {{SINGULAR}}\',
    ]
]);
';
    }
    
    /**
     * Taxonomy Template
     */
    private function getTaxonomyTemplate() {
        return '<?php
/**
 * Taxonomia: Categoria de {{PLURAL_UPPER}}
 * 
 * Auto-generated on {{DATE}}
 * 
 * @package FuerzaThemeAPI
 */

if (!defined(\'ABSPATH\')) {
    exit;
}

// Register Category Taxonomy for {{PLURAL_UPPER}} using the dynamic system
Content_Manager::register_taxonomy(\'{{TAXONOMY_NAME}}\', [\'{{NAME}}\'], [
    \'singular_name\' => \'Categoria de {{SINGULAR_UPPER}}\',
    \'plural_name\' => \'Categorias de {{PLURAL_UPPER}}\',
    \'description\' => \'Categories to organize {{PLURAL}}\',
    \'hierarchical\' => true,
    \'public\' => true,
    \'show_in_rest\' => true,
    \'rest_base\' => \'{{TAXONOMY_NAME}}\',
    \'show_ui\' => true,
    \'show_in_menu\' => true,
    \'show_in_nav_menus\' => true,
    \'show_tagcloud\' => true,
    \'show_in_quick_edit\' => true,
    \'show_admin_column\' => true,
    \'query_var\' => true,
    \'rewrite\' => [
        \'slug\' => \'categoria-{{NAME}}\',
        \'with_front\' => false,
        \'hierarchical\' => true,
    ],
    
    // Custom meta fields for terms
    \'term_meta_fields\' => [
        [
            \'key\' => \'categoria_cor\',
            \'label\' => \'Category Color\',
            \'type\' => \'color\',
            \'description\' => \'Color that represents this category\'
        ],
        [
            \'key\' => \'categoria_destaque\',
            \'label\' => \'Featured Category\',
            \'type\' => \'select\',
            \'options\' => [
                \'nao\' => \'Não\',
                \'sim\' => \'Sim\'
            ],
            \'description\' => \'Mark as featured category\'
        ]
    ],
    
    // Custom administrative columns
    \'admin_columns\' => [
        \'categoria_cor\' => [
            \'title\' => \'Cor\',
            \'callback\' => function($content, $column, $term_id) {
                if ($column === \'categoria_cor\') {
                    $cor = get_term_meta($term_id, \'categoria_cor\', true);
                    if ($cor) {
                        return \'<span style="display:inline-block;width:20px;height:20px;background-color:\' . esc_attr($cor) . \';border-radius:50%;border:1px solid #ddd;"></span> \' . esc_html($cor);
                    }
                    return \'—\';
                }
                return $content;
            }
        ]
    ],
    
    // Custom hooks
    \'hooks\' => [
        \'created_{{TAXONOMY_NAME}}\' => function($term_id) {
            if (function_exists(\'wp_cache_delete\')) {
                wp_cache_delete(\'{{TAXONOMY_NAME}}_list\', \'fuerza_theme\');
            }
        }
    ]
]);
';
    }
    
    /**
     * Formatter Template
     */
    private function getFormatterTemplate() {
        return '<?php
/**
 * Formatter for data of {{PLURAL}}
 * 
 * Auto-generated on {{DATE}}
 * 
 * @package FuerzaThemeAPI
 */

if (!defined(\'ABSPATH\')) {
    exit;
}

require_once get_template_directory() . \'/inc/api/formatters/class-base-formatter.php\';

class {{CLASS_NAME}}_Formatter extends Base_Formatter {
    
    /**
     * Formatar dados de um {{SINGULAR}}
     */
    public static function format_{{NAME}}($post_id = null) {
        if ($post_id) {
            $post = get_post($post_id);
            setup_postdata($post);
        } else {
            $post_id = get_the_ID();
        }
        
        // Basic post data
        ${{NAME}} = self::format_basic_post_data($post_id);
        
        // Add specific data
        ${{NAME}}[\'imagem_destacada\'] = self::format_featured_image($post_id);
        ${{NAME}}[\'autor\'] = self::format_author($post_id);
        ${{NAME}}[\'categorias\'] = self::format_{{NAME}}_categories($post_id);
        ${{NAME}}[\'acf\'] = self::format_acf_fields($post_id);
        
        if ($post_id !== get_the_ID()) {
            wp_reset_postdata();
        }
        
        return ${{NAME}};
    }
    
    /**
     * Format specific categories
     */
    public static function format_{{NAME}}_categories($post_id) {
        $taxonomies = [\'{{TAXONOMY_NAME}}\', \'category\'];
        return self::format_terms($post_id, $taxonomies);
    }
    
    /**
     * Format multiple {{PLURAL}}
     */
    public static function format_{{PLURAL}}($posts) {
        ${{PLURAL}} = [];
        
        foreach ($posts as $post) {
            ${{PLURAL}}[] = self::format_{{NAME}}($post->ID);
        }
        
        return ${{PLURAL}};
    }
    
    /**
     * Format pagination response
     */
    public static function format_pagination($query, $page, $per_page) {
        return [
            \'total_{{PLURAL}}\' => $query->found_posts,
            \'total_paginas\' => $query->max_num_pages,
            \'pagina_atual\' => $page,
            \'{{PLURAL}}_por_pagina\' => $per_page,
            \'tem_proxima_pagina\' => $page < $query->max_num_pages,
            \'tem_pagina_anterior\' => $page > 1,
        ];
    }
}
';
    }
    
    /**
     * Handler Template
     */
    private function getHandlerTemplate() {
        return '<?php
/**
 * Handler for routes of {{PLURAL}}
 * 
 * Auto-generated on {{DATE}}
 * 
 * @package FuerzaThemeAPI
 */

if (!defined(\'ABSPATH\')) {
    exit;
}

require_once get_template_directory() . \'/inc/api/formatters/class-{{NAME}}-formatter.php\';

class {{CLASS_NAME}}_Handler {
    
    /**
     * Obter {{PLURAL}} formatados
     */
    public static function get_{{PLURAL}}($request) {
        $per_page = $request->get_param(\'per_page\');
        $page = $request->get_param(\'page\');
        $orderby = $request->get_param(\'orderby\');
        $order = $request->get_param(\'order\');
        $categoria = $request->get_param(\'categoria\');
        $busca = $request->get_param(\'busca\');

        // Montar argumentos da query
        $args = [
            \'post_type\' => \'{{NAME}}\',
            \'post_status\' => \'publish\',
            \'posts_per_page\' => $per_page,
            \'paged\' => $page,
            \'orderby\' => $orderby,
            \'order\' => $order,
        ];

        // Adicionar filtros se especificados
        if ($categoria) {
            $args[\'tax_query\'][] = [
                \'taxonomy\' => \'{{TAXONOMY_NAME}}\',
                \'field\' => \'slug\',
                \'terms\' => $categoria,
            ];
        }

        if ($busca) {
            $args[\'s\'] = $busca;
        }

        // Executar query
        $query = new WP_Query($args);
        ${{PLURAL}} = [];

        if ($query->have_posts()) {
            ${{PLURAL}} = {{CLASS_NAME}}_Formatter::format_{{PLURAL}}($query->posts);
        }

        // Formatar resposta com paginação
        return [
            \'{{PLURAL}}\' => ${{PLURAL}},
            \'paginacao\' => {{CLASS_NAME}}_Formatter::format_pagination($query, $page, $per_page),
        ];
    }
    
    /**
     * Obter um {{SINGULAR}} específico
     */
    public static function get_{{NAME}}($request) {
        $id = $request->get_param(\'id\');
        
        $post = get_post($id);
        
        if (!$post || $post->post_type !== \'{{NAME}}\' || $post->post_status !== \'publish\') {
            return new WP_Error(
                \'{{NAME}}_not_found\',
                \'{{SINGULAR_UPPER}} not found.\',
                [\'status\' => 404]
            );
        }
        
        return {{CLASS_NAME}}_Formatter::format_{{NAME}}($id);
    }
    
    /**
     * Validate request parameters
     */
    public static function validate_{{PLURAL}}_params() {
        return [
            \'per_page\' => [
                \'default\' => 10,
                \'sanitize_callback\' => \'absint\',
                \'validate_callback\' => function($param) {
                    return is_numeric($param) && $param > 0 && $param <= 100;
                },
            ],
            \'page\' => [
                \'default\' => 1,
                \'sanitize_callback\' => \'absint\',
            ],
            \'orderby\' => [
                \'default\' => \'date\',
                \'sanitize_callback\' => \'sanitize_text_field\',
            ],
            \'order\' => [
                \'default\' => \'DESC\',
                \'sanitize_callback\' => \'sanitize_text_field\',
            ],
            \'categoria\' => [
                \'sanitize_callback\' => \'sanitize_text_field\',
            ],
            \'busca\' => [
                \'sanitize_callback\' => \'sanitize_text_field\',
            ],
        ];
    }
    
    /**
     * Validate specific.*parameters
     */
    public static function validate_{{NAME}}_params() {
        return [
            \'id\' => [
                \'required\' => true,
                \'sanitize_callback\' => \'absint\',
                \'validate_callback\' => function($param) {
                    return is_numeric($param) && $param > 0;
                },
            ],
        ];
    }
}
';
    }
    
    /**
     * Routes Template
     */
    private function getRoutesTemplate() {
        return '<?php
/**
 * API Routes for {{PLURAL}}
 * 
 * Auto-generated on {{DATE}}
 * 
 * @package FuerzaThemeAPI
 */

if (!defined(\'ABSPATH\')) {
    exit;
}

require_once get_template_directory() . \'/inc/api/handlers/class-{{NAME}}-handler.php\';

// Obter instância do gerenciador de API
$api_manager = API_Manager::get_instance();

// Registrar rota para listar {{PLURAL}}
$api_manager->add_route(
    \'/{{PLURAL}}\',
    \'GET\',
    [{{CLASS_NAME}}_Handler::class, \'get_{{PLURAL}}\'],
    {{CLASS_NAME}}_Handler::validate_{{PLURAL}}_params()
);

// Registrar rota para obter {{SINGULAR}} específico
$api_manager->add_route(
    \'/{{PLURAL}}/(?P<id>\\d+)\',
    \'GET\',
    [{{CLASS_NAME}}_Handler::class, \'get_{{NAME}}\'],
    {{CLASS_NAME}}_Handler::validate_{{NAME}}_params()
);
';
    }
    
    /**
     * Utilities
     */
    private function sanitizeName($name) {
        return strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name));
    }
    
    private function generateSingular($name) {
        return ucfirst($name);
    }
    
    private function generatePlural($singular) {
        if (substr($singular, -1) === 's') {
            return $singular;
        }
        return $singular . 's';
    }
    
    private function generateClassName($name) {
        return str_replace(' ', '_', ucwords(str_replace(['_', '-'], ' ', $name)));
    }
    
    /**
     * User Interface
     */
    private function printHeader() {
        echo "\n";
        echo "🚀 Gerador de CPTs e Taxonomias\n";
        echo "================================\n";
        echo "Nome: {$this->name}\n";
        echo "Singular: {$this->singular}\n";
        echo "Plural: {$this->plural}\n";
        echo "\n";
    }
    
    private function printSummary() {
        echo "\n";
        echo "📋 Resumo:\n";
        echo "----------\n";
        
        if (!isset($this->options['taxonomy-only'])) {
            echo "✅ CPT created at: inc/content-types/cpts/{$this->name}.php\n";
        }
        
        if (isset($this->options['with-taxonomy']) || isset($this->options['taxonomy-only'])) {
            echo "✅ Taxonomy created at: inc/content-types/taxonomies/categoria_{$this->name}.php\n";
        }
        
        if (isset($this->options['with-api']) && !isset($this->options['taxonomy-only'])) {
            echo "✅ API created at: inc/api/routes/{$this->name}-routes.php\n";
            echo "📡 Endpoint: /wp-json/meu-tema/v1/{$this->plural}\n";
        }
        
        echo "\n🎉 Done! Your files were generated successfully!\n";
        echo "💡 Dica: Run wp rewrite flush if necessary.\n\n";
    }
    
    private function success($message) {
        echo $message . "\n";
    }
    
    private function error($message) {
        echo $message . "\n";
    }
    
    private function showHelp() {
        echo "\n";
        echo "🚀 Gerador de CPTs e Taxonomias\n";
        echo "================================\n\n";
        echo "Uso: php bin/generate-content-type.php <nome> [opções]\n\n";
        echo "Argumentos:\n";
        echo "  <nome>                    Nome do CPT (obrigatório)\n\n";
        echo "Opções:\n";
        echo "  --singular=<nome>         Custom singular name\n";
        echo "  --plural=<nome>           Custom plural name\n";
        echo "  --with-taxonomy           Create taxonomy along with CPT\n";
        echo "  --taxonomy-only           Create taxonomy only\n";
        echo "  --with-api                Create REST API files\n";
        echo "  --force                   Overwrite existing files\n";
        echo "  --help                    Show this help\n\n";
        echo "Exemplos:\n";
        echo "  php bin/generate-content-type.php produto\n";
        echo "  php bin/generate-content-type.php evento --with-taxonomy\n";
        echo "  php bin/generate-content-type.php noticia --singular=\"Notícia\" --plural=\"Notícias\"\n";
        echo "  php bin/generate-content-type.php portfolio --with-taxonomy --with-api\n";
        echo "  php bin/generate-content-type.php produto --taxonomy-only\n\n";
    }
}

// Verificar se é pedido de ajuda
if (in_array('--help', $argv) || count($argv) < 2) {
    (new ContentTypeGenerator())->showHelp();
    exit(0);
}

// Executar o gerador
new ContentTypeGenerator();
