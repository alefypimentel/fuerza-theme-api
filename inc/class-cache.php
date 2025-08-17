<?php
/**
 * Sistema de Cache para o Fuerza Theme API
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

class Fuerza_Cache {
    
    /**
     * Grupos de cache
     */
    const GROUP_API = 'fuerza_api';
    const GROUP_QUERIES = 'fuerza_queries';
    const GROUP_FORMATTERS = 'fuerza_formatters';
    const GROUP_CONTENT = 'fuerza_content';
    
    /**
     * Tempos de expiração (em segundos)
     */
    const EXPIRE_SHORT = 300;    // 5 minutos
    const EXPIRE_MEDIUM = 1800;  // 30 minutos
    const EXPIRE_LONG = 3600;    // 1 hora
    const EXPIRE_DAILY = 86400;  // 24 horas
    
    /**
     * Instância singleton
     */
    private static $instance = null;
    
    /**
     * Configurações de cache
     */
    private $config;
    
    /**
     * Estatísticas de cache
     */
    private $stats = [
        'hits' => 0,
        'misses' => 0,
        'sets' => 0,
        'deletes' => 0,
    ];
    
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
            'enabled' => get_option('fuerza_cache_enabled', true),
            'default_expiration' => get_option('fuerza_cache_default_expiration', self::EXPIRE_MEDIUM),
            'redis_enabled' => get_option('fuerza_cache_redis_enabled', false) && class_exists('Redis'),
            'redis_host' => get_option('fuerza_cache_redis_host', '127.0.0.1'),
            'redis_port' => get_option('fuerza_cache_redis_port', 6379),
            'redis_password' => get_option('fuerza_cache_redis_password', ''),
            'compress_data' => get_option('fuerza_cache_compress', true),
            'max_size' => get_option('fuerza_cache_max_size', 1000000), // 1MB
        ];
    }
    
    /**
     * Configurar hooks
     */
    private function setup_hooks() {
        // Invalidar cache quando posts são atualizados
        add_action('save_post', [$this, 'invalidate_post_cache'], 10, 2);
        add_action('deleted_post', [$this, 'invalidate_post_cache'], 10, 2);
        
        // Invalidar cache quando termos são atualizados
        add_action('created_term', [$this, 'invalidate_taxonomy_cache'], 10, 3);
        add_action('edited_term', [$this, 'invalidate_taxonomy_cache'], 10, 3);
        add_action('deleted_term', [$this, 'invalidate_taxonomy_cache'], 10, 3);
        
        // Invalidar cache quando opções são atualizadas
        add_action('updated_option', [$this, 'invalidate_option_cache'], 10, 3);
        
        // Limpeza periódica
        add_action('fuerza_cache_cleanup', [$this, 'cleanup_expired_cache']);
        
        if (!wp_next_scheduled('fuerza_cache_cleanup')) {
            wp_schedule_event(time(), 'hourly', 'fuerza_cache_cleanup');
        }
        
        // Mostrar estatísticas no footer (apenas para admins em debug)
        if (WP_DEBUG && current_user_can('manage_options')) {
            add_action('wp_footer', [$this, 'display_cache_stats']);
            add_action('admin_footer', [$this, 'display_cache_stats']);
        }
    }
    
    /**
     * Obter valor do cache
     */
    public static function get($key, $group = self::GROUP_API, $default = false) {
        return self::get_instance()->get_cache($key, $group, $default);
    }
    
    /**
     * Definir valor no cache
     */
    public static function set($key, $data, $group = self::GROUP_API, $expiration = null) {
        return self::get_instance()->set_cache($key, $data, $group, $expiration);
    }
    
    /**
     * Deletar valor do cache
     */
    public static function delete($key, $group = self::GROUP_API) {
        return self::get_instance()->delete_cache($key, $group);
    }
    
    /**
     * Limpar grupo de cache
     */
    public static function flush_group($group) {
        return self::get_instance()->flush_cache_group($group);
    }
    
    /**
     * Limpar todo o cache
     */
    public static function flush_all() {
        return self::get_instance()->flush_all_cache();
    }
    
    /**
     * Cache de API Response
     */
    public static function cache_api_response($endpoint, $params, $data, $expiration = self::EXPIRE_MEDIUM) {
        $cache_key = self::generate_api_cache_key($endpoint, $params);
        return self::set($cache_key, $data, self::GROUP_API, $expiration);
    }
    
    /**
     * Obter resposta da API do cache
     */
    public static function get_cached_api_response($endpoint, $params) {
        $cache_key = self::generate_api_cache_key($endpoint, $params);
        return self::get($cache_key, self::GROUP_API);
    }
    
    /**
     * Cache de query de posts
     */
    public static function cache_query($query_args, $results, $expiration = self::EXPIRE_SHORT) {
        $cache_key = 'query_' . md5(serialize($query_args));
        return self::set($cache_key, $results, self::GROUP_QUERIES, $expiration);
    }
    
    /**
     * Obter query do cache
     */
    public static function get_cached_query($query_args) {
        $cache_key = 'query_' . md5(serialize($query_args));
        return self::get($cache_key, self::GROUP_QUERIES);
    }
    
    /**
     * Cache de dados formatados
     */
    public static function cache_formatted_data($post_id, $type, $data, $expiration = self::EXPIRE_LONG) {
        $cache_key = "formatted_{$type}_{$post_id}";
        return self::set($cache_key, $data, self::GROUP_FORMATTERS, $expiration);
    }
    
    /**
     * Obter dados formatados do cache
     */
    public static function get_cached_formatted_data($post_id, $type) {
        $cache_key = "formatted_{$type}_{$post_id}";
        return self::get($cache_key, self::GROUP_FORMATTERS);
    }
    
    /**
     * Implementação interna de get
     */
    private function get_cache($key, $group, $default) {
        if (!$this->config['enabled']) {
            return $default;
        }
        
        try {
            $cache_key = $this->build_cache_key($key, $group);
            
            if ($this->config['redis_enabled']) {
                $data = $this->get_from_redis($cache_key);
            } else {
                $data = wp_cache_get($cache_key, $group);
            }
            
            if ($data !== false) {
                $this->stats['hits']++;
                
                // Verificar se dados foram comprimidos
                if (is_array($data) && isset($data['_compressed']) && $data['_compressed']) {
                    $data = unserialize(gzuncompress(base64_decode($data['data'])));
                }
                
                return $data;
            }
            
            $this->stats['misses']++;
            return $default;
            
        } catch (Exception $e) {
            Fuerza_Logger::error("Cache get error: " . $e->getMessage(), [
                'key' => $key,
                'group' => $group
            ]);
            return $default;
        }
    }
    
    /**
     * Implementação interna de set
     */
    private function set_cache($key, $data, $group, $expiration) {
        if (!$this->config['enabled']) {
            return false;
        }
        
        try {
            $expiration = $expiration ?? $this->config['default_expiration'];
            $cache_key = $this->build_cache_key($key, $group);
            
            // Comprimir dados se necessário
            $data_size = strlen(serialize($data));
            if ($this->config['compress_data'] && $data_size > 1024) {
                $compressed_data = base64_encode(gzcompress(serialize($data)));
                $data = [
                    '_compressed' => true,
                    'data' => $compressed_data,
                    'original_size' => $data_size,
                    'compressed_size' => strlen($compressed_data)
                ];
            }
            
            // Verificar tamanho máximo
            if (strlen(serialize($data)) > $this->config['max_size']) {
                Fuerza_Logger::warning("Cache data too large", [
                    'key' => $key,
                    'size' => strlen(serialize($data)),
                    'max_size' => $this->config['max_size']
                ]);
                return false;
            }
            
            if ($this->config['redis_enabled']) {
                $result = $this->set_in_redis($cache_key, $data, $expiration);
            } else {
                $result = wp_cache_set($cache_key, $data, $group, $expiration);
            }
            
            if ($result) {
                $this->stats['sets']++;
            }
            
            return $result;
            
        } catch (Exception $e) {
            Fuerza_Logger::error("Cache set error: " . $e->getMessage(), [
                'key' => $key,
                'group' => $group
            ]);
            return false;
        }
    }
    
    /**
     * Implementação interna de delete
     */
    private function delete_cache($key, $group) {
        if (!$this->config['enabled']) {
            return false;
        }
        
        try {
            $cache_key = $this->build_cache_key($key, $group);
            
            if ($this->config['redis_enabled']) {
                $result = $this->delete_from_redis($cache_key);
            } else {
                $result = wp_cache_delete($cache_key, $group);
            }
            
            if ($result) {
                $this->stats['deletes']++;
            }
            
            return $result;
            
        } catch (Exception $e) {
            Fuerza_Logger::error("Cache delete error: " . $e->getMessage(), [
                'key' => $key,
                'group' => $group
            ]);
            return false;
        }
    }
    
    /**
     * Limpar grupo de cache
     */
    private function flush_cache_group($group) {
        if (!$this->config['enabled']) {
            return false;
        }
        
        try {
            if ($this->config['redis_enabled']) {
                return $this->flush_redis_group($group);
            } else {
                return wp_cache_flush_group($group);
            }
        } catch (Exception $e) {
            Fuerza_Logger::error("Cache flush group error: " . $e->getMessage(), [
                'group' => $group
            ]);
            return false;
        }
    }
    
    /**
     * Limpar todo o cache
     */
    private function flush_all_cache() {
        if (!$this->config['enabled']) {
            return false;
        }
        
        try {
            if ($this->config['redis_enabled']) {
                return $this->flush_redis_all();
            } else {
                return wp_cache_flush();
            }
        } catch (Exception $e) {
            Fuerza_Logger::error("Cache flush all error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Construir chave de cache
     */
    private function build_cache_key($key, $group) {
        return "fuerza_{$group}_{$key}";
    }
    
    /**
     * Gerar chave de cache para API
     */
    private static function generate_api_cache_key($endpoint, $params) {
        // Remover parâmetros sensíveis
        $safe_params = $params;
        unset($safe_params['_wpnonce'], $safe_params['password'], $safe_params['token']);
        
        ksort($safe_params); // Ordenar para consistência
        return 'api_' . md5($endpoint . '_' . serialize($safe_params));
    }
    
    /**
     * Métodos Redis
     */
    private function get_redis_connection() {
        static $redis = null;
        
        if ($redis === null && $this->config['redis_enabled']) {
            try {
                $redis = new Redis();
                $redis->connect($this->config['redis_host'], $this->config['redis_port']);
                
                if (!empty($this->config['redis_password'])) {
                    $redis->auth($this->config['redis_password']);
                }
                
                $redis->select(0); // Usar database 0
                
            } catch (Exception $e) {
                Fuerza_Logger::error("Redis connection error: " . $e->getMessage());
                $redis = false;
            }
        }
        
        return $redis;
    }
    
    private function get_from_redis($key) {
        $redis = $this->get_redis_connection();
        if (!$redis) return false;
        
        $data = $redis->get($key);
        return $data ? unserialize($data) : false;
    }
    
    private function set_in_redis($key, $data, $expiration) {
        $redis = $this->get_redis_connection();
        if (!$redis) return false;
        
        return $redis->setex($key, $expiration, serialize($data));
    }
    
    private function delete_from_redis($key) {
        $redis = $this->get_redis_connection();
        if (!$redis) return false;
        
        return $redis->del($key) > 0;
    }
    
    private function flush_redis_group($group) {
        $redis = $this->get_redis_connection();
        if (!$redis) return false;
        
        $pattern = "fuerza_{$group}_*";
        $keys = $redis->keys($pattern);
        
        if (!empty($keys)) {
            return $redis->del($keys) > 0;
        }
        
        return true;
    }
    
    private function flush_redis_all() {
        $redis = $this->get_redis_connection();
        if (!$redis) return false;
        
        return $redis->flushDB();
    }
    
    /**
     * Invalidações automáticas
     */
    public function invalidate_post_cache($post_id, $post = null) {
        if (!$post) {
            $post = get_post($post_id);
        }
        
        if (!$post) return;
        
        // Limpar cache específico do post
        $this->delete_cache("post_{$post_id}", self::GROUP_CONTENT);
        $this->delete_cache("formatted_post_{$post_id}", self::GROUP_FORMATTERS);
        
        // Limpar cache de queries relacionadas ao tipo de post
        $post_type = $post->post_type;
        $this->flush_cache_group(self::GROUP_QUERIES);
        
        // Limpar cache da API relacionado
        $this->delete_cache("api_{$post_type}_*", self::GROUP_API);
        
        Fuerza_Logger::debug("Cache invalidated for post", [
            'post_id' => $post_id,
            'post_type' => $post_type
        ]);
    }
    
    public function invalidate_taxonomy_cache($term_id, $tt_id, $taxonomy) {
        // Limpar cache de termos
        $this->delete_cache("term_{$term_id}", self::GROUP_CONTENT);
        $this->delete_cache("taxonomy_{$taxonomy}", self::GROUP_CONTENT);
        
        // Limpar cache de queries
        $this->flush_cache_group(self::GROUP_QUERIES);
        
        Fuerza_Logger::debug("Cache invalidated for taxonomy", [
            'term_id' => $term_id,
            'taxonomy' => $taxonomy
        ]);
    }
    
    public function invalidate_option_cache($option_name, $old_value, $value) {
        // Invalidar cache para opções críticas
        $critical_options = [
            'blogname',
            'blogdescription',
            'siteurl',
            'home',
        ];
        
        if (in_array($option_name, $critical_options)) {
            $this->flush_all_cache();
        }
    }
    
    /**
     * Limpeza de cache expirado
     */
    public function cleanup_expired_cache() {
        // Para WordPress cache, não há muito o que fazer (é automático)
        // Para Redis, também é automático com TTL
        
        // Limpar estatísticas antigas
        $this->cleanup_cache_stats();
    }
    
    private function cleanup_cache_stats() {
        // Resetar estatísticas a cada limpeza
        $this->stats = [
            'hits' => 0,
            'misses' => 0,
            'sets' => 0,
            'deletes' => 0,
        ];
    }
    
    /**
     * Obter estatísticas de cache
     */
    public function get_stats() {
        $hit_rate = $this->stats['hits'] + $this->stats['misses'] > 0
            ? round(($this->stats['hits'] / ($this->stats['hits'] + $this->stats['misses'])) * 100, 2)
            : 0;
        
        return array_merge($this->stats, [
            'hit_rate' => $hit_rate,
            'enabled' => $this->config['enabled'],
            'redis_enabled' => $this->config['redis_enabled'],
        ]);
    }
    
    /**
     * Mostrar estatísticas de cache no footer (debug)
     */
    public function display_cache_stats() {
        if (!WP_DEBUG || !current_user_can('manage_options')) {
            return;
        }
        
        $stats = $this->get_stats();
        
        echo '<!-- Fuerza Cache Stats -->';
        echo '<div style="position: fixed; bottom: 10px; right: 10px; background: rgba(0,0,0,0.8); color: white; padding: 10px; border-radius: 5px; font-size: 12px; z-index: 9999;">';
        echo '<strong>Cache Stats:</strong><br>';
        echo "Hits: {$stats['hits']} | Misses: {$stats['misses']}<br>";
        echo "Hit Rate: {$stats['hit_rate']}%<br>";
        echo "Redis: " . ($stats['redis_enabled'] ? 'Yes' : 'No');
        echo '</div>';
    }
    
    /**
     * Configurações
     */
    public static function enable($enabled = true) {
        update_option('fuerza_cache_enabled', $enabled);
        self::get_instance()->config['enabled'] = $enabled;
    }
    
    public static function enable_redis($enabled = true, $host = '127.0.0.1', $port = 6379, $password = '') {
        update_option('fuerza_cache_redis_enabled', $enabled);
        update_option('fuerza_cache_redis_host', $host);
        update_option('fuerza_cache_redis_port', $port);
        update_option('fuerza_cache_redis_password', $password);
        
        $instance = self::get_instance();
        $instance->config['redis_enabled'] = $enabled && class_exists('Redis');
        $instance->config['redis_host'] = $host;
        $instance->config['redis_port'] = $port;
        $instance->config['redis_password'] = $password;
    }
}

// Inicializar o cache
Fuerza_Cache::get_instance();
