<?php
/**
 * Sistema de Suporte a Plugins de Tradução (WPML/Polylang)
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

class Fuerza_Translation_Support {
    
    /**
     * Instância singleton
     */
    private static $instance = null;
    
    /**
     * Plugin de tradução ativo
     */
    private $active_plugin = null;
    
    /**
     * Idiomas disponíveis
     */
    private $available_languages = [];
    
    /**
     * Idioma padrão
     */
    private $default_language = '';
    
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
        add_action('init', [$this, 'detect_translation_plugin'], 5);
        add_action('init', [$this, 'setup_translation_support'], 10);
        add_action('rest_api_init', [$this, 'add_language_parameter_to_api']);
    }
    
    /**
     * Detectar qual plugin de tradução está ativo
     */
    public function detect_translation_plugin() {
        if (defined('ICL_SITEPRESS_VERSION') && class_exists('SitePress')) {
            $this->active_plugin = 'wpml';
            $this->setup_wpml();
        } elseif (function_exists('pll_languages_list')) {
            $this->active_plugin = 'polylang';
            $this->setup_polylang();
        }
        
        // Hook para permitir outros plugins
        do_action('fuerza_translation_plugin_detected', $this->active_plugin);
    }
    
    /**
     * Configurar suporte WPML
     */
    private function setup_wpml() {
        global $sitepress;
        
        if (!$sitepress) {
            return;
        }
        
        // Obter idiomas disponíveis
        $this->available_languages = icl_get_languages('skip_missing=0');
        $this->default_language = $sitepress->get_default_language();
        
        // Configurar CPTs para tradução automática
        add_action('init', [$this, 'configure_wpml_cpts'], 20);
        
        // Filtros para API
        add_filter('fuerza_api_get_posts_args', [$this, 'wpml_filter_posts_args'], 10, 2);
        add_filter('fuerza_api_format_post', [$this, 'wpml_add_translation_info'], 10, 2);
    }
    
    /**
     * Configurar suporte Polylang
     */
    private function setup_polylang() {
        // Obter idiomas disponíveis
        if (function_exists('pll_languages_list')) {
            $languages = pll_languages_list(['fields' => null]);
            foreach ($languages as $lang) {
                $this->available_languages[$lang->slug] = [
                    'code' => $lang->slug,
                    'native_name' => $lang->name,
                    'url' => pll_home_url($lang->slug)
                ];
            }
            
            $this->default_language = pll_default_language();
        }
        
        // Filtros para API
        add_filter('fuerza_api_get_posts_args', [$this, 'polylang_filter_posts_args'], 10, 2);
        add_filter('fuerza_api_format_post', [$this, 'polylang_add_translation_info'], 10, 2);
    }
    
    /**
     * Configurar CPTs para WPML
     */
    public function configure_wpml_cpts() {
        if (!$this->is_wpml_active()) {
            return;
        }
        
        // Obter todos os CPTs registrados pelo tema
        $registered_cpts = CPT_Manager::get_registered_cpts();
        
        foreach ($registered_cpts as $post_type => $config) {
            // Registrar CPT para tradução no WPML
            do_action('wpml_register_single_string', 'fuerza-theme', "CPT {$post_type} singular", $config['singular_name'] ?? ucfirst($post_type));
            do_action('wpml_register_single_string', 'fuerza-theme', "CPT {$post_type} plural", $config['plural_name'] ?? ucfirst($post_type) . 's');
            
            // Configurar tradução automática para o CPT
            $this->register_wpml_post_type($post_type);
        }
        
        // Registrar taxonomias também
        $this->register_wpml_taxonomies();
    }
    
    /**
     * Registrar post type no WPML
     */
    private function register_wpml_post_type($post_type) {
        // Configuração para WPML
        $wpml_config = [
            'kind' => 'post_type',
            'name' => $post_type,
            'translate' => 1, // 0 = não traduzir, 1 = traduzir, 2 = exibir como traduzido
            'display_as_translated' => 1
        ];
        
        // Aplicar configuração
        do_action('wpml_register_single_string', 'admin_texts_wpml_posts', "Post Type: {$post_type}", $post_type);
    }
    
    /**
     * Registrar taxonomias no WPML
     */
    private function register_wpml_taxonomies() {
        // Obter taxonomias personalizadas do tema
        $theme_taxonomies = get_taxonomies(['_builtin' => false], 'objects');
        
        foreach ($theme_taxonomies as $taxonomy) {
            if (strpos($taxonomy->name, 'categoria_') === 0) {
                do_action('wpml_register_single_string', 'admin_texts_wpml_taxonomies', "Taxonomy: {$taxonomy->name}", $taxonomy->name);
            }
        }
    }
    
    /**
     * Configurar suporte geral para tradução
     */
    public function setup_translation_support() {
        if (!$this->has_translation_plugin()) {
            return;
        }
        
        // Adicionar suporte a REST API com idiomas
        add_action('rest_api_init', [$this, 'register_language_routes']);
        
        // Adicionar hooks para formatação multilíngue
        add_filter('fuerza_api_post_data', [$this, 'add_translation_metadata'], 10, 2);
        add_filter('fuerza_api_posts_query', [$this, 'filter_posts_by_language'], 10, 2);
    }
    
    /**
     * Adicionar parâmetro de idioma à API
     */
    public function add_language_parameter_to_api() {
        if (!$this->has_translation_plugin()) {
            return;
        }
        
        // Registrar parâmetro global de idioma
        register_rest_field('post', 'language', [
            'get_callback' => [$this, 'get_post_language'],
            'schema' => [
                'description' => 'Idioma do post',
                'type' => 'string',
                'context' => ['view', 'edit']
            ]
        ]);
        
        register_rest_field('post', 'translations', [
            'get_callback' => [$this, 'get_post_translations'],
            'schema' => [
                'description' => 'Traduções disponíveis do post',
                'type' => 'object',
                'context' => ['view', 'edit']
            ]
        ]);
    }
    
    /**
     * Registrar rotas específicas para idiomas
     */
    public function register_language_routes() {
        // Rota para obter idiomas disponíveis
        register_rest_route(API_Manager::get_namespace(), '/languages', [
            'methods' => 'GET',
            'callback' => [$this, 'get_available_languages'],
            'permission_callback' => '__return_true'
        ]);
        
        // Rota para alternar idioma
        register_rest_route(API_Manager::get_namespace(), '/language/(?P<lang>[a-zA-Z-_]+)', [
            'methods' => 'POST',
            'callback' => [$this, 'switch_language'],
            'permission_callback' => '__return_true',
            'args' => [
                'lang' => [
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field'
                ]
            ]
        ]);
    }
    
    /**
     * Filtrar posts por idioma (WPML)
     */
    public function wpml_filter_posts_args($args, $request) {
        if (!$this->is_wpml_active()) {
            return $args;
        }
        
        $language = $request->get_param('lang') ?: $this->get_current_language();
        
        if ($language && $language !== 'all') {
            global $sitepress;
            $sitepress->switch_lang($language);
        }
        
        return $args;
    }
    
    /**
     * Filtrar posts por idioma (Polylang)
     */
    public function polylang_filter_posts_args($args, $request) {
        if (!$this->is_polylang_active()) {
            return $args;
        }
        
        $language = $request->get_param('lang') ?: $this->get_current_language();
        
        if ($language && $language !== 'all') {
            $args['lang'] = $language;
        }
        
        return $args;
    }
    
    /**
     * Adicionar informações de tradução (WPML)
     */
    public function wpml_add_translation_info($post_data, $post) {
        if (!$this->is_wpml_active()) {
            return $post_data;
        }
        
        $post_data['language'] = $this->get_post_language($post);
        $post_data['translations'] = $this->get_post_translations($post);
        
        return $post_data;
    }
    
    /**
     * Adicionar informações de tradução (Polylang)
     */
    public function polylang_add_translation_info($post_data, $post) {
        if (!$this->is_polylang_active()) {
            return $post_data;
        }
        
        $post_data['language'] = $this->get_post_language($post);
        $post_data['translations'] = $this->get_post_translations($post);
        
        return $post_data;
    }
    
    /**
     * Obter idioma do post
     */
    public function get_post_language($post) {
        if (is_numeric($post)) {
            $post_id = $post;
        } elseif (is_object($post)) {
            $post_id = $post->ID ?? $post['id'] ?? 0;
        } else {
            return $this->default_language;
        }
        
        if ($this->is_wpml_active()) {
            return apply_filters('wpml_post_language_details', null, $post_id)['language_code'] ?? $this->default_language;
        } elseif ($this->is_polylang_active()) {
            return pll_get_post_language($post_id) ?: $this->default_language;
        }
        
        return $this->default_language;
    }
    
    /**
     * Obter traduções do post
     */
    public function get_post_translations($post) {
        if (is_numeric($post)) {
            $post_id = $post;
        } elseif (is_object($post)) {
            $post_id = $post->ID ?? $post['id'] ?? 0;
        } else {
            return [];
        }
        
        $translations = [];
        
        if ($this->is_wpml_active()) {
            $wpml_translations = apply_filters('wpml_get_element_translations', null, $post_id, 'post_' . get_post_type($post_id));
            
            if ($wpml_translations) {
                foreach ($wpml_translations as $lang => $translation) {
                    if ($translation->element_id != $post_id) {
                        $translations[$lang] = [
                            'id' => $translation->element_id,
                            'language' => $lang,
                            'url' => get_permalink($translation->element_id)
                        ];
                    }
                }
            }
        } elseif ($this->is_polylang_active()) {
            $pll_translations = pll_get_post_translations($post_id);
            
            if ($pll_translations) {
                foreach ($pll_translations as $lang => $translation_id) {
                    if ($translation_id != $post_id) {
                        $translations[$lang] = [
                            'id' => $translation_id,
                            'language' => $lang,
                            'url' => get_permalink($translation_id)
                        ];
                    }
                }
            }
        }
        
        return $translations;
    }
    
    /**
     * Callback para endpoint de idiomas disponíveis
     */
    public function get_available_languages($request) {
        return rest_ensure_response([
            'languages' => $this->available_languages,
            'default' => $this->default_language,
            'current' => $this->get_current_language(),
            'plugin' => $this->active_plugin
        ]);
    }
    
    /**
     * Callback para alternar idioma
     */
    public function switch_language($request) {
        $language = $request->get_param('lang');
        
        if (!isset($this->available_languages[$language])) {
            return new WP_Error('invalid_language', 'Idioma não disponível', ['status' => 400]);
        }
        
        // Definir idioma na sessão ou cookie
        if ($this->is_wpml_active()) {
            global $sitepress;
            $sitepress->switch_lang($language);
        } elseif ($this->is_polylang_active()) {
            // Polylang gerencia isso automaticamente via URL
        }
        
        return rest_ensure_response([
            'success' => true,
            'language' => $language,
            'message' => sprintf('Idioma alterado para: %s', $this->available_languages[$language]['native_name'] ?? $language)
        ]);
    }
    
    /**
     * Obter idioma atual
     */
    public function get_current_language() {
        if ($this->is_wpml_active()) {
            return apply_filters('wpml_current_language', null);
        } elseif ($this->is_polylang_active()) {
            return pll_current_language();
        }
        
        return $this->default_language;
    }
    
    /**
     * Verificar se WPML está ativo
     */
    public function is_wpml_active() {
        return $this->active_plugin === 'wpml';
    }
    
    /**
     * Verificar se Polylang está ativo
     */
    public function is_polylang_active() {
        return $this->active_plugin === 'polylang';
    }
    
    /**
     * Verificar se há plugin de tradução ativo
     */
    public function has_translation_plugin() {
        return !empty($this->active_plugin);
    }
    
    /**
     * Obter nome do plugin ativo
     */
    public function get_active_plugin() {
        return $this->active_plugin;
    }
    
    /**
     * Obter todas as informações de tradução
     */
    public function get_translation_info() {
        return [
            'plugin' => $this->active_plugin,
            'languages' => $this->available_languages,
            'default_language' => $this->default_language,
            'current_language' => $this->get_current_language()
        ];
    }
    
    /**
     * Adicionar metadados de tradução aos posts
     */
    public function add_translation_metadata($post_data, $post) {
        if (!$this->has_translation_plugin()) {
            return $post_data;
        }
        
        $post_data['language_info'] = [
            'current' => $this->get_post_language($post),
            'available_translations' => $this->get_post_translations($post),
            'is_default_language' => $this->get_post_language($post) === $this->default_language
        ];
        
        return $post_data;
    }
    
    /**
     * Filtrar consulta de posts por idioma
     */
    public function filter_posts_by_language($query_args, $request) {
        if (!$this->has_translation_plugin()) {
            return $query_args;
        }
        
        $language = $request->get_param('lang');
        
        if ($language && $language !== 'all') {
            if ($this->is_wpml_active()) {
                // WPML irá filtrar automaticamente
                global $sitepress;
                $sitepress->switch_lang($language);
            } elseif ($this->is_polylang_active()) {
                $query_args['lang'] = $language;
            }
        }
        
        return $query_args;
    }
}

// Inicializar suporte a tradução
Fuerza_Translation_Support::get_instance();
