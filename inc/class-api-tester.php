<?php
/**
 * Sistema de teste das rotas da API
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

class Fuerza_API_Tester {
    
    /**
     * Instância singleton
     */
    private static $instance = null;
    
    /**
     * Lista de rotas disponíveis
     */
    private $available_routes = [];
    
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
        add_action('rest_api_init', [$this, 'collect_routes'], 999);
        add_action('wp_ajax_test_api_route', [$this, 'ajax_test_route']);
        add_action('wp_ajax_get_api_routes', [$this, 'ajax_get_routes']);
    }
    
    /**
     * Coletar todas as rotas da API do tema
     */
    public function collect_routes() {
        // Garantir que o REST API está inicializado
        if (!did_action('rest_api_init')) {
            do_action('rest_api_init');
        }
        
        try {
            $routes = rest_get_server()->get_routes();
            $theme_routes = [];
            
            foreach ($routes as $route => $handlers) {
                if (strpos($route, '/fuerza-theme/v1') === 0) {
                    $clean_route = str_replace('/fuerza-theme/v1', '', $route);
                    
                    foreach ($handlers as $handler) {
                        $theme_routes[] = [
                            'route' => $route,
                            'clean_route' => $clean_route,
                            'methods' => $handler['methods'],
                            'callback' => $this->get_callback_info($handler['callback']),
                            'args' => $handler['args'] ?? [],
                            'full_url' => home_url('/wp-json' . $route),
                            'description' => $this->generate_route_description($clean_route, $handler['methods'])
                        ];
                    }
                }
            }
            
            $this->available_routes = $theme_routes;
        } catch (Exception $e) {
            // Se houver erro, usar rotas padrão conhecidas
            $this->available_routes = $this->get_fallback_routes();
        }
    }
    
    /**
     * Rotas de fallback caso não consiga coletar dinamicamente
     */
    private function get_fallback_routes() {
        return [
            [
                'route' => '/fuerza-theme/v1/ping',
                'clean_route' => '/ping',
                'methods' => ['GET'],
                'callback' => 'Closure',
                'args' => [],
                'full_url' => home_url('/wp-json/fuerza-theme/v1/ping'),
                'description' => 'Testa se a API está funcionando (GET)'
            ],
            [
                'route' => '/fuerza-theme/v1/eventos',
                'clean_route' => '/eventos',
                'methods' => ['GET'],
                'callback' => 'Eventos_Handler::get_eventos',
                'args' => [],
                'full_url' => home_url('/wp-json/fuerza-theme/v1/eventos'),
                'description' => 'Lista todos os eventos disponíveis (GET)'
            ],
            [
                'route' => '/fuerza-theme/v1/Produtos',
                'clean_route' => '/Produtos',
                'methods' => ['GET'],
                'callback' => 'Produto_Handler::get_Produtos',
                'args' => [],
                'full_url' => home_url('/wp-json/fuerza-theme/v1/Produtos'),
                'description' => 'Lista todos os produtos disponíveis (GET)'
            ],
            [
                'route' => '/fuerza-theme/v1/Teams',
                'clean_route' => '/Teams',
                'methods' => ['GET'],
                'callback' => 'Team_Handler::get_Teams',
                'args' => [],
                'full_url' => home_url('/wp-json/fuerza-theme/v1/Teams'),
                'description' => 'Lista todos os teams disponíveis (GET)'
            ]
        ];
    }
    
    /**
     * Gerar descrição da rota
     */
    private function generate_route_description($route, $methods) {
        $descriptions = [
            '/ping' => 'Testa se a API está funcionando',
            '/eventos' => 'Lista todos os eventos disponíveis',
            '/eventos/(?P<id>\d+)' => 'Obtém um evento específico por ID',
            '/Produtos' => 'Lista todos os produtos disponíveis',
            '/Produtos/(?P<id>\d+)' => 'Obtém um produto específico por ID',
            '/Teams' => 'Lista todos os teams disponíveis',
            '/Teams/(?P<id>\d+)' => 'Obtém um team específico por ID'
        ];
        
        $method_text = is_array($methods) ? implode(', ', $methods) : $methods;
        $base_description = $descriptions[$route] ?? 'Endpoint da API';
        
        return "{$base_description} ({$method_text})";
    }
    
    /**
     * Obter informações do callback
     */
    private function get_callback_info($callback) {
        if (is_array($callback)) {
            if (is_string($callback[0])) {
                return $callback[0] . '::' . $callback[1];
            } else {
                return get_class($callback[0]) . '::' . $callback[1];
            }
        } elseif (is_string($callback)) {
            return $callback;
        } else {
            return 'Closure';
        }
    }
    
    /**
     * Testar uma rota específica
     */
    public function test_route($route, $method = 'GET', $params = []) {
        $start_time = microtime(true);
        
        // Preparar URL completa
        $url = home_url('/wp-json' . $route);
        
        // Adicionar parâmetros para GET
        if ($method === 'GET' && !empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        // Configurar argumentos da requisição
        $args = [
            'method' => $method,
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json',
            ]
        ];
        
        // Para métodos POST/PUT, adicionar dados no body
        if (in_array($method, ['POST', 'PUT', 'PATCH']) && !empty($params)) {
            $args['body'] = json_encode($params);
        }
        
        // Fazer a requisição
        $response = wp_remote_request($url, $args);
        $end_time = microtime(true);
        
        // Processar resposta
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'error' => $response->get_error_message(),
                'response_time' => round(($end_time - $start_time) * 1000, 2),
                'url' => $url
            ];
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $headers = wp_remote_retrieve_headers($response);
        
        return [
            'success' => $status_code >= 200 && $status_code < 300,
            'status_code' => $status_code,
            'response_time' => round(($end_time - $start_time) * 1000, 2),
            'body' => $body,
            'headers' => $headers->getAll(),
            'url' => $url,
            'parsed_body' => json_decode($body, true)
        ];
    }
    
    /**
     * Obter todas as rotas disponíveis
     */
    public function get_available_routes() {
        // Se não temos rotas coletadas, forçar coleta
        if (empty($this->available_routes)) {
            $this->collect_routes();
        }
        return $this->available_routes;
    }
    
    /**
     * AJAX: Testar rota
     */
    public function ajax_test_route() {
        check_ajax_referer('fuerza_api_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $route = sanitize_text_field($_POST['route'] ?? '');
        $method = sanitize_text_field($_POST['method'] ?? 'GET');
        $params = $_POST['params'] ?? [];
        
        // Sanitizar parâmetros
        $clean_params = [];
        if (is_array($params)) {
            foreach ($params as $key => $value) {
                $clean_params[sanitize_key($key)] = sanitize_text_field($value);
            }
        }
        
        $result = $this->test_route($route, $method, $clean_params);
        
        wp_send_json($result);
    }
    
    /**
     * AJAX: Obter rotas
     */
    public function ajax_get_routes() {
        check_ajax_referer('fuerza_api_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        wp_send_json_success($this->get_available_routes());
    }
    
    /**
     * Testar todas as rotas automaticamente
     */
    public function test_all_routes() {
        $results = [];
        
        foreach ($this->available_routes as $route_info) {
            $route = $route_info['route'];
            $methods = is_array($route_info['methods']) ? $route_info['methods'] : [$route_info['methods']];
            
            foreach ($methods as $method) {
                // Pular métodos que requerem dados específicos
                if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
                    continue;
                }
                
                $test_params = [];
                
                // Para rotas com ID, usar um ID de exemplo
                if (strpos($route, '(?P<id>') !== false) {
                    $test_route = str_replace('(?P<id>\d+)', '1', $route);
                } else {
                    $test_route = $route;
                }
                
                $result = $this->test_route($test_route, $method, $test_params);
                $results[] = [
                    'route' => $test_route,
                    'method' => $method,
                    'description' => $route_info['description'],
                    'result' => $result
                ];
            }
        }
        
        return $results;
    }
}

// Inicializar o testador
Fuerza_API_Tester::get_instance();
?>
