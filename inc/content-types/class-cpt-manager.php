<?php
/**
 * Gerenciador dinâmico de Custom Post Types
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

class CPT_Manager {
    
    /**
     * Lista de CPTs registrados
     */
    private static $registered_cpts = [];
    
    /**
     * Instância singleton
     */
    private static $instance = null;
    
    /**
     * Obtém instância singleton
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Construtor privado
     */
    private function __construct() {
        add_action('init', [$this, 'register_all_cpts']);
        $this->load_cpt_definitions();
    }
    
    /**
     * Carrega todas as definições de CPTs
     */
    private function load_cpt_definitions() {
        $cpts_dir = get_template_directory() . '/inc/content-types/cpts/';
        
        if (!is_dir($cpts_dir)) {
            return;
        }
        
        $cpt_files = glob($cpts_dir . '*.php');
        
        foreach ($cpt_files as $file) {
            require_once $file;
        }
    }
    
    /**
     * Registra um novo CPT
     */
    public static function register_cpt($post_type, $config) {
        self::$registered_cpts[$post_type] = $config;
    }
    
    /**
     * Registra todos os CPTs no WordPress
     */
    public function register_all_cpts() {
        foreach (self::$registered_cpts as $post_type => $config) {
            $this->register_single_cpt($post_type, $config);
        }
    }
    
    /**
     * Registra um CPT individual
     */
    private function register_single_cpt($post_type, $config) {
        // Configurações padrão
        $defaults = [
            'label' => ucfirst($post_type),
            'labels' => $this->generate_labels($post_type, $config),
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_nav_menus' => true,
            'show_in_admin_bar' => true,
            'show_in_rest' => true,
            'rest_base' => $post_type,
            'capability_type' => 'post',
            'hierarchical' => false,
            'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'author', 'comments'],
            'has_archive' => true,
            'rewrite' => ['slug' => $post_type],
            'query_var' => true,
            'menu_icon' => 'dashicons-admin-post',
            'menu_position' => 20,
        ];
        
        // Mesclar configurações
        $args = wp_parse_args($config, $defaults);
        
        // Registrar o CPT
        register_post_type($post_type, $args);
        
        // Aplicar configurações adicionais se especificadas
        $this->apply_additional_configs($post_type, $config);
    }
    
    /**
     * Gera labels automaticamente
     */
    private function generate_labels($post_type, $config) {
        $singular = $config['singular_name'] ?? ucfirst($post_type);
        $plural = $config['plural_name'] ?? $singular . 's';
        
        $labels = [
            'name' => $plural,
            'singular_name' => $singular,
            'menu_name' => $plural,
            'name_admin_bar' => $singular,
            'add_new' => 'Adicionar Novo',
            'add_new_item' => 'Adicionar Novo ' . $singular,
            'new_item' => 'Novo ' . $singular,
            'edit_item' => 'Editar ' . $singular,
            'view_item' => 'Ver ' . $singular,
            'all_items' => 'Todos os ' . $plural,
            'search_items' => 'Buscar ' . $plural,
            'parent_item_colon' => $singular . ' Pai:',
            'not_found' => 'Nenhum ' . strtolower($singular) . ' encontrado.',
            'not_found_in_trash' => 'Nenhum ' . strtolower($singular) . ' encontrado na lixeira.',
            'featured_image' => 'Imagem destacada',
            'set_featured_image' => 'Definir imagem destacada',
            'remove_featured_image' => 'Remover imagem destacada',
            'use_featured_image' => 'Usar como imagem destacada',
            'archives' => 'Arquivo de ' . $plural,
            'insert_into_item' => 'Inserir no ' . strtolower($singular),
            'uploaded_to_this_item' => 'Enviado para este ' . strtolower($singular),
            'filter_items_list' => 'Filtrar lista de ' . strtolower($plural),
            'items_list_navigation' => 'Navegação da lista de ' . strtolower($plural),
            'items_list' => 'Lista de ' . strtolower($plural),
        ];
        
        // Permitir override de labels específicos
        if (isset($config['labels']) && is_array($config['labels'])) {
            $labels = array_merge($labels, $config['labels']);
        }
        
        return $labels;
    }
    
    /**
     * Aplicar configurações adicionais
     */
    private function apply_additional_configs($post_type, $config) {
        // Configurar meta boxes personalizados
        if (isset($config['meta_boxes']) && is_array($config['meta_boxes'])) {
            $this->setup_meta_boxes($post_type, $config['meta_boxes']);
        }
        
        // Configurar colunas administrativas personalizadas
        if (isset($config['admin_columns']) && is_array($config['admin_columns'])) {
            $this->setup_admin_columns($post_type, $config['admin_columns']);
        }
        
        // Configurar hooks personalizados
        if (isset($config['hooks']) && is_array($config['hooks'])) {
            $this->setup_custom_hooks($post_type, $config['hooks']);
        }
    }
    
    /**
     * Configurar meta boxes
     */
    private function setup_meta_boxes($post_type, $meta_boxes) {
        foreach ($meta_boxes as $meta_box) {
            add_action('add_meta_boxes', function() use ($post_type, $meta_box) {
                add_meta_box(
                    $meta_box['id'],
                    $meta_box['title'],
                    $meta_box['callback'],
                    $post_type,
                    $meta_box['context'] ?? 'normal',
                    $meta_box['priority'] ?? 'default'
                );
            });
        }
    }
    
    /**
     * Configurar colunas administrativas
     */
    private function setup_admin_columns($post_type, $columns) {
        // Adicionar colunas
        add_filter("manage_{$post_type}_posts_columns", function($existing_columns) use ($columns) {
            return array_merge($existing_columns, $columns);
        });
        
        // Popular colunas
        add_action("manage_{$post_type}_posts_custom_column", function($column, $post_id) use ($columns) {
            if (isset($columns[$column]) && isset($columns[$column]['callback'])) {
                call_user_func($columns[$column]['callback'], $column, $post_id);
            }
        }, 10, 2);
    }
    
    /**
     * Configurar hooks personalizados
     */
    private function setup_custom_hooks($post_type, $hooks) {
        foreach ($hooks as $hook => $callback) {
            add_action($hook, $callback);
        }
    }
    
    /**
     * Obter todos os CPTs registrados
     */
    public static function get_registered_cpts() {
        return self::$registered_cpts;
    }
    
    /**
     * Verificar se um CPT está registrado
     */
    public static function is_cpt_registered($post_type) {
        return isset(self::$registered_cpts[$post_type]);
    }
    
    /**
     * Obter configuração de um CPT específico
     */
    public static function get_cpt_config($post_type) {
        return self::$registered_cpts[$post_type] ?? null;
    }
}

// Inicializar o gerenciador
CPT_Manager::get_instance();
