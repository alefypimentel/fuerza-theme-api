<?php
/**
 * Dynamic Custom Post Types Manager
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

class CPT_Manager {
    
    /**
     * List of registered CPTs
     */
    private static $registered_cpts = [];
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Private constructor
     */
    private function __construct() {
        add_action('init', [$this, 'register_all_cpts']);
        $this->load_cpt_definitions();
    }
    
    /**
     * Load all CPT definitions
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
     * Register a new CPT
     */
    public static function register_cpt($post_type, $config) {
        self::$registered_cpts[$post_type] = $config;
    }
    
    /**
     * Register all CPTs in WordPress
     */
    public function register_all_cpts() {
        foreach (self::$registered_cpts as $post_type => $config) {
            $this->register_single_cpt($post_type, $config);
        }
    }
    
    /**
     * Register individual CPT
     */
    private function register_single_cpt($post_type, $config) {
        // Default settings
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
        
        // Adicionar suporte a tradução automático
        $this->add_translation_support($post_type, $defaults);
        
        // Merge settings
        $args = wp_parse_args($config, $defaults);
        
        // Register the CPT
        register_post_type($post_type, $args);
        
        // Apply additional configurations if specified
        $this->apply_additional_configs($post_type, $config);
    }
    
    /**
     * Generate labels automatically
     */
    private function generate_labels($post_type, $config) {
        $singular = $config['singular_name'] ?? ucfirst($post_type);
        $plural = $config['plural_name'] ?? $singular . 's';
        
        $labels = [
            'name' => $plural,
            'singular_name' => $singular,
            'menu_name' => $plural,
            'name_admin_bar' => $singular,
            'add_new' => __('Add New', 'fuerza-theme'),
            'add_new_item' => sprintf(__('Add New %s', 'fuerza-theme'), $singular),
            'new_item' => sprintf(__('New %s', 'fuerza-theme'), $singular),
            'edit_item' => sprintf(__('Edit %s', 'fuerza-theme'), $singular),
            'view_item' => sprintf(__('View %s', 'fuerza-theme'), $singular),
            'all_items' => sprintf(__('All %s', 'fuerza-theme'), $plural),
            'search_items' => sprintf(__('Search %s', 'fuerza-theme'), $plural),
            'parent_item_colon' => sprintf(__('%s Parent:', 'fuerza-theme'), $singular),
            'not_found' => sprintf(__('No %s found.', 'fuerza-theme'), strtolower($plural)),
            'not_found_in_trash' => sprintf(__('No %s found in Trash.', 'fuerza-theme'), strtolower($plural)),
            'featured_image' => __('Featured Image', 'fuerza-theme'),
            'set_featured_image' => __('Set Featured Image', 'fuerza-theme'),
            'remove_featured_image' => __('Remove Featured Image', 'fuerza-theme'),
            'use_featured_image' => __('Use as Featured Image', 'fuerza-theme'),
            'archives' => sprintf(__('%s Archives', 'fuerza-theme'), $singular),
            'insert_into_item' => sprintf(__('Insert into %s', 'fuerza-theme'), strtolower($singular)),
            'uploaded_to_this_item' => sprintf(__('Uploaded to this %s', 'fuerza-theme'), strtolower($singular)),
            'filter_items_list' => sprintf(__('Filter %s list', 'fuerza-theme'), strtolower($plural)),
            'items_list_navigation' => sprintf(__('%s list navigation', 'fuerza-theme'), $plural),
            'items_list' => sprintf(__('%s list', 'fuerza-theme'), $plural),
        ];
        
        // Allow override of specific labels
        if (isset($config['labels']) && is_array($config['labels'])) {
            $labels = array_merge($labels, $config['labels']);
        }
        
        return $labels;
    }
    
    /**
     * Apply additional configurations
     */
    private function apply_additional_configs($post_type, $config) {
        // Configure custom meta boxes
        if (isset($config['meta_boxes']) && is_array($config['meta_boxes'])) {
            $this->setup_meta_boxes($post_type, $config['meta_boxes']);
        }
        
        // Configure custom admin columns
        if (isset($config['admin_columns']) && is_array($config['admin_columns'])) {
            $this->setup_admin_columns($post_type, $config['admin_columns']);
        }
        
        // Configure custom hooks
        if (isset($config['hooks']) && is_array($config['hooks'])) {
            $this->setup_custom_hooks($post_type, $config['hooks']);
        }
    }
    
    /**
     * Configure meta boxes
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
     * Configure admin columns
     */
    private function setup_admin_columns($post_type, $columns) {
        // Add columns
        add_filter("manage_{$post_type}_posts_columns", function($existing_columns) use ($columns) {
            return array_merge($existing_columns, $columns);
        });
        
        // Populate columns
        add_action("manage_{$post_type}_posts_custom_column", function($column, $post_id) use ($columns) {
            if (isset($columns[$column]) && isset($columns[$column]['callback'])) {
                call_user_func($columns[$column]['callback'], $column, $post_id);
            }
        }, 10, 2);
    }
    
    /**
     * Configure custom hooks
     */
    private function setup_custom_hooks($post_type, $hooks) {
        foreach ($hooks as $hook => $callback) {
            add_action($hook, $callback);
        }
    }
    
    /**
     * Get all registered CPTs
     */
    public static function get_registered_cpts() {
        return self::$registered_cpts;
    }
    
    /**
     * Check if a CPT is registered
     */
    public static function is_cpt_registered($post_type) {
        return isset(self::$registered_cpts[$post_type]);
    }
    
    /**
     * Get specific CPT configuration
     */
    public static function get_cpt_config($post_type) {
        return self::$registered_cpts[$post_type] ?? null;
    }
    
    /**
     * Adicionar suporte automático a tradução
     */
    private function add_translation_support($post_type, &$defaults) {
        // Verificar se há plugin de tradução ativo
        $translation_support = class_exists('Fuerza_Translation_Support') ? Fuerza_Translation_Support::get_instance() : null;
        
        if (!$translation_support || !$translation_support->has_translation_plugin()) {
            return;
        }
        
        // Configurações específicas para tradução
        if ($translation_support->is_wpml_active()) {
            // Para WPML - adicionar suporte específico
            add_action('init', function() use ($post_type) {
                // Registrar strings para tradução
                do_action('wpml_register_single_string', 'fuerza-theme-cpt', "Post Type {$post_type}", $post_type);
                
                // Configurar como traduzível
                if (function_exists('icl_register_string')) {
                    icl_register_string('fuerza-theme-cpt', "Post Type {$post_type} Label", ucfirst($post_type));
                }
            }, 25);
        }
        
        if ($translation_support->is_polylang_active()) {
            // Para Polylang - configurações específicas
            add_action('init', function() use ($post_type) {
                // Polylang detecta CPTs automaticamente quando show_in_rest = true
                // Apenas certificar que está configurado corretamente
                if (function_exists('pll_register_string')) {
                    pll_register_string("cpt_{$post_type}_label", ucfirst($post_type), 'Fuerza Theme');
                }
            }, 25);
        }
        
        // Adicionar suporte REST API multilíngue
        $defaults['show_in_rest'] = true;
        
        // Hook para após registro do CPT
        add_action('registered_post_type', function($post_type_registered, $post_type_object) use ($post_type) {
            if ($post_type_registered === $post_type) {
                $this->configure_cpt_translation($post_type);
            }
        }, 10, 2);
    }
    
    /**
     * Configurar tradução após registro do CPT
     */
    private function configure_cpt_translation($post_type) {
        $translation_support = class_exists('Fuerza_Translation_Support') ? Fuerza_Translation_Support::get_instance() : null;
        
        if (!$translation_support || !$translation_support->has_translation_plugin()) {
            return;
        }
        
        // Para WPML
        if ($translation_support->is_wpml_active()) {
            // Configurar o CPT como traduzível no WPML
            global $sitepress_settings;
            if (isset($sitepress_settings['custom_posts_sync_option'])) {
                $sitepress_settings['custom_posts_sync_option'][$post_type] = 1; // 1 = traduzir, 2 = não traduzir
            }
            
            // Aplicar filtros WPML
            add_filter('wpml_get_translatable_types', function($types) use ($post_type) {
                $types[] = $post_type;
                return $types;
            });
        }
        
        // Para Polylang
        if ($translation_support->is_polylang_active()) {
            // Polylang gerencia automaticamente CPTs com show_in_rest = true
            // Adicionar filtros específicos se necessário
            add_filter('pll_get_post_types', function($post_types) use ($post_type) {
                $post_types[$post_type] = $post_type;
                return $post_types;
            });
        }
    }
}

// Initialize the manager
CPT_Manager::get_instance();
