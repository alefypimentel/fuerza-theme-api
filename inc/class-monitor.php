<?php
/**
 * Sistema de Monitoramento para o Fuerza Theme API
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

class Fuerza_Monitor {
    
    /**
     * Instância singleton
     */
    private static $instance = null;
    
    /**
     * Tempo de início da requisição
     */
    private $request_start_time;
    
    /**
     * Dados de performance
     */
    private $performance_data = [];
    
    /**
     * Configurações de monitoramento
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
        $this->request_start_time = microtime(true);
    }
    
    /**
     * Configurar sistema
     */
    private function setup_config() {
        $this->config = [
            'enabled' => get_option('fuerza_api_monitoring_enabled', true),
            'slow_query_threshold' => get_option('fuerza_api_slow_query_threshold', 1000), // ms
            'memory_threshold' => get_option('fuerza_api_memory_threshold', 64 * 1024 * 1024), // 64MB
            'track_user_agents' => get_option('fuerza_api_track_user_agents', true),
            'alert_email' => get_option('fuerza_api_alert_email', get_option('admin_email')),
        ];
    }
    
    /**
     * Configurar hooks
     */
    private function setup_hooks() {
        // Monitorar requisições da API
        add_action('rest_api_init', [$this, 'track_api_request_start']);
        add_filter('rest_post_dispatch', [$this, 'track_api_request_end'], 10, 3);
        
        // Monitorar queries lentas
        add_action('log_query_custom_data', [$this, 'track_slow_queries'], 10, 5);
        
        // Monitorar erros
        add_action('wp_ajax_nopriv_fuerza_api_error', [$this, 'track_api_error']);
        add_action('wp_ajax_fuerza_api_error', [$this, 'track_api_error']);
        
        // Cleanup de dados antigos
        add_action('fuerza_monitor_cleanup', [$this, 'cleanup_old_data']);
        
        if (!wp_next_scheduled('fuerza_monitor_cleanup')) {
            wp_schedule_event(time(), 'daily', 'fuerza_monitor_cleanup');
        }
    }
    
    /**
     * Rastrear início da requisição API
     */
    public function track_api_request_start() {
        if (!$this->config['enabled']) {
            return;
        }
        
        $this->request_start_time = microtime(true);
        $this->performance_data['memory_start'] = memory_get_usage(true);
        $this->performance_data['queries_start'] = get_num_queries();
    }
    
    /**
     * Rastrear fim da requisição API
     */
    public function track_api_request_end($response, $server, $request) {
        if (!$this->config['enabled']) {
            return $response;
        }
        
        $end_time = microtime(true);
        $execution_time = ($end_time - $this->request_start_time) * 1000; // em ms
        $memory_usage = memory_get_usage(true) - $this->performance_data['memory_start'];
        $memory_peak = memory_get_peak_usage(true);
        $queries_count = get_num_queries() - $this->performance_data['queries_start'];
        
        $route = $request->get_route();
        $method = $request->get_method();
        $status_code = $response->get_status();
        
        // Log da requisição
        Fuerza_Logger::api_request(
            $route,
            $method,
            $status_code,
            round($execution_time, 2),
            [
                'memory_usage' => $memory_usage,
                'memory_peak' => $memory_peak,
                'queries_count' => $queries_count,
                'user_agent' => $this->config['track_user_agents'] ? ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown') : null,
            ]
        );
        
        // Salvar métricas
        $this->save_performance_metrics([
            'route' => $route,
            'method' => $method,
            'status_code' => $status_code,
            'execution_time' => $execution_time,
            'memory_usage' => $memory_usage,
            'memory_peak' => $memory_peak,
            'queries_count' => $queries_count,
            'timestamp' => current_time('mysql'),
        ]);
        
        // Verificar alertas
        $this->check_performance_alerts($execution_time, $memory_peak, $queries_count);
        
        return $response;
    }
    
    /**
     * Rastrear queries lentas
     */
    public function track_slow_queries($query, $query_time, $query_callstack, $query_start, $query_data) {
        if (!$this->config['enabled']) {
            return;
        }
        
        $query_time_ms = $query_time * 1000;
        
        if ($query_time_ms > $this->config['slow_query_threshold']) {
            Fuerza_Logger::warning(
                "Slow Query Detected: {$query_time_ms}ms",
                [
                    'query' => $query,
                    'execution_time' => $query_time_ms,
                    'callstack' => $query_callstack,
                ]
            );
            
            // Salvar query lenta
            $this->save_slow_query([
                'query' => $query,
                'execution_time' => $query_time_ms,
                'callstack' => json_encode($query_callstack),
                'timestamp' => current_time('mysql'),
            ]);
        }
    }
    
    /**
     * Rastrear erros da API
     */
    public function track_api_error() {
        if (!$this->config['enabled']) {
            return;
        }
        
        $error_data = [
            'message' => sanitize_text_field($_POST['message'] ?? 'Unknown error'),
            'file' => sanitize_text_field($_POST['file'] ?? ''),
            'line' => absint($_POST['line'] ?? 0),
            'stack' => sanitize_textarea_field($_POST['stack'] ?? ''),
            'url' => esc_url_raw($_POST['url'] ?? ''),
            'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'),
            'timestamp' => current_time('mysql'),
        ];
        
        Fuerza_Logger::error(
            "API Error: {$error_data['message']}",
            $error_data
        );
        
        $this->save_error_log($error_data);
        
        wp_die();
    }
    
    /**
     * Salvar métricas de performance
     */
    private function save_performance_metrics($metrics) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'fuerza_api_metrics';
        
        // Criar tabela se não existe
        $this->create_metrics_table();
        
        $wpdb->insert($table_name, $metrics);
    }
    
    /**
     * Salvar query lenta
     */
    private function save_slow_query($query_data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'fuerza_slow_queries';
        
        // Criar tabela se não existe
        $this->create_slow_queries_table();
        
        $wpdb->insert($table_name, $query_data);
    }
    
    /**
     * Salvar log de erro
     */
    private function save_error_log($error_data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'fuerza_api_errors';
        
        // Criar tabela se não existe
        $this->create_errors_table();
        
        $wpdb->insert($table_name, $error_data);
    }
    
    /**
     * Criar tabela de métricas
     */
    public function create_metrics_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'fuerza_api_metrics';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            route varchar(255) NOT NULL,
            method varchar(10) NOT NULL,
            status_code int(3) NOT NULL,
            execution_time decimal(10,2) NOT NULL,
            memory_usage bigint(20) NOT NULL,
            memory_peak bigint(20) NOT NULL,
            queries_count int(5) NOT NULL,
            timestamp datetime NOT NULL,
            PRIMARY KEY (id),
            KEY route_method (route, method),
            KEY timestamp (timestamp),
            KEY execution_time (execution_time)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Criar tabela de queries lentas
     */
    public function create_slow_queries_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'fuerza_slow_queries';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            query longtext NOT NULL,
            execution_time decimal(10,2) NOT NULL,
            callstack longtext,
            timestamp datetime NOT NULL,
            PRIMARY KEY (id),
            KEY execution_time (execution_time),
            KEY timestamp (timestamp)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Criar tabela de erros
     */
    public function create_errors_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'fuerza_api_errors';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            message text NOT NULL,
            file varchar(500),
            line int(5),
            stack longtext,
            url varchar(500),
            user_agent varchar(500),
            timestamp datetime NOT NULL,
            PRIMARY KEY (id),
            KEY timestamp (timestamp),
            KEY message (message(100))
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Verificar alertas de performance
     */
    private function check_performance_alerts($execution_time, $memory_peak, $queries_count) {
        $alerts = [];
        
        // Verificar tempo de execução
        if ($execution_time > $this->config['slow_query_threshold']) {
            $alerts[] = "Slow API response: {$execution_time}ms";
        }
        
        // Verificar uso de memória
        if ($memory_peak > $this->config['memory_threshold']) {
            $memory_mb = round($memory_peak / 1024 / 1024, 2);
            $alerts[] = "High memory usage: {$memory_mb}MB";
        }
        
        // Verificar número de queries
        if ($queries_count > 20) {
            $alerts[] = "High query count: {$queries_count} queries";
        }
        
        // Enviar alertas se necessário
        if (!empty($alerts) && $this->config['alert_email']) {
            $this->send_performance_alert($alerts);
        }
    }
    
    /**
     * Enviar alerta de performance
     */
    private function send_performance_alert($alerts) {
        $subject = '[' . get_bloginfo('name') . '] API Performance Alert';
        $message = "Performance issues detected:\n\n";
        $message .= implode("\n", $alerts);
        $message .= "\n\nTime: " . current_time('Y-m-d H:i:s');
        $message .= "\nURL: " . ($_SERVER['REQUEST_URI'] ?? 'Unknown');
        
        wp_mail($this->config['alert_email'], $subject, $message);
    }
    
    /**
     * Obter estatísticas de performance
     */
    public function get_performance_stats($days = 7) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'fuerza_api_metrics';
        $date_limit = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total_requests,
                AVG(execution_time) as avg_response_time,
                MAX(execution_time) as max_response_time,
                AVG(memory_usage) as avg_memory_usage,
                MAX(memory_peak) as max_memory_usage,
                AVG(queries_count) as avg_queries_count,
                MAX(queries_count) as max_queries_count
            FROM {$table_name} 
            WHERE timestamp >= %s",
            $date_limit
        ), ARRAY_A);
        
        // Estatísticas por rota
        $routes_stats = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                route,
                COUNT(*) as requests,
                AVG(execution_time) as avg_time,
                MAX(execution_time) as max_time
            FROM {$table_name} 
            WHERE timestamp >= %s
            GROUP BY route
            ORDER BY requests DESC
            LIMIT 10",
            $date_limit
        ), ARRAY_A);
        
        // Códigos de status
        $status_codes = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                status_code,
                COUNT(*) as count
            FROM {$table_name} 
            WHERE timestamp >= %s
            GROUP BY status_code
            ORDER BY count DESC",
            $date_limit
        ), ARRAY_A);
        
        return [
            'overview' => $stats,
            'routes' => $routes_stats,
            'status_codes' => $status_codes,
            'period_days' => $days,
        ];
    }
    
    /**
     * Limpar dados antigos
     */
    public function cleanup_old_data() {
        global $wpdb;
        
        $retention_days = get_option('fuerza_api_monitoring_retention', 30);
        $date_limit = date('Y-m-d H:i:s', strtotime("-{$retention_days} days"));
        
        // Limpar métricas antigas
        $tables = [
            $wpdb->prefix . 'fuerza_api_metrics',
            $wpdb->prefix . 'fuerza_slow_queries',
            $wpdb->prefix . 'fuerza_api_errors',
        ];
        
        foreach ($tables as $table) {
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$table} WHERE timestamp < %s",
                $date_limit
            ));
        }
    }
    
    /**
     * Habilitar/desabilitar monitoramento
     */
    public static function enable($enabled = true) {
        update_option('fuerza_api_monitoring_enabled', $enabled);
        self::get_instance()->config['enabled'] = $enabled;
    }
    
    /**
     * Inicializar todas as tabelas de monitoramento
     */
    public static function initialize_database() {
        $instance = self::get_instance();
        $instance->create_metrics_table();
        $instance->create_slow_queries_table();
        $instance->create_errors_table();
    }
    
    /**
     * Método público para criar todas as tabelas
     */
    public function create_all_tables() {
        $this->create_metrics_table();
        $this->create_slow_queries_table();
        $this->create_errors_table();
    }
}

// Inicializar o monitor se necessário
if (get_option('fuerza_api_monitoring_enabled', true)) {
    Fuerza_Monitor::get_instance();
}
