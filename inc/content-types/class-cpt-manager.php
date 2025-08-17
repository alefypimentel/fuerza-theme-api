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
}

// Initialize the manager
CPT_Manager::get_instance();
