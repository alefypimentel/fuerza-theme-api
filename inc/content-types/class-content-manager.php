<?php
/**
 * Gerenciador principal de conteúdo (CPTs e Taxonomias)
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

class Content_Manager {
    
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
        $this->load_managers();
        $this->setup_hooks();
    }
    
    /**
     * Carrega os gerenciadores
     */
    private function load_managers() {
        require_once get_template_directory() . '/inc/content-types/class-cpt-manager.php';
        require_once get_template_directory() . '/inc/content-types/class-taxonomy-manager.php';
    }
    
    /**
     * Configura hooks
     */
    private function setup_hooks() {
        // Hook para flush rewrite rules quando necessário
        add_action('after_switch_theme', 'flush_rewrite_rules');
        
        // Hook para debug de CPTs e taxonomias (apenas para admins)
        if (current_user_can('manage_options')) {
            add_action('wp_footer', [$this, 'debug_content_types']);
        }
    }
    
    /**
     * Debug de tipos de conteúdo (apenas para desenvolvimento)
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
     * Registrar um novo CPT usando o sistema dinâmico
     */
    public static function register_cpt($post_type, $config = []) {
        return CPT_Manager::register_cpt($post_type, $config);
    }
    
    /**
     * Registrar uma nova taxonomia usando o sistema dinâmico
     */
    public static function register_taxonomy($taxonomy, $post_types, $config = []) {
        return Taxonomy_Manager::register_taxonomy($taxonomy, $post_types, $config);
    }
    
    /**
     * Obter informações de todos os CPTs registrados
     */
    public static function get_all_cpts() {
        return CPT_Manager::get_registered_cpts();
    }
    
    /**
     * Obter informações de todas as taxonomias registradas
     */
    public static function get_all_taxonomies() {
        return Taxonomy_Manager::get_registered_taxonomies();
    }
    
    /**
     * Verificar se um CPT está registrado
     */
    public static function cpt_exists($post_type) {
        return CPT_Manager::is_cpt_registered($post_type);
    }
    
    /**
     * Verificar se uma taxonomia está registrada
     */
    public static function taxonomy_exists($taxonomy) {
        return Taxonomy_Manager::is_taxonomy_registered($taxonomy);
    }
    
    /**
     * Obter configuração de um CPT específico
     */
    public static function get_cpt_config($post_type) {
        return CPT_Manager::get_cpt_config($post_type);
    }
    
    /**
     * Obter configuração de uma taxonomia específica
     */
    public static function get_taxonomy_config($taxonomy) {
        return Taxonomy_Manager::get_taxonomy_config($taxonomy);
    }
    
    /**
     * Função utilitária para criar um CPT simples
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
     * Função utilitária para criar uma taxonomia simples
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

// Inicializar o gerenciador
Content_Manager::get_instance();
