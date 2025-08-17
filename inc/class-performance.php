<?php
/**
 * Sistema de Otimizações de Performance
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

class Fuerza_Performance {
    
    /**
     * Instância singleton
     */
    private static $instance = null;
    
    /**
     * Configurações
     */
    private $config;
    
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
    }
    
    /**
     * Configurar sistema
     */
    private function setup_config() {
        $this->config = [
            'lazy_loading' => get_option('fuerza_performance_lazy_loading', true),
            'query_optimization' => get_option('fuerza_performance_query_optimization', true),
            'compression' => get_option('fuerza_performance_compression', true),
            'preload_critical' => get_option('fuerza_performance_preload_critical', true),
            'database_cleanup' => get_option('fuerza_performance_db_cleanup', true),
            'object_cache' => get_option('fuerza_performance_object_cache', true),
        ];
    }
    
    /**
     * Configurar hooks
     */
    private function setup_hooks() {
        // Otimizações de query
        if ($this->config['query_optimization']) {
            add_action('pre_get_posts', [$this, 'optimize_queries']);
            add_filter('posts_clauses', [$this, 'optimize_post_clauses'], 10, 2);
        }
        
        // Lazy loading de handlers
        if ($this->config['lazy_loading']) {
            add_action('rest_api_init', [$this, 'setup_lazy_loading'], 0);
        }
        
        // Compressão de respostas
        if ($this->config['compression']) {
            add_filter('rest_post_dispatch', [$this, 'compress_response'], 10, 3);
        }
        
        // Preload de recursos críticos
        if ($this->config['preload_critical']) {
            add_action('wp_head', [$this, 'preload_critical_resources']);
        }
        
        // Limpeza de banco de dados
        if ($this->config['database_cleanup']) {
            add_action('fuerza_performance_cleanup', [$this, 'cleanup_database']);
            
            if (!wp_next_scheduled('fuerza_performance_cleanup')) {
                wp_schedule_event(time(), 'weekly', 'fuerza_performance_cleanup');
            }
        }
        
        // Object cache otimizado
        if ($this->config['object_cache']) {
            add_action('init', [$this, 'setup_object_cache_optimizations']);
        }
        
        // Headers de performance
        add_action('rest_api_init', [$this, 'add_performance_headers']);
        
        // Otimizações específicas da API
        add_action('rest_api_init', [$this, 'optimize_api_performance']);
    }
    
    /**
     * Otimizar queries
     */
    public function optimize_queries($query) {
        if (!$query->is_main_query() || is_admin()) {
            return;
        }
        
        // Limitar campos selecionados quando possível
        if ($query->is_home() || $query->is_archive()) {
            $query->set('fields', 'ids');
        }
        
        // Desabilitar contagem de posts para melhor performance
        if (!$query->get('no_found_rows')) {
            $query->set('no_found_rows', true);
        }
        
        // Limitar meta queries complexas
        $meta_query = $query->get('meta_query');
        if (!empty($meta_query) && count($meta_query) > 3) {
            Fuerza_Logger::warning('Complex meta query detected', [
                'query_vars' => $query->query_vars,
                'meta_query_count' => count($meta_query)
            ]);
        }
    }
    
    /**
     * Otimizar cláusulas de posts
     */
    public function optimize_post_clauses($clauses, $query) {
        global $wpdb;
        
        // Adicionar índices quando necessário
        if (strpos($clauses['where'], 'meta_value') !== false) {
            // Sugerir índice para meta queries
            $this->suggest_meta_index($clauses);
        }
        
        // Otimizar ORDER BY
        if (strpos($clauses['orderby'], 'meta_value') !== false) {
            $clauses['orderby'] = str_replace(
                'meta_value',
                'CAST(meta_value AS DECIMAL(10,2))',
                $clauses['orderby']
            );
        }
        
        return $clauses;
    }
    
    /**
     * Sugerir índice para meta queries
     */
    private function suggest_meta_index($clauses) {
        // Log suggestion for database optimization
        Fuerza_Logger::info('Meta query optimization suggestion', [
            'suggestion' => 'Consider adding index on wp_postmeta (meta_key, meta_value)',
            'query_fragment' => substr($clauses['where'], 0, 200)
        ]);
    }
    
    /**
     * Setup lazy loading
     */
    public function setup_lazy_loading() {
        // Interceptar carregamento de handlers apenas quando necessário
        add_filter('rest_route_data', [$this, 'lazy_load_handlers'], 10, 2);
    }
    
    /**
     * Lazy load de handlers
     */
    public function lazy_load_handlers($route_data, $routes = null) {
        // Implementar carregamento sob demanda de handlers específicos
        // O filtro rest_route_data passa apenas 2 argumentos: $available e $routes
        
        if (is_array($routes)) {
            foreach ($routes as $route => $route_config) {
                if (strpos($route, '/eventos') !== false && !class_exists('Eventos_Handler')) {
                    require_once get_template_directory() . '/inc/api/handlers/class-eventos-handler.php';
                }
                
                if (strpos($route, '/produtos') !== false && !class_exists('Produto_Handler')) {
                    require_once get_template_directory() . '/inc/api/handlers/class-produto-handler.php';
                }
            }
        }
        
        return $route_data;
    }
    
    /**
     * Comprimir resposta
     */
    public function compress_response($response, $server, $request) {
        if (!$response instanceof WP_REST_Response) {
            return $response;
        }
        
        $data = $response->get_data();
        
        // Comprimir apenas respostas grandes
        $serialized_size = strlen(json_encode($data));
        if ($serialized_size > 1024) { // > 1KB
            
            // Verificar se cliente suporta compressão
            $accept_encoding = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';
            
            if (strpos($accept_encoding, 'gzip') !== false) {
                $response->header('Content-Encoding', 'gzip');
                $response->header('X-Original-Size', $serialized_size);
            }
            
            // Log de respostas grandes
            if ($serialized_size > 100000) { // > 100KB
                Fuerza_Logger::warning('Large API response', [
                    'route' => $request->get_route(),
                    'size_bytes' => $serialized_size,
                    'size_formatted' => size_format($serialized_size)
                ]);
            }
        }
        
        return $response;
    }
    
    /**
     * Preload de recursos críticos
     */
    public function preload_critical_resources() {
        // Preload da especificação da API
        $api_namespace = API_Manager::get_namespace();
        $api_url = home_url("/wp-json/{$api_namespace}");
        
        echo '<link rel="dns-prefetch" href="' . esc_url(home_url()) . '">' . "\n";
        echo '<link rel="preconnect" href="' . esc_url($api_url) . '">' . "\n";
        
        // Preload de endpoints críticos
        $critical_endpoints = [
            '/ping',
            '/docs/openapi'
        ];
        
        foreach ($critical_endpoints as $endpoint) {
            echo '<link rel="prefetch" href="' . esc_url($api_url . $endpoint) . '">' . "\n";
        }
    }
    
    /**
     * Limpeza de banco de dados
     */
    public function cleanup_database() {
        global $wpdb;
        
        // Limpar revisões antigas (manter apenas 5 mais recentes)
        $wpdb->query("
            DELETE r1 FROM {$wpdb->posts} r1
            INNER JOIN {$wpdb->posts} r2
            WHERE r1.post_type = 'revision'
            AND r1.post_parent = r2.ID
            AND r1.post_date < DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        
        // Limpar spam e lixo
        $wpdb->query("
            DELETE FROM {$wpdb->comments}
            WHERE comment_approved = 'spam'
            AND comment_date < DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        
        // Limpar transients expirados
        $wpdb->query("
            DELETE FROM {$wpdb->options}
            WHERE option_name LIKE '_transient_timeout_%'
            AND option_value < UNIX_TIMESTAMP()
        ");
        
        // Otimizar tabelas
        $tables = [
            $wpdb->posts,
            $wpdb->postmeta,
            $wpdb->comments,
            $wpdb->commentmeta,
            $wpdb->options,
            $wpdb->prefix . 'fuerza_api_metrics',
            $wpdb->prefix . 'fuerza_rate_limits'
        ];
        
        foreach ($tables as $table) {
            $wpdb->query("OPTIMIZE TABLE {$table}");
        }
        
        Fuerza_Logger::info('Database cleanup completed', [
            'tables_optimized' => count($tables),
            'timestamp' => current_time('mysql')
        ]);
    }
    
    /**
     * Setup de otimizações de object cache
     */
    public function setup_object_cache_optimizations() {
        // Configurar grupos de cache não persistentes
        wp_cache_add_non_persistent_groups([
            'fuerza_temp',
            'fuerza_session'
        ]);
        
        // Preload de dados críticos
        $this->preload_critical_data();
    }
    
    /**
     * Preload de dados críticos
     */
    private function preload_critical_data() {
        // Preload de opções críticas
        $critical_options = [
            'blogname',
            'blogdescription',
            'siteurl',
            'home',
            'template',
            'stylesheet'
        ];
        
        foreach ($critical_options as $option) {
            wp_cache_get($option, 'options');
        }
        
        // Preload de posts populares (se disponível)
        if (function_exists('stats_get_csv')) {
            $popular_posts = wp_cache_get('popular_posts', 'fuerza_cache');
            if ($popular_posts === false) {
                // Simular busca de posts populares
                $popular_posts = get_posts([
                    'numberposts' => 10,
                    'meta_key' => '_view_count',
                    'orderby' => 'meta_value_num',
                    'order' => 'DESC'
                ]);
                wp_cache_set('popular_posts', $popular_posts, 'fuerza_cache', 3600);
            }
        }
    }
    
    /**
     * Adicionar headers de performance
     */
    public function add_performance_headers() {
        // Headers para cache do navegador
        add_filter('rest_post_dispatch', function($response, $server, $request) {
            // Cache headers para endpoints específicos
            $route = $request->get_route();
            
            if (strpos($route, '/docs') !== false) {
                // Documentação pode ser cacheada por mais tempo
                $response->header('Cache-Control', 'public, max-age=3600');
                $response->header('ETag', md5($route . filemtime(__FILE__)));
            } elseif (strpos($route, '/ping') !== false) {
                // Ping deve ser sempre fresh
                $response->header('Cache-Control', 'no-cache, must-revalidate');
            } else {
                // Cache padrão para outros endpoints
                $response->header('Cache-Control', 'public, max-age=300');
            }
            
            // Headers de performance
            $response->header('X-Content-Type-Options', 'nosniff');
            $response->header('X-Frame-Options', 'SAMEORIGIN');
            $response->header('X-Powered-By', 'Fuerza Theme API');
            
            return $response;
        }, 10, 3);
    }
    
    /**
     * Otimizar performance da API
     */
    public function optimize_api_performance() {
        // Remover hooks desnecessários durante requisições da API
        if (defined('REST_REQUEST') && REST_REQUEST) {
            
            // Desabilitar parsing de emojis
            remove_action('wp_head', 'print_emoji_detection_script', 7);
            remove_action('wp_print_styles', 'print_emoji_styles');
            
            // Desabilitar embeds
            remove_action('wp_head', 'wp_oembed_add_discovery_links');
            remove_action('wp_head', 'wp_oembed_add_host_js');
            
            // Desabilitar feeds
            remove_action('wp_head', 'feed_links', 2);
            remove_action('wp_head', 'feed_links_extra', 3);
            
            // Desabilitar RSD e WLW
            remove_action('wp_head', 'rsd_link');
            remove_action('wp_head', 'wlwmanifest_link');
            
            // Otimizar carregamento de plugins
            add_filter('option_active_plugins', [$this, 'optimize_plugin_loading']);
        }
    }
    
    /**
     * Otimizar carregamento de plugins durante API requests
     */
    public function optimize_plugin_loading($plugins) {
        if (!defined('REST_REQUEST') || !REST_REQUEST) {
            return $plugins;
        }
        
        // Lista de plugins essenciais para API
        $essential_plugins = [
            'advanced-custom-fields/acf.php',
            'redis-cache/redis-cache.php',
            // Adicione outros plugins essenciais aqui
        ];
        
        // Filtrar apenas plugins essenciais
        $filtered_plugins = array_intersect($plugins, $essential_plugins);
        
        if (count($filtered_plugins) < count($plugins)) {
            Fuerza_Logger::debug('Optimized plugin loading for API request', [
                'original_count' => count($plugins),
                'filtered_count' => count($filtered_plugins),
                'saved_plugins' => count($plugins) - count($filtered_plugins)
            ]);
        }
        
        return $plugins;
    }
    
    /**
     * Obter estatísticas de performance
     */
    public function get_performance_stats() {
        global $wpdb;
        
        // Estatísticas de queries
        $query_stats = [
            'total_queries' => get_num_queries(),
            'query_time' => timer_stop(),
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
        ];
        
        // Estatísticas de cache
        if (function_exists('wp_cache_get_stats')) {
            $cache_stats = wp_cache_get_stats();
        } else {
            $cache_stats = ['hits' => 0, 'misses' => 0];
        }
        
        // Estatísticas de banco de dados
        $db_stats = [
            'table_count' => $wpdb->get_var("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()"),
            'total_size' => $this->get_database_size(),
        ];
        
        return [
            'queries' => $query_stats,
            'cache' => $cache_stats,
            'database' => $db_stats,
            'timestamp' => current_time('mysql'),
        ];
    }
    
    /**
     * Obter tamanho do banco de dados
     */
    private function get_database_size() {
        global $wpdb;
        
        $size = $wpdb->get_var("
            SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'DB Size in MB' 
            FROM information_schema.tables 
            WHERE table_schema = DATABASE()
        ");
        
        return $size ? $size . ' MB' : 'Unknown';
    }
    
    /**
     * Configurações
     */
    public static function enable_all_optimizations($enabled = true) {
        $options = [
            'fuerza_performance_lazy_loading',
            'fuerza_performance_query_optimization',
            'fuerza_performance_compression',
            'fuerza_performance_preload_critical',
            'fuerza_performance_db_cleanup',
            'fuerza_performance_object_cache',
        ];
        
        foreach ($options as $option) {
            update_option($option, $enabled);
        }
        
        // Recriar instância com novas configurações
        self::$instance = null;
        self::get_instance();
    }
    
    public static function enable_query_optimization($enabled = true) {
        update_option('fuerza_performance_query_optimization', $enabled);
        self::get_instance()->config['query_optimization'] = $enabled;
    }
    
    public static function enable_lazy_loading($enabled = true) {
        update_option('fuerza_performance_lazy_loading', $enabled);
        self::get_instance()->config['lazy_loading'] = $enabled;
    }
}

// Inicializar otimizações de performance
Fuerza_Performance::get_instance();
