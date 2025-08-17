<?php
/**
 * Sistema de Logging para o Fuerza Theme API
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

class Fuerza_Logger {
    
    /**
     * Níveis de log
     */
    const LEVEL_DEBUG = 'debug';
    const LEVEL_INFO = 'info';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';
    const LEVEL_CRITICAL = 'critical';
    
    /**
     * Instância singleton
     */
    private static $instance = null;
    
    /**
     * Diretório de logs
     */
    private $log_directory;
    
    /**
     * Arquivo de log atual
     */
    private $log_file;
    
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
        $this->setup_log_directory();
        $this->setup_hooks();
    }
    
    /**
     * Configurar sistema
     */
    private function setup_config() {
        $this->config = [
            'enabled' => WP_DEBUG || get_option('fuerza_api_logging_enabled', false),
            'level' => get_option('fuerza_api_log_level', self::LEVEL_INFO),
            'max_file_size' => get_option('fuerza_api_log_max_size', 5 * 1024 * 1024), // 5MB
            'retention_days' => get_option('fuerza_api_log_retention', 30),
            'include_user_data' => get_option('fuerza_api_log_user_data', false),
        ];
    }
    
    /**
     * Configurar diretório de logs
     */
    private function setup_log_directory() {
        $upload_dir = wp_upload_dir();
        $this->log_directory = $upload_dir['basedir'] . '/fuerza-api-logs';
        
        if (!file_exists($this->log_directory)) {
            wp_mkdir_p($this->log_directory);
            
            // Criar arquivo .htaccess para proteger logs
            file_put_contents(
                $this->log_directory . '/.htaccess',
                "Order deny,allow\nDeny from all"
            );
        }
        
        $this->log_file = $this->log_directory . '/api-' . date('Y-m-d') . '.log';
    }
    
    /**
     * Configurar hooks
     */
    private function setup_hooks() {
        add_action('wp_scheduled_delete', [$this, 'cleanup_old_logs']);
        add_action('fuerza_api_log_cleanup', [$this, 'cleanup_old_logs']);
        
        // Agendar limpeza diária se não existe
        if (!wp_next_scheduled('fuerza_api_log_cleanup')) {
            wp_schedule_event(time(), 'daily', 'fuerza_api_log_cleanup');
        }
    }
    
    /**
     * Log de debug
     */
    public static function debug($message, $context = []) {
        return self::get_instance()->log(self::LEVEL_DEBUG, $message, $context);
    }
    
    /**
     * Log de informação
     */
    public static function info($message, $context = []) {
        return self::get_instance()->log(self::LEVEL_INFO, $message, $context);
    }
    
    /**
     * Log de warning
     */
    public static function warning($message, $context = []) {
        return self::get_instance()->log(self::LEVEL_WARNING, $message, $context);
    }
    
    /**
     * Log de erro
     */
    public static function error($message, $context = []) {
        return self::get_instance()->log(self::LEVEL_ERROR, $message, $context);
    }
    
    /**
     * Log crítico
     */
    public static function critical($message, $context = []) {
        return self::get_instance()->log(self::LEVEL_CRITICAL, $message, $context);
    }
    
    /**
     * Log de requisição da API
     */
    public static function api_request($endpoint, $method, $response_code, $response_time = null, $context = []) {
        $message = sprintf(
            'API Request: %s %s - Status: %d%s',
            $method,
            $endpoint,
            $response_code,
            $response_time ? " - Time: {$response_time}ms" : ''
        );
        
        $context['endpoint'] = $endpoint;
        $context['method'] = $method;
        $context['response_code'] = $response_code;
        $context['response_time'] = $response_time;
        
        return self::get_instance()->log(self::LEVEL_INFO, $message, $context);
    }
    
    /**
     * Escrever log
     */
    public function log($level, $message, $context = []) {
        if (!$this->should_log($level)) {
            return false;
        }
        
        try {
            $log_entry = $this->format_log_entry($level, $message, $context);
            
            // Verificar tamanho do arquivo
            if (file_exists($this->log_file) && filesize($this->log_file) > $this->config['max_file_size']) {
                $this->rotate_log_file();
            }
            
            return file_put_contents($this->log_file, $log_entry, FILE_APPEND | LOCK_EX) !== false;
            
        } catch (Exception $e) {
            // Em caso de erro no logging, tentar error_log como fallback
            error_log("Fuerza Logger Error: " . $e->getMessage());
            error_log("Original message: [{$level}] {$message}");
            return false;
        }
    }
    
    /**
     * Verificar se deve fazer log baseado no nível
     */
    private function should_log($level) {
        if (!$this->config['enabled']) {
            return false;
        }
        
        $levels = [
            self::LEVEL_DEBUG => 0,
            self::LEVEL_INFO => 1,
            self::LEVEL_WARNING => 2,
            self::LEVEL_ERROR => 3,
            self::LEVEL_CRITICAL => 4,
        ];
        
        $current_level = $levels[$this->config['level']] ?? 1;
        $message_level = $levels[$level] ?? 1;
        
        return $message_level >= $current_level;
    }
    
    /**
     * Formatar entrada do log
     */
    private function format_log_entry($level, $message, $context = []) {
        $timestamp = current_time('Y-m-d H:i:s');
        $user_id = get_current_user_id();
        $ip = $this->get_client_ip();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        $log_data = [
            'timestamp' => $timestamp,
            'level' => strtoupper($level),
            'message' => $message,
            'ip' => $ip,
        ];
        
        // Adicionar dados do usuário se habilitado
        if ($this->config['include_user_data'] && $user_id) {
            $log_data['user_id'] = $user_id;
            $log_data['user_agent'] = substr($user_agent, 0, 200); // Limitar tamanho
        }
        
        // Adicionar contexto se fornecido
        if (!empty($context)) {
            $log_data['context'] = $context;
        }
        
        // Adicionar informações de requisição se disponível
        if (isset($_SERVER['REQUEST_URI'])) {
            $log_data['request_uri'] = $_SERVER['REQUEST_URI'];
        }
        
        return json_encode($log_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    }
    
    /**
     * Obter IP do cliente
     */
    private function get_client_ip() {
        $ip_headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',            // Proxy
            'HTTP_X_FORWARDED_FOR',      // Load Balancer/Proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR'                // Standard
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
        
        return $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    }
    
    /**
     * Rotacionar arquivo de log
     */
    private function rotate_log_file() {
        if (file_exists($this->log_file)) {
            $backup_file = $this->log_file . '-' . time() . '.bak';
            rename($this->log_file, $backup_file);
        }
    }
    
    /**
     * Limpar logs antigos
     */
    public function cleanup_old_logs() {
        if (!is_dir($this->log_directory)) {
            return;
        }
        
        $retention_time = time() - ($this->config['retention_days'] * 24 * 60 * 60);
        $files = glob($this->log_directory . '/*.log*');
        
        foreach ($files as $file) {
            if (filemtime($file) < $retention_time) {
                unlink($file);
            }
        }
    }
    
    /**
     * Obter estatísticas de logs
     */
    public function get_stats() {
        if (!is_dir($this->log_directory)) {
            return [];
        }
        
        $files = glob($this->log_directory . '/*.log*');
        $total_size = 0;
        $file_count = count($files);
        
        foreach ($files as $file) {
            $total_size += filesize($file);
        }
        
        return [
            'file_count' => $file_count,
            'total_size' => $total_size,
            'total_size_formatted' => size_format($total_size),
            'directory' => $this->log_directory,
            'current_file' => $this->log_file,
            'config' => $this->config
        ];
    }
    
    /**
     * Habilitar/desabilitar logging
     */
    public static function enable($enabled = true) {
        update_option('fuerza_api_logging_enabled', $enabled);
        self::get_instance()->config['enabled'] = $enabled;
    }
    
    /**
     * Definir nível de log
     */
    public static function set_level($level) {
        $valid_levels = [
            self::LEVEL_DEBUG,
            self::LEVEL_INFO,
            self::LEVEL_WARNING,
            self::LEVEL_ERROR,
            self::LEVEL_CRITICAL
        ];
        
        if (in_array($level, $valid_levels)) {
            update_option('fuerza_api_log_level', $level);
            self::get_instance()->config['level'] = $level;
            return true;
        }
        
        return false;
    }
}

// Inicializar o logger se necessário
if (WP_DEBUG || get_option('fuerza_api_logging_enabled', false)) {
    Fuerza_Logger::get_instance();
}
