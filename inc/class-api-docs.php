<?php
/**
 * Sistema de Documentação Automática da API
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

class Fuerza_API_Docs {
    
    /**
     * Instância singleton
     */
    private static $instance = null;
    
    /**
     * Configurações
     */
    private $config;
    
    /**
     * Rotas documentadas
     */
    private $documented_routes = [];
    
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
        $this->setup_config();
        $this->setup_hooks();
        $this->register_documentation_routes();
    }
    
    /**
     * Configurar sistema
     */
    private function setup_config() {
        $this->config = [
            'enabled' => get_option('fuerza_api_docs_enabled', true),
            'public_access' => get_option('fuerza_api_docs_public', false),
            'theme_color' => get_option('fuerza_api_docs_theme_color', '#667eea'),
            'show_examples' => get_option('fuerza_api_docs_show_examples', true),
            'show_schemas' => get_option('fuerza_api_docs_show_schemas', true),
        ];
    }
    
    /**
     * Configurar hooks
     */
    private function setup_hooks() {
        add_action('rest_api_init', [$this, 'collect_route_documentation'], 999);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_docs_assets']);
    }
    
    /**
     * Registrar rotas de documentação
     */
    private function register_documentation_routes() {
        $api_manager = API_Manager::get_instance();
        
        // Rota para documentação em JSON (OpenAPI)
        $api_manager->add_route(
            '/docs/openapi',
            'GET',
            [$this, 'get_openapi_spec'],
            [],
            [$this, 'check_docs_permission']
        );
        
        // Rota para documentação HTML
        $api_manager->add_route(
            '/docs',
            'GET',
            [$this, 'get_html_documentation'],
            [],
            [$this, 'check_docs_permission']
        );
        
        // Rota para esquemas de dados
        $api_manager->add_route(
            '/docs/schemas',
            'GET',
            [$this, 'get_data_schemas'],
            [],
            [$this, 'check_docs_permission']
        );
    }
    
    /**
     * Verificar permissões de acesso à documentação
     */
    public function check_docs_permission($request) {
        if ($this->config['public_access']) {
            return true;
        }
        
        return current_user_can('manage_options');
    }
    
    /**
     * Coletar documentação das rotas
     */
    public function collect_route_documentation() {
        if (!$this->config['enabled']) {
            return;
        }
        
        global $wp_rest_server;
        
        if (!$wp_rest_server) {
            return;
        }
        
        $routes = $wp_rest_server->get_routes();
        $namespace = API_Manager::get_namespace();
        
        foreach ($routes as $route => $endpoints) {
            // Filtrar apenas rotas do nosso namespace
            if (strpos($route, '/' . $namespace) === 0) {
                $this->documented_routes[$route] = $this->analyze_route($route, $endpoints);
            }
        }
    }
    
    /**
     * Analisar rota para documentação
     */
    private function analyze_route($route, $endpoints) {
        $route_doc = [
            'path' => $route,
            'methods' => [],
            'description' => '',
            'tags' => [],
        ];
        
        foreach ($endpoints as $endpoint) {
            foreach ($endpoint['methods'] as $method => $enabled) {
                if (!$enabled) continue;
                
                $method_doc = [
                    'method' => $method,
                    'description' => $this->extract_method_description($route, $method),
                    'parameters' => $this->extract_parameters($endpoint),
                    'responses' => $this->generate_response_examples($route, $method),
                    'tags' => $this->extract_tags($route),
                ];
                
                $route_doc['methods'][$method] = $method_doc;
            }
        }
        
        return $route_doc;
    }
    
    /**
     * Extrair descrição do método
     */
    private function extract_method_description($route, $method) {
        // Mapear rotas para descrições
        $descriptions = [
            'GET' => [
                '/ping' => 'Verificar status da API',
                '/eventos' => 'Listar eventos disponíveis',
                '/eventos/(\d+)' => 'Obter evento específico por ID',
                '/produtos' => 'Listar produtos disponíveis',
                '/produtos/(\d+)' => 'Obter produto específico por ID',
            ],
            'POST' => [
                '/eventos' => 'Criar novo evento',
                '/produtos' => 'Criar novo produto',
            ],
            'PUT' => [
                '/eventos/(\d+)' => 'Atualizar evento existente',
                '/produtos/(\d+)' => 'Atualizar produto existente',
            ],
            'DELETE' => [
                '/eventos/(\d+)' => 'Deletar evento',
                '/produtos/(\d+)' => 'Deletar produto',
            ],
        ];
        
        if (isset($descriptions[$method])) {
            foreach ($descriptions[$method] as $pattern => $description) {
                if (preg_match('#' . $pattern . '#', $route)) {
                    return $description;
                }
            }
        }
        
        return "Endpoint {$method} para {$route}";
    }
    
    /**
     * Extrair parâmetros
     */
    private function extract_parameters($endpoint) {
        $parameters = [];
        
        if (isset($endpoint['args'])) {
            foreach ($endpoint['args'] as $param_name => $param_config) {
                $parameter = [
                    'name' => $param_name,
                    'type' => $this->detect_parameter_type($param_config),
                    'required' => $param_config['required'] ?? false,
                    'description' => $param_config['description'] ?? '',
                    'default' => $param_config['default'] ?? null,
                    'example' => $this->generate_parameter_example($param_name, $param_config),
                ];
                
                if (isset($param_config['enum'])) {
                    $parameter['enum'] = $param_config['enum'];
                }
                
                $parameters[] = $parameter;
            }
        }
        
        return $parameters;
    }
    
    /**
     * Detectar tipo de parâmetro
     */
    private function detect_parameter_type($param_config) {
        if (isset($param_config['type'])) {
            return $param_config['type'];
        }
        
        if (isset($param_config['sanitize_callback'])) {
            $callback = $param_config['sanitize_callback'];
            
            // Verificar se é uma string antes de usar strpos
            if ($callback === 'absint' || (is_string($callback) && strpos($callback, 'int') !== false)) {
                return 'integer';
            }
            
            if ($callback === 'sanitize_email') {
                return 'string';
            }
            
            // Se for um closure/callable mas não uma string, tentar detectar o tipo
            if (is_callable($callback) && !is_string($callback)) {
                // Para closures, retornar tipo genérico
                return 'mixed';
            }
        }
        
        return 'string';
    }
    
    /**
     * Gerar exemplo de parâmetro
     */
    private function generate_parameter_example($param_name, $param_config) {
        $examples = [
            'id' => 123,
            'page' => 1,
            'per_page' => 10,
            'orderby' => 'date',
            'order' => 'DESC',
            'categoria' => 'tecnologia',
            'tag' => 'wordpress',
            'busca' => 'evento especial',
            'status' => 'publish',
        ];
        
        if (isset($examples[$param_name])) {
            return $examples[$param_name];
        }
        
        if (isset($param_config['default'])) {
            return $param_config['default'];
        }
        
        return null;
    }
    
    /**
     * Gerar exemplos de resposta
     */
    private function generate_response_examples($route, $method) {
        $responses = [
            '200' => [
                'description' => 'Sucesso',
                'example' => $this->generate_success_example($route, $method),
            ],
            '400' => [
                'description' => 'Erro de validação',
                'example' => [
                    'code' => 'rest_invalid_param',
                    'message' => 'Parâmetro inválido.',
                    'data' => ['status' => 400]
                ],
            ],
            '404' => [
                'description' => 'Não encontrado',
                'example' => [
                    'code' => 'rest_not_found',
                    'message' => 'Recurso não encontrado.',
                    'data' => ['status' => 404]
                ],
            ],
            '429' => [
                'description' => 'Rate limit excedido',
                'example' => [
                    'code' => 'rate_limit_exceeded',
                    'message' => 'Rate limit exceeded. Please try again later.',
                    'data' => ['status' => 429]
                ],
            ],
        ];
        
        return $responses;
    }
    
    /**
     * Gerar exemplo de resposta de sucesso
     */
    private function generate_success_example($route, $method) {
        if (strpos($route, '/ping') !== false) {
            return [
                'status' => 'ok',
                'message' => 'API is working',
                'timestamp' => '2024-01-01T12:00:00Z',
                'version' => '1.0'
            ];
        }
        
        if (strpos($route, '/eventos') !== false) {
            if (preg_match('/eventos\/\d+/', $route)) {
                // Evento específico
                return [
                    'id' => 123,
                    'titulo' => 'Evento Exemplo',
                    'conteudo' => 'Descrição do evento...',
                    'data_publicacao' => '2024-01-01T12:00:00Z',
                    'imagem_destacada' => [
                        'url_completa' => 'https://exemplo.com/imagem.jpg',
                        'alt' => 'Imagem do evento'
                    ]
                ];
            } else {
                // Lista de eventos
                return [
                    'eventos' => [
                        [
                            'id' => 123,
                            'titulo' => 'Evento Exemplo',
                            'resumo' => 'Resumo do evento...',
                            'data_publicacao' => '2024-01-01T12:00:00Z'
                        ]
                    ],
                    'paginacao' => [
                        'total_eventos' => 1,
                        'total_paginas' => 1,
                        'pagina_atual' => 1
                    ]
                ];
            }
        }
        
        return ['message' => 'Success'];
    }
    
    /**
     * Extrair tags da rota
     */
    private function extract_tags($route) {
        $tags = [];
        
        if (strpos($route, '/eventos') !== false) {
            $tags[] = 'Eventos';
        } elseif (strpos($route, '/produtos') !== false) {
            $tags[] = 'Produtos';
        } elseif (strpos($route, '/ping') !== false) {
            $tags[] = 'Sistema';
        } elseif (strpos($route, '/docs') !== false) {
            $tags[] = 'Documentação';
        }
        
        return $tags;
    }
    
    /**
     * Obter especificação OpenAPI
     */
    public function get_openapi_spec($request) {
        $spec = [
            'openapi' => '3.0.0',
            'info' => [
                'title' => get_bloginfo('name') . ' API',
                'description' => get_bloginfo('description') ?: 'API REST do WordPress',
                'version' => '1.0.0',
                'contact' => [
                    'name' => 'Fuerza Studio',
                    'url' => 'https://fuerzastudio.com',
                ],
            ],
            'servers' => [
                [
                    'url' => home_url('/wp-json/' . API_Manager::get_namespace()),
                    'description' => 'Servidor de produção',
                ]
            ],
            'paths' => $this->generate_openapi_paths(),
            'components' => [
                'schemas' => $this->generate_openapi_schemas(),
            ],
        ];
        
        return rest_ensure_response($spec);
    }
    
    /**
     * Gerar paths para OpenAPI
     */
    private function generate_openapi_paths() {
        $paths = [];
        
        foreach ($this->documented_routes as $route => $route_doc) {
            $clean_path = str_replace('/' . API_Manager::get_namespace(), '', $route);
            $clean_path = preg_replace('/\(\?\P\<(\w+)\>[^)]+\)/', '{\1}', $clean_path);
            
            foreach ($route_doc['methods'] as $method => $method_doc) {
                $method_lower = strtolower($method);
                
                $paths[$clean_path][$method_lower] = [
                    'summary' => $method_doc['description'],
                    'tags' => $method_doc['tags'],
                    'parameters' => $this->format_openapi_parameters($method_doc['parameters']),
                    'responses' => $this->format_openapi_responses($method_doc['responses']),
                ];
            }
        }
        
        return $paths;
    }
    
    /**
     * Formatar parâmetros para OpenAPI
     */
    private function format_openapi_parameters($parameters) {
        $formatted = [];
        
        foreach ($parameters as $param) {
            $formatted[] = [
                'name' => $param['name'],
                'in' => 'query',
                'required' => $param['required'],
                'description' => $param['description'],
                'schema' => [
                    'type' => $param['type'],
                    'default' => $param['default'],
                    'example' => $param['example'],
                ],
            ];
        }
        
        return $formatted;
    }
    
    /**
     * Formatar respostas para OpenAPI
     */
    private function format_openapi_responses($responses) {
        $formatted = [];
        
        foreach ($responses as $code => $response) {
            $formatted[$code] = [
                'description' => $response['description'],
                'content' => [
                    'application/json' => [
                        'example' => $response['example'],
                    ],
                ],
            ];
        }
        
        return $formatted;
    }
    
    /**
     * Gerar esquemas para OpenAPI
     */
    private function generate_openapi_schemas() {
        return [
            'Evento' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer', 'example' => 123],
                    'titulo' => ['type' => 'string', 'example' => 'Evento Exemplo'],
                    'conteudo' => ['type' => 'string', 'example' => 'Descrição do evento...'],
                    'data_publicacao' => ['type' => 'string', 'format' => 'date-time'],
                    'imagem_destacada' => ['$ref' => '#/components/schemas/Imagem'],
                ],
            ],
            'Produto' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer', 'example' => 456],
                    'titulo' => ['type' => 'string', 'example' => 'Produto Exemplo'],
                    'conteudo' => ['type' => 'string', 'example' => 'Descrição do produto...'],
                    'preco' => ['type' => 'number', 'example' => 99.99],
                ],
            ],
            'Imagem' => [
                'type' => 'object',
                'properties' => [
                    'url_completa' => ['type' => 'string', 'format' => 'uri'],
                    'alt' => ['type' => 'string'],
                    'largura' => ['type' => 'integer'],
                    'altura' => ['type' => 'integer'],
                ],
            ],
            'Erro' => [
                'type' => 'object',
                'properties' => [
                    'code' => ['type' => 'string'],
                    'message' => ['type' => 'string'],
                    'data' => ['type' => 'object'],
                ],
            ],
        ];
    }
    
    /**
     * Obter documentação HTML
     */
    public function get_html_documentation($request) {
        $html = $this->generate_html_docs();
        
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
        exit;
    }
    
    /**
     * Gerar documentação HTML
     */
    private function generate_html_docs() {
        $site_name = get_bloginfo('name');
        $api_url = home_url('/wp-json/' . API_Manager::get_namespace());
        $openapi_url = $api_url . '/docs/openapi';
        
        return '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentação da API - ' . esc_html($site_name) . '</title>
    <script src="https://unpkg.com/swagger-ui-dist@4.15.5/swagger-ui-bundle.js"></script>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@4.15.5/swagger-ui.css" />
    <style>
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .swagger-ui .topbar { background-color: ' . $this->config['theme_color'] . '; }
        .swagger-ui .topbar .download-url-wrapper { display: none; }
        .custom-header {
            background: linear-gradient(135deg, ' . $this->config['theme_color'] . ', #764ba2);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .custom-header h1 { margin: 0; font-size: 2.5rem; }
        .custom-header p { margin: 0.5rem 0 0; opacity: 0.9; }
    </style>
</head>
<body>
    <div class="custom-header">
        <h1>📡 API REST</h1>
        <p>' . esc_html($site_name) . '</p>
    </div>
    
    <div id="swagger-ui"></div>
    
    <script>
        SwaggerUIBundle({
            url: "' . esc_url($openapi_url) . '",
            dom_id: "#swagger-ui",
            deepLinking: true,
            presets: [
                SwaggerUIBundle.presets.apis,
                SwaggerUIBundle.presets.standalone
            ],
            plugins: [
                SwaggerUIBundle.plugins.DownloadUrl
            ],
            layout: "StandaloneLayout",
            tryItOutEnabled: true,
            supportedSubmitMethods: ["get", "post", "put", "delete"],
            docExpansion: "list",
            defaultModelsExpandDepth: 1,
            defaultModelExpandDepth: 1
        });
    </script>
</body>
</html>';
    }
    
    /**
     * Obter esquemas de dados
     */
    public function get_data_schemas($request) {
        $schemas = $this->generate_openapi_schemas();
        return rest_ensure_response($schemas);
    }
    
    /**
     * Adicionar menu no admin
     */
    public function add_admin_menu() {
        add_management_page(
            'Documentação da API',
            'API Docs',
            'manage_options',
            'fuerza-api-docs',
            [$this, 'render_admin_page']
        );
    }
    
    /**
     * Renderizar página do admin
     */
    public function render_admin_page() {
        $api_docs_url = home_url('/wp-json/' . API_Manager::get_namespace() . '/docs');
        $openapi_url = home_url('/wp-json/' . API_Manager::get_namespace() . '/docs/openapi');
        
        echo '<div class="wrap">';
        echo '<h1>Documentação da API</h1>';
        echo '<div class="card">';
        echo '<h2>Links Úteis</h2>';
        echo '<p><a href="' . esc_url($api_docs_url) . '" target="_blank" class="button button-primary">📖 Ver Documentação</a></p>';
        echo '<p><a href="' . esc_url($openapi_url) . '" target="_blank" class="button">📋 Especificação OpenAPI</a></p>';
        echo '</div>';
        
        echo '<div class="card">';
        echo '<h2>Rotas Disponíveis</h2>';
        echo '<ul>';
        foreach ($this->documented_routes as $route => $route_doc) {
            $methods = implode(', ', array_keys($route_doc['methods']));
            echo '<li><strong>' . esc_html($route) . '</strong> - ' . esc_html($methods) . '</li>';
        }
        echo '</ul>';
        echo '</div>';
        echo '</div>';
    }
    
    /**
     * Enfileirar assets da documentação
     */
    public function enqueue_docs_assets() {
        // Assets específicos podem ser adicionados aqui se necessário
    }
    
    /**
     * Configurações
     */
    public static function enable($enabled = true) {
        update_option('fuerza_api_docs_enabled', $enabled);
        self::get_instance()->config['enabled'] = $enabled;
    }
    
    public static function set_public_access($public = false) {
        update_option('fuerza_api_docs_public', $public);
        self::get_instance()->config['public_access'] = $public;
    }
}

// Inicializar documentação se habilitada
if (get_option('fuerza_api_docs_enabled', true)) {
    Fuerza_API_Docs::get_instance();
}
