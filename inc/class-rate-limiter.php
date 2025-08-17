<?php
/**
 * Sistema de Rate Limiting para o Fuerza Theme API
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

class Fuerza_Rate_Limiter {
    
    /**
     * Instância singleton
     */
    private static $instance = null;
    
    /**
     * Configurações de rate limiting
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
            'enabled' => get_option('fuerza_rate_limiting_enabled', false), // Desabilitado por padrão
            'requests_per_minute' => get_option('fuerza_rate_limit_per_minute', 60),
            'requests_per_hour' => get_option('fuerza_rate_limit_per_hour', 1000),
            'burst_limit' => get_option('fuerza_rate_limit_burst', 10),
            'whitelist_ips' => get_option('fuerza_rate_limit_whitelist', []),
            'blacklist_ips' => get_option('fuerza_rate_limit_blacklist', []),
            'block_duration' => get_option('fuerza_rate_limit_block_duration', 300), // 5 minutos
        ];
    }
    
    /**
     * Configurar hooks
     */
    private function setup_hooks() {
        add_filter('rest_pre_dispatch', [$this, 'check_rate_limit_for_our_api'], 10, 3);
        add_action('fuerza_rate_limit_cleanup', [$this, 'cleanup_old_records']);
        
        if (!wp_next_scheduled('fuerza_rate_limit_cleanup')) {
            wp_schedule_event(time(), 'hourly', 'fuerza_rate_limit_cleanup');
        }
    }
    
    /**
     * Verificar rate limit apenas para nossa API
     */
    public function check_rate_limit_for_our_api($result, $server, $request) {
        // Aplicar rate limiting apenas para rotas do nosso tema
        $route = $request->get_route();
        if (strpos($route, '/fuerza-theme/v1') !== 0) {
            return $result; // Não aplicar rate limiting para outras APIs
        }
        
        // Pular rate limiting para usuários administrativos logados
        if (is_user_logged_in() && current_user_can('manage_options')) {
            return $result;
        }
        
        $this->check_rate_limit();
        return $result;
    }
    
    /**
     * Verificar rate limit
     */
    public function check_rate_limit() {
        if (!$this->config['enabled']) {
            return;
        }
        
        $ip = $this->get_client_ip();
        
        // Verificar se IP está na blacklist
        if (in_array($ip, $this->config['blacklist_ips'])) {
            $this->block_request('IP blacklisted');
            return;
        }
        
        // Verificar se IP está na whitelist
        if (in_array($ip, $this->config['whitelist_ips'])) {
            return; // Permitir sem limites
        }
        
        // Verificar se IP está bloqueado temporariamente
        if ($this->is_ip_blocked($ip)) {
            $this->block_request('IP temporarily blocked due to rate limiting');
            return;
        }
        
        // Verificar limites de rate
        if (!$this->check_limits($ip)) {
            $this->block_ip($ip);
            $this->block_request('Rate limit exceeded');
            return;
        }
        
        // Registrar requisição
        $this->record_request($ip);
    }
    
    /**
     * Verificar limites
     */
    private function check_limits($ip) {
        $now = time();
        $minute_ago = $now - 60;
        $hour_ago = $now - 3600;
        
        // Contar requisições na última hora
        $hour_count = $this->get_request_count($ip, $hour_ago);
        if ($hour_count >= $this->config['requests_per_hour']) {
            Fuerza_Logger::warning("Rate limit exceeded (hour): {$hour_count} requests", [
                'ip' => $ip,
                'limit' => $this->config['requests_per_hour']
            ]);
            return false;
        }
        
        // Contar requisições no último minuto
        $minute_count = $this->get_request_count($ip, $minute_ago);
        if ($minute_count >= $this->config['requests_per_minute']) {
            Fuerza_Logger::warning("Rate limit exceeded (minute): {$minute_count} requests", [
                'ip' => $ip,
                'limit' => $this->config['requests_per_minute']
            ]);
            return false;
        }
        
        // Verificar burst limit (últimos 10 segundos)
        $burst_time = $now - 10;
        $burst_count = $this->get_request_count($ip, $burst_time);
        if ($burst_count >= $this->config['burst_limit']) {
            Fuerza_Logger::warning("Burst limit exceeded: {$burst_count} requests", [
                'ip' => $ip,
                'limit' => $this->config['burst_limit']
            ]);
            return false;
        }
        
        return true;
    }
    
    /**
     * Obter contagem de requisições
     */
    private function get_request_count($ip, $since_time) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'fuerza_rate_limits';
        $this->create_rate_limit_table();
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} 
             WHERE ip = %s AND timestamp >= %d",
            $ip,
            $since_time
        ));
        
        return (int) $count;
    }
    
    /**
     * Registrar requisição
     */
    private function record_request($ip) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'fuerza_rate_limits';
        $this->create_rate_limit_table();
        
        $wpdb->insert($table_name, [
            'ip' => $ip,
            'endpoint' => $_SERVER['REQUEST_URI'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'timestamp' => time()
        ]);
    }
    
    /**
     * Verificar se IP está bloqueado
     */
    private function is_ip_blocked($ip) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'fuerza_blocked_ips';
        $this->create_blocked_ips_table();
        
        $blocked_until = $wpdb->get_var($wpdb->prepare(
            "SELECT blocked_until FROM {$table_name} 
             WHERE ip = %s AND blocked_until > %d",
            $ip,
            time()
        ));
        
        return !empty($blocked_until);
    }
    
    /**
     * Bloquear IP temporariamente
     */
    private function block_ip($ip) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'fuerza_blocked_ips';
        $this->create_blocked_ips_table();
        
        $blocked_until = time() + $this->config['block_duration'];
        
        $wpdb->replace($table_name, [
            'ip' => $ip,
            'blocked_at' => time(),
            'blocked_until' => $blocked_until,
            'reason' => 'Rate limit exceeded'
        ]);
        
        Fuerza_Logger::warning("IP blocked temporarily", [
            'ip' => $ip,
            'blocked_until' => date('Y-m-d H:i:s', $blocked_until),
            'duration_minutes' => $this->config['block_duration'] / 60
        ]);
    }
    
    /**
     * Bloquear requisição
     */
    private function block_request($reason) {
        status_header(429);
        header('Content-Type: application/json');
        header('X-RateLimit-Limit: ' . $this->config['requests_per_minute']);
        header('X-RateLimit-Remaining: 0');
        header('X-RateLimit-Reset: ' . (time() + 60));
        header('Retry-After: 60');
        
        $response = [
            'code' => 'rate_limit_exceeded',
            'message' => 'Rate limit exceeded. Please try again later.',
            'data' => [
                'status' => 429,
                'reason' => $reason,
                'retry_after' => 60
            ]
        ];
        
        echo json_encode($response);
        exit;
    }
    
    /**
     * Obter IP do cliente
     */
    private function get_client_ip() {
        $ip_headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($ip_headers as $header) {
            if (isset($_SERVER[$header]) && !empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    
    /**
     * Criar tabela de rate limits
     */
    private function create_rate_limit_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'fuerza_rate_limits';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            ip varchar(45) NOT NULL,
            endpoint varchar(255),
            user_agent varchar(500),
            timestamp int(10) NOT NULL,
            PRIMARY KEY (id),
            KEY ip_timestamp (ip, timestamp),
            KEY timestamp (timestamp)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Criar tabela de IPs bloqueados
     */
    private function create_blocked_ips_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'fuerza_blocked_ips';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            ip varchar(45) NOT NULL,
            blocked_at int(10) NOT NULL,
            blocked_until int(10) NOT NULL,
            reason varchar(255),
            PRIMARY KEY (ip),
            KEY blocked_until (blocked_until)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Limpar registros antigos
     */
    public function cleanup_old_records() {
        global $wpdb;
        
        $cleanup_time = time() - 3600; // Limpar registros de mais de 1 hora
        
        // Limpar rate limits antigos
        $rate_table = $wpdb->prefix . 'fuerza_rate_limits';
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$rate_table} WHERE timestamp < %d",
            $cleanup_time
        ));
        
        // Limpar bloqueios expirados
        $blocked_table = $wpdb->prefix . 'fuerza_blocked_ips';
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$blocked_table} WHERE blocked_until < %d",
            time()
        ));
    }
    
    /**
     * Obter estatísticas de rate limiting
     */
    public function get_stats() {
        global $wpdb;
        
        $rate_table = $wpdb->prefix . 'fuerza_rate_limits';
        $blocked_table = $wpdb->prefix . 'fuerza_blocked_ips';
        
        $hour_ago = time() - 3600;
        
        // Requisições na última hora
        $hourly_requests = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$rate_table} WHERE timestamp >= %d",
            $hour_ago
        ));
        
        // IPs únicos na última hora
        $unique_ips = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT ip) FROM {$rate_table} WHERE timestamp >= %d",
            $hour_ago
        ));
        
        // IPs bloqueados atualmente
        $blocked_ips = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$blocked_table} WHERE blocked_until > %d",
            time()
        ));
        
        // Top IPs por requisições
        $top_ips = $wpdb->get_results($wpdb->prepare(
            "SELECT ip, COUNT(*) as requests 
             FROM {$rate_table} 
             WHERE timestamp >= %d 
             GROUP BY ip 
             ORDER BY requests DESC 
             LIMIT 10",
            $hour_ago
        ), ARRAY_A);
        
        return [
            'hourly_requests' => (int) $hourly_requests,
            'unique_ips' => (int) $unique_ips,
            'blocked_ips' => (int) $blocked_ips,
            'top_ips' => $top_ips,
            'config' => $this->config
        ];
    }
    
    /**
     * Configurações
     */
    public static function enable($enabled = true) {
        update_option('fuerza_rate_limiting_enabled', $enabled);
        self::get_instance()->config['enabled'] = $enabled;
    }
    
    public static function set_limits($per_minute = 60, $per_hour = 1000, $burst = 10) {
        update_option('fuerza_rate_limit_per_minute', $per_minute);
        update_option('fuerza_rate_limit_per_hour', $per_hour);
        update_option('fuerza_rate_limit_burst', $burst);
        
        $instance = self::get_instance();
        $instance->config['requests_per_minute'] = $per_minute;
        $instance->config['requests_per_hour'] = $per_hour;
        $instance->config['burst_limit'] = $burst;
    }
    
    public static function whitelist_ip($ip) {
        $whitelist = get_option('fuerza_rate_limit_whitelist', []);
        if (!in_array($ip, $whitelist)) {
            $whitelist[] = $ip;
            update_option('fuerza_rate_limit_whitelist', $whitelist);
            self::get_instance()->config['whitelist_ips'] = $whitelist;
        }
    }
    
    public static function blacklist_ip($ip) {
        $blacklist = get_option('fuerza_rate_limit_blacklist', []);
        if (!in_array($ip, $blacklist)) {
            $blacklist[] = $ip;
            update_option('fuerza_rate_limit_blacklist', $blacklist);
            self::get_instance()->config['blacklist_ips'] = $blacklist;
        }
    }
    
    /**
     * Limpar todos os bloqueios de IP (função de emergência)
     */
    public static function clear_all_blocks() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'fuerza_rate_limits';
        $wpdb->query("DELETE FROM {$table_name} WHERE type = 'block'");
        
        return true;
    }
    
    /**
     * Desbloquear IP específico
     */
    public static function unblock_ip($ip) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'fuerza_rate_limits';
        $wpdb->delete($table_name, [
            'ip_address' => $ip,
            'type' => 'block'
        ]);
        
        return true;
    }
}

// Inicializar o rate limiter se habilitado
if (get_option('fuerza_rate_limiting_enabled', false)) {
    Fuerza_Rate_Limiter::get_instance();
}
