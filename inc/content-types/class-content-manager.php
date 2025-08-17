<?php
/**
 * Main Content Manager (CPTs and Taxonomies)
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

class Content_Manager {
    
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
        $this->load_managers();
        $this->setup_hooks();
    }
    
    /**
     * Load managers
     */
    private function load_managers() {
        require_once get_template_directory() . '/inc/content-types/class-cpt-manager.php';
        require_once get_template_directory() . '/inc/content-types/class-taxonomy-manager.php';
    }
    
    /**
     * Setup hooks
     */
    private function setup_hooks() {
        // Hook to flush rewrite rules when necessary
        add_action('after_switch_theme', 'flush_rewrite_rules');
        
        // Hook for CPTs and taxonomies debug (admin only)
        if (current_user_can('manage_options')) {
            add_action('wp_footer', [$this, 'debug_content_types']);
        }
    }
    
    /**
     * Debug content types (development only)
     */
    public function debug_content_types() {
        if (!WP_DEBUG || !current_user_can('manage_options')) {
            return;
        }
        
        $cpts = CPT_Manager::get_registered_cpts();
        $taxonomies = Taxonomy_Manager::get_registered_taxonomies();
        
        if (!empty($cpts) || !empty($taxonomies)) {
            echo '<!-- Content Types Debug -->';
            echo '<!-- CPTs: ' . implode(', ', array_keys($cpts)) . ' -->';
            echo '<!-- Taxonomies: ' . implode(', ', array_keys($taxonomies)) . ' -->';
        }
    }
    
    /**
     * Register a new CPT using the dynamic system
     */
    public static function register_cpt($post_type, $config = []) {
        return CPT_Manager::register_cpt($post_type, $config);
    }
    
    /**
     * Register a new taxonomy using the dynamic system
     */
    public static function register_taxonomy($taxonomy, $post_types, $config = []) {
        return Taxonomy_Manager::register_taxonomy($taxonomy, $post_types, $config);
    }
    
    /**
     * Get information for all registered CPTs
     */
    public static function get_all_cpts() {
        return CPT_Manager::get_registered_cpts();
    }
    
    /**
     * Get information for all registered taxonomies
     */
    public static function get_all_taxonomies() {
        return Taxonomy_Manager::get_registered_taxonomies();
    }
    
    /**
     * Check if a CPT is registered
     */
    public static function cpt_exists($post_type) {
        return CPT_Manager::is_cpt_registered($post_type);
    }
    
    /**
     * Check if a taxonomy is registered
     */
    public static function taxonomy_exists($taxonomy) {
        return Taxonomy_Manager::is_taxonomy_registered($taxonomy);
    }
    
    /**
     * Get specific CPT configuration
     */
    public static function get_cpt_config($post_type) {
        return CPT_Manager::get_cpt_config($post_type);
    }
    
    /**
     * Get specific taxonomy configuration
     */
    public static function get_taxonomy_config($taxonomy) {
        return Taxonomy_Manager::get_taxonomy_config($taxonomy);
    }
    
    /**
     * Utility function to create a simple CPT
     */
    public static function create_simple_cpt($post_type, $singular_name, $plural_name, $options = []) {
        $config = array_merge([
            'singular_name' => $singular_name,
            'plural_name' => $plural_name,
            'public' => true,
            'show_in_rest' => true,
            'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
            'has_archive' => true,
        ], $options);
        
        return self::register_cpt($post_type, $config);
    }
    
    /**
     * Utility function to create a simple taxonomy
     */
    public static function create_simple_taxonomy($taxonomy, $post_types, $singular_name, $plural_name, $options = []) {
        $config = array_merge([
            'singular_name' => $singular_name,
            'plural_name' => $plural_name,
            'hierarchical' => false,
            'show_in_rest' => true,
            'show_admin_column' => true,
        ], $options);
        
        return self::register_taxonomy($taxonomy, $post_types, $config);
    }
}

// Initialize the manager
Content_Manager::get_instance();
