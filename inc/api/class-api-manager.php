<?php
/**
 * Gerenciador principal das rotas da API
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

class API_Manager {
    
    /**
     * Namespace base para todas as rotas
     */
    const NAMESPACE_BASE = 'fuerza-theme/v1';
    
    /**
     * Lista de rotas registradas
     */
    private $routes = [];
    
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
        add_action('rest_api_init', [$this, 'register_routes']);
        $this->load_route_handlers();
    }
    
    /**
     * Carrega todos os manipuladores de rota
     */
    private function load_route_handlers() {
        $routes_dir = get_template_directory() . '/inc/api/routes/';
        
        if (!is_dir($routes_dir)) {
            return;
        }
        
        $route_files = glob($routes_dir . '*.php');
        
        foreach ($route_files as $file) {
            require_once $file;
        }
    }
    
    /**
     * Registra uma nova rota
     */
    public function add_route($endpoint, $methods, $callback, $args = [], $permission_callback = null) {
        $this->routes[] = [
            'endpoint' => $endpoint,
            'methods' => $methods,
            'callback' => $callback,
            'args' => $args,
            'permission_callback' => $permission_callback
        ];
    }
    
    /**
     * Registra todas as rotas no WordPress
     */
    public function register_routes() {
        foreach ($this->routes as $route) {
            register_rest_route(
                self::NAMESPACE_BASE,
                $route['endpoint'],
                [
                    'methods' => $route['methods'],
                    'callback' => $route['callback'],
                    'args' => $route['args'],
                    'permission_callback' => $route['permission_callback'] ?? [$this, 'check_api_permissions']
                ]
            );
        }
    }
    
    /**
     * Verificar permissões da API
     */
    public function check_api_permissions($request) {
        // Para rotas públicas de leitura, permitir acesso básico
        if ($request->get_method() === 'GET') {
            return true;
        }
        
        // Para outras operações, exigir capacidades específicas
        return current_user_can('edit_posts');
    }
    
    /**
     * Permissão pública - sempre permite acesso
     */
    public function public_permissions($request) {
        return true;
    }
    
    /**
     * Obtém o namespace base
     */
    public static function get_namespace() {
        return self::NAMESPACE_BASE;
    }
}

// Inicializar o gerenciador
API_Manager::get_instance();
