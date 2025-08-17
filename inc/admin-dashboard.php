<?php
/**
 * Dashboard Administrativo para o Fuerza Theme API
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

class Fuerza_Admin_Dashboard {
    
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
        add_action('admin_menu', [$this, 'add_admin_menus']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_ajax_fuerza_api_stats', [$this, 'get_api_stats']);
        add_action('wp_ajax_fuerza_toggle_feature', [$this, 'toggle_feature']);
        add_action('wp_ajax_test_all_api_routes', [$this, 'ajax_test_all_routes']);
    }
    
    /**
     * Adicionar menus administrativos
     */
    public function add_admin_menus() {
        // Menu principal
        add_menu_page(
            'Fuerza API',
            'Fuerza API',
            'manage_options',
            'fuerza-api',
            [$this, 'render_dashboard'],
            'dashicons-rest-api',
            30
        );
        
        // Submenus
        add_submenu_page(
            'fuerza-api',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'fuerza-api',
            [$this, 'render_dashboard']
        );
        
        add_submenu_page(
            'fuerza-api',
            'Monitoramento',
            'Monitoramento',
            'manage_options',
            'fuerza-api-monitoring',
            [$this, 'render_monitoring']
        );
        
        add_submenu_page(
            'fuerza-api',
            'Cache',
            'Cache',
            'manage_options',
            'fuerza-api-cache',
            [$this, 'render_cache']
        );
        
        add_submenu_page(
            'fuerza-api',
            'Rate Limiting',
            'Rate Limiting',
            'manage_options',
            'fuerza-api-rate-limit',
            [$this, 'render_rate_limit']
        );
        
        add_submenu_page(
            'fuerza-api',
            'Teste de Rotas',
            'Teste de Rotas',
            'manage_options',
            'fuerza-api-routes',
            [$this, 'render_routes_tester']
        );
        
        add_submenu_page(
            'fuerza-api',
            'Configurações',
            'Configurações',
            'manage_options',
            'fuerza-api-settings',
            [$this, 'render_settings']
        );
    }
    
    /**
     * Enfileirar assets administrativos
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'fuerza-api') === false) {
            return;
        }
        
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], '3.9.1', true);
        wp_enqueue_script('fuerza-admin', '', [], '1.0.0', true);
        wp_enqueue_style('fuerza-admin', '', [], '1.0.0');
        
        // Adicionar CSS inline
        wp_add_inline_style('fuerza-admin', $this->get_admin_css());
        
        // Adicionar JS inline
        wp_add_inline_script('fuerza-admin', $this->get_admin_js());
        
        // Localizar script
        wp_localize_script('fuerza-admin', 'fuerzaAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('fuerza_admin_nonce'),
            'apiUrl' => home_url('/wp-json/' . API_Manager::get_namespace()),
        ]);
    }
    
    /**
     * CSS administrativo
     */
    private function get_admin_css() {
        return '
        .fuerza-dashboard {
            max-width: 1200px;
        }
        .fuerza-card {
            background: white;
            border: 1px solid #c3c4c7;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .fuerza-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .fuerza-stat-card {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        .fuerza-stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 8px;
            display: block;
        }
        .fuerza-stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        .fuerza-toggle {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin: 10px 0;
        }
        .fuerza-switch {
            position: relative;
            width: 50px;
            height: 24px;
            background: #ccc;
            border-radius: 12px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .fuerza-switch.active {
            background: #667eea;
        }
        .fuerza-switch::after {
            content: "";
            position: absolute;
            top: 2px;
            left: 2px;
            width: 20px;
            height: 20px;
            background: white;
            border-radius: 50%;
            transition: transform 0.3s;
        }
        .fuerza-switch.active::after {
            transform: translateX(26px);
        }
        .fuerza-chart-container {
            position: relative;
            height: 300px;
            margin-top: 20px;
        }
        .fuerza-alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin: 10px 0;
        }
        .fuerza-alert.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .fuerza-alert.warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .fuerza-alert.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        ';
    }
    
    /**
     * JavaScript administrativo
     */
    private function get_admin_js() {
        return '
        jQuery(document).ready(function($) {
            // Toggle switches
            $(".fuerza-switch").on("click", function() {
                const $switch = $(this);
                const feature = $switch.data("feature");
                const enabled = !$switch.hasClass("active");
                
                $.post(fuerzaAdmin.ajaxUrl, {
                    action: "fuerza_toggle_feature",
                    feature: feature,
                    enabled: enabled ? 1 : 0,
                    nonce: fuerzaAdmin.nonce
                }, function(response) {
                    if (response.success) {
                        $switch.toggleClass("active", enabled);
                        showAlert("Configuração atualizada com sucesso!", "success");
                    } else {
                        showAlert("Erro ao atualizar configuração: " + response.data, "error");
                    }
                });
            });
            
            // Atualizar estatísticas
            function updateStats() {
                $.post(fuerzaAdmin.ajaxUrl, {
                    action: "fuerza_api_stats",
                    nonce: fuerzaAdmin.nonce
                }, function(response) {
                    if (response.success) {
                        updateStatsDisplay(response.data);
                    }
                });
            }
            
            function updateStatsDisplay(stats) {
                $("#total-requests").text(stats.total_requests || 0);
                $("#avg-response-time").text((stats.avg_response_time || 0).toFixed(2) + "ms");
                $("#cache-hit-rate").text((stats.cache_hit_rate || 0) + "%");
                $("#active-ips").text(stats.active_ips || 0);
            }
            
            function showAlert(message, type) {
                const alertHtml = `<div class="fuerza-alert ${type}">${message}</div>`;
                $(".fuerza-dashboard").prepend(alertHtml);
                setTimeout(() => {
                    $(".fuerza-alert").fadeOut(() => {
                        $(this).remove();
                    });
                }, 3000);
            }
            
            // Atualizar estatísticas a cada 30 segundos
            updateStats();
            setInterval(updateStats, 30000);
            
            // Gráficos
            if (typeof Chart !== "undefined") {
                initCharts();
            }
            
            function initCharts() {
                // Gráfico de requisições por hora
                const ctx1 = document.getElementById("requestsChart");
                if (ctx1) {
                    new Chart(ctx1, {
                        type: "line",
                        data: {
                            labels: Array.from({length: 24}, (_, i) => i + "h"),
                            datasets: [{
                                label: "Requisições",
                                data: Array.from({length: 24}, () => Math.floor(Math.random() * 100)),
                                borderColor: "#667eea",
                                backgroundColor: "rgba(102, 126, 234, 0.1)",
                                tension: 0.4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                }
                
                // Gráfico de status codes
                const ctx2 = document.getElementById("statusChart");
                if (ctx2) {
                    new Chart(ctx2, {
                        type: "doughnut",
                        data: {
                            labels: ["200 OK", "404 Not Found", "429 Rate Limited", "500 Error"],
                            datasets: [{
                                data: [85, 10, 3, 2],
                                backgroundColor: ["#28a745", "#ffc107", "#fd7e14", "#dc3545"]
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false
                        }
                    });
                }
            }
        });
        ';
    }
    
    /**
     * Renderizar dashboard principal
     */
    public function render_dashboard() {
        // Obter estatísticas
        $monitor_stats = Fuerza_Monitor::get_instance()->get_performance_stats();
        $cache_stats = Fuerza_Cache::get_instance()->get_stats();
        $rate_limit_stats = Fuerza_Rate_Limiter::get_instance()->get_stats();
        
        ?>
        <div class="wrap fuerza-dashboard">
            <h1>🚀 Fuerza API Dashboard</h1>
            
            <div class="fuerza-stats-grid">
                <div class="fuerza-stat-card">
                    <span class="fuerza-stat-number" id="total-requests"><?php echo $monitor_stats['overview']['total_requests'] ?? 0; ?></span>
                    <span class="fuerza-stat-label">Total de Requisições (7 dias)</span>
                </div>
                
                <div class="fuerza-stat-card">
                    <span class="fuerza-stat-number" id="avg-response-time"><?php echo round($monitor_stats['overview']['avg_response_time'] ?? 0, 2); ?>ms</span>
                    <span class="fuerza-stat-label">Tempo Médio de Resposta</span>
                </div>
                
                <div class="fuerza-stat-card">
                    <span class="fuerza-stat-number" id="cache-hit-rate"><?php echo $cache_stats['hit_rate'] ?? 0; ?>%</span>
                    <span class="fuerza-stat-label">Taxa de Acerto do Cache</span>
                </div>
                
                <div class="fuerza-stat-card">
                    <span class="fuerza-stat-number" id="active-ips"><?php echo $rate_limit_stats['unique_ips'] ?? 0; ?></span>
                    <span class="fuerza-stat-label">IPs Únicos (1 hora)</span>
                </div>
            </div>
            
            <div class="fuerza-card">
                <h2>⚡ Configurações Rápidas</h2>
                
                <div class="fuerza-toggle">
                    <span class="fuerza-switch <?php echo get_option('fuerza_cache_enabled', true) ? 'active' : ''; ?>" data-feature="cache"></span>
                    <label>Sistema de Cache</label>
                </div>
                
                <div class="fuerza-toggle">
                    <span class="fuerza-switch <?php echo get_option('fuerza_rate_limiting_enabled', true) ? 'active' : ''; ?>" data-feature="rate_limit"></span>
                    <label>Rate Limiting</label>
                </div>
                
                <div class="fuerza-toggle">
                    <span class="fuerza-switch <?php echo get_option('fuerza_api_logging_enabled', false) ? 'active' : ''; ?>" data-feature="logging"></span>
                    <label>Sistema de Logs</label>
                </div>
                
                <div class="fuerza-toggle">
                    <span class="fuerza-switch <?php echo get_option('fuerza_api_monitoring_enabled', true) ? 'active' : ''; ?>" data-feature="monitoring"></span>
                    <label>Monitoramento</label>
                </div>
                
                <div class="fuerza-toggle">
                    <span class="fuerza-switch <?php echo get_option('fuerza_api_docs_enabled', true) ? 'active' : ''; ?>" data-feature="docs"></span>
                    <label>Documentação da API</label>
                </div>
            </div>
            
            <div class="fuerza-card">
                <h2>📊 Requisições por Hora</h2>
                <div class="fuerza-chart-container">
                    <canvas id="requestsChart"></canvas>
                </div>
            </div>
            
            <div class="fuerza-card">
                <h2>🎯 Status das Respostas</h2>
                <div class="fuerza-chart-container">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
            
            <div class="fuerza-card">
                <h2>🔗 Links Úteis</h2>
                <p>
                    <a href="<?php echo home_url('/wp-json/' . API_Manager::get_namespace() . '/docs'); ?>" target="_blank" class="button button-primary">📖 Documentação da API</a>
                    <a href="<?php echo home_url('/wp-json/' . API_Manager::get_namespace() . '/ping'); ?>" target="_blank" class="button">🏓 Testar API</a>
                    <a href="<?php echo admin_url('admin.php?page=fuerza-api-monitoring'); ?>" class="button">📈 Monitoramento Detalhado</a>
                </p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Renderizar página de monitoramento
     */
    public function render_monitoring() {
        $stats = Fuerza_Monitor::get_instance()->get_performance_stats();
        
        ?>
        <div class="wrap">
            <h1>📈 Monitoramento da API</h1>
            
            <div class="fuerza-card">
                <h2>Estatísticas Gerais</h2>
                <table class="wp-list-table widefat fixed striped">
                    <tbody>
                        <tr>
                            <td><strong>Total de Requisições</strong></td>
                            <td><?php echo $stats['overview']['total_requests'] ?? 0; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Tempo Médio de Resposta</strong></td>
                            <td><?php echo round($stats['overview']['avg_response_time'] ?? 0, 2); ?>ms</td>
                        </tr>
                        <tr>
                            <td><strong>Uso Médio de Memória</strong></td>
                            <td><?php echo size_format($stats['overview']['avg_memory_usage'] ?? 0); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="fuerza-card">
                <h2>Rotas Mais Utilizadas</h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Rota</th>
                            <th>Requisições</th>
                            <th>Tempo Médio</th>
                            <th>Tempo Máximo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['routes'] as $route): ?>
                        <tr>
                            <td><?php echo esc_html($route['route']); ?></td>
                            <td><?php echo $route['requests']; ?></td>
                            <td><?php echo round($route['avg_time'], 2); ?>ms</td>
                            <td><?php echo round($route['max_time'], 2); ?>ms</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
    
    /**
     * Renderizar página de cache
     */
    public function render_cache() {
        $stats = Fuerza_Cache::get_instance()->get_stats();
        
        ?>
        <div class="wrap">
            <h1>💾 Gerenciamento de Cache</h1>
            
            <div class="fuerza-card">
                <h2>Estatísticas do Cache</h2>
                <table class="wp-list-table widefat fixed striped">
                    <tbody>
                        <tr>
                            <td><strong>Status</strong></td>
                            <td><?php echo $stats['enabled'] ? '✅ Ativo' : '❌ Inativo'; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Cache Hits</strong></td>
                            <td><?php echo $stats['hits']; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Cache Misses</strong></td>
                            <td><?php echo $stats['misses']; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Taxa de Acerto</strong></td>
                            <td><?php echo $stats['hit_rate']; ?>%</td>
                        </tr>
                        <tr>
                            <td><strong>Redis</strong></td>
                            <td><?php echo $stats['redis_enabled'] ? '✅ Habilitado' : '❌ Desabilitado'; ?></td>
                        </tr>
                    </tbody>
                </table>
                
                <p>
                    <button class="button button-primary" onclick="clearCache('all')">🗑️ Limpar Todo Cache</button>
                    <button class="button" onclick="clearCache('api')">🗑️ Limpar Cache da API</button>
                    <button class="button" onclick="clearCache('queries')">🗑️ Limpar Cache de Queries</button>
                </p>
            </div>
        </div>
        
        <script>
        function clearCache(type) {
            if (confirm('Tem certeza que deseja limpar o cache?')) {
                jQuery.post(ajaxurl, {
                    action: 'fuerza_clear_cache',
                    type: type,
                    nonce: '<?php echo wp_create_nonce('fuerza_admin_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        alert('Cache limpo com sucesso!');
                        location.reload();
                    } else {
                        alert('Erro ao limpar cache: ' + response.data);
                    }
                });
            }
        }
        </script>
        <?php
    }
    
    /**
     * Renderizar página de rate limiting
     */
    public function render_rate_limit() {
        $stats = Fuerza_Rate_Limiter::get_instance()->get_stats();
        
        ?>
        <div class="wrap">
            <h1>🛡️ Rate Limiting</h1>
            
            <div class="fuerza-card">
                <h2>Estatísticas</h2>
                <table class="wp-list-table widefat fixed striped">
                    <tbody>
                        <tr>
                            <td><strong>Requisições na Última Hora</strong></td>
                            <td><?php echo $stats['hourly_requests']; ?></td>
                        </tr>
                        <tr>
                            <td><strong>IPs Únicos</strong></td>
                            <td><?php echo $stats['unique_ips']; ?></td>
                        </tr>
                        <tr>
                            <td><strong>IPs Bloqueados</strong></td>
                            <td><?php echo $stats['blocked_ips']; ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="fuerza-card">
                <h2>Top IPs por Requisições</h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>IP</th>
                            <th>Requisições</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['top_ips'] as $ip_stat): ?>
                        <tr>
                            <td><?php echo esc_html($ip_stat['ip']); ?></td>
                            <td><?php echo $ip_stat['requests']; ?></td>
                            <td>
                                <button class="button button-small" onclick="whitelistIP('<?php echo esc_js($ip_stat['ip']); ?>')">✅ Whitelist</button>
                                <button class="button button-small" onclick="blacklistIP('<?php echo esc_js($ip_stat['ip']); ?>')">❌ Blacklist</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <script>
        function whitelistIP(ip) {
            if (confirm('Adicionar ' + ip + ' à whitelist?')) {
                jQuery.post(ajaxurl, {
                    action: 'fuerza_manage_ip',
                    ip: ip,
                    action_type: 'whitelist',
                    nonce: '<?php echo wp_create_nonce('fuerza_admin_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        alert('IP adicionado à whitelist!');
                        location.reload();
                    }
                });
            }
        }
        
        function blacklistIP(ip) {
            if (confirm('Adicionar ' + ip + ' à blacklist?')) {
                jQuery.post(ajaxurl, {
                    action: 'fuerza_manage_ip',
                    ip: ip,
                    action_type: 'blacklist',
                    nonce: '<?php echo wp_create_nonce('fuerza_admin_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        alert('IP adicionado à blacklist!');
                        location.reload();
                    }
                });
            }
        }
        </script>
        <?php
    }
    
    /**
     * Renderizar página de teste de rotas
     */
    public function render_routes_tester() {
        $api_tester = Fuerza_API_Tester::get_instance();
        $routes = $api_tester->get_available_routes();
        ?>
        <div class="wrap">
            <h1>🧪 Teste de Rotas da API</h1>
            
            <div class="notice notice-info">
                <p><strong>💡 Dica:</strong> Use esta ferramenta para testar todas as rotas da API criadas pelos Custom Post Types e verificar se estão funcionando corretamente.</p>
            </div>
            
            <div class="fuerza-admin-grid">
                <!-- Painel de Teste Rápido -->
                <div class="fuerza-card">
                    <h2>🚀 Teste Rápido</h2>
                    <p>Teste todas as rotas automaticamente:</p>
                    <button type="button" class="button button-primary" id="test-all-routes">
                        Testar Todas as Rotas
                    </button>
                    <div id="test-all-results" style="margin-top: 15px;"></div>
                </div>
                
                <!-- Painel de Teste Individual -->
                <div class="fuerza-card">
                    <h2>🎯 Teste Individual</h2>
                    <form id="test-single-route">
                        <table class="form-table">
                            <tr>
                                <th scope="row">Rota</th>
                                <td>
                                    <select name="route" id="route-select" required>
                                        <option value="">Selecione uma rota...</option>
                                        <?php foreach ($routes as $route): ?>
                                            <option value="<?php echo esc_attr($route['route']); ?>" 
                                                    data-methods="<?php echo esc_attr(implode(',', (array)$route['methods'])); ?>">
                                                <?php echo esc_html($route['route']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Método</th>
                                <td>
                                    <select name="method" id="method-select" required>
                                        <option value="GET">GET</option>
                                        <option value="POST">POST</option>
                                        <option value="PUT">PUT</option>
                                        <option value="DELETE">DELETE</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Parâmetros (JSON)</th>
                                <td>
                                    <textarea name="params" id="params-input" rows="4" cols="50" 
                                              placeholder='{"id": 1, "param": "value"}'></textarea>
                                    <p class="description">Digite os parâmetros em formato JSON (opcional)</p>
                                </td>
                            </tr>
                        </table>
                        <button type="submit" class="button button-primary">Testar Rota</button>
                    </form>
                    <div id="single-test-result" style="margin-top: 15px;"></div>
                </div>
            </div>
            
            <!-- Lista de Rotas Disponíveis -->
            <div class="fuerza-card" style="margin-top: 20px;">
                <h2>📋 Rotas Disponíveis</h2>
                <div class="tablenav top">
                    <div class="alignleft actions">
                        <span class="displaying-num"><?php echo count($routes); ?> rotas encontradas</span>
                    </div>
                </div>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Rota</th>
                            <th>Método(s)</th>
                            <th>Descrição</th>
                            <th>URL Completa</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($routes)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 20px;">
                                    <em>Nenhuma rota da API encontrada. Certifique-se de que há Custom Post Types configurados.</em>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($routes as $route): ?>
                                <tr>
                                    <td><code><?php echo esc_html($route['clean_route']); ?></code></td>
                                    <td>
                                        <?php 
                                        $methods = is_array($route['methods']) ? $route['methods'] : [$route['methods']];
                                        foreach ($methods as $method): 
                                        ?>
                                            <span class="fuerza-method-badge fuerza-method-<?php echo strtolower($method); ?>">
                                                <?php echo esc_html($method); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </td>
                                    <td><?php echo esc_html($route['description']); ?></td>
                                    <td>
                                        <a href="<?php echo esc_url($route['full_url']); ?>" target="_blank" class="button button-small">
                                            Ver no Navegador
                                        </a>
                                    </td>
                                    <td>
                                        <button class="button button-small test-single-route-btn" 
                                                data-route="<?php echo esc_attr($route['route']); ?>"
                                                data-methods="<?php echo esc_attr(implode(',', $methods)); ?>">
                                            Testar
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <style>
        .fuerza-method-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            margin-right: 5px;
        }
        .fuerza-method-get { background: #4CAF50; color: white; }
        .fuerza-method-post { background: #2196F3; color: white; }
        .fuerza-method-put { background: #FF9800; color: white; }
        .fuerza-method-delete { background: #F44336; color: white; }
        
        .test-result {
            margin-top: 10px;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        .test-result.success { background: #f0f8ff; border-color: #4CAF50; }
        .test-result.error { background: #fff5f5; border-color: #F44336; }
        
        .response-details {
            margin-top: 10px;
            font-family: monospace;
            font-size: 12px;
            background: #f5f5f5;
            padding: 10px;
            border-radius: 3px;
            max-height: 300px;
            overflow-y: auto;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Teste de todas as rotas
            $('#test-all-routes').on('click', function() {
                var button = $(this);
                var resultsDiv = $('#test-all-results');
                
                button.prop('disabled', true).text('Testando...');
                resultsDiv.html('<p>🔄 Testando todas as rotas...</p>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'test_all_api_routes',
                        nonce: '<?php echo wp_create_nonce('fuerza_api_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            displayAllTestResults(response.data, resultsDiv);
                        } else {
                            resultsDiv.html('<div class="test-result error">❌ Erro: ' + response.data + '</div>');
                        }
                    },
                    error: function() {
                        resultsDiv.html('<div class="test-result error">❌ Erro de conexão</div>');
                    },
                    complete: function() {
                        button.prop('disabled', false).text('Testar Todas as Rotas');
                    }
                });
            });
            
            // Teste de rota individual
            $('#test-single-route').on('submit', function(e) {
                e.preventDefault();
                
                var route = $('#route-select').val();
                var method = $('#method-select').val();
                var params = $('#params-input').val();
                var resultsDiv = $('#single-test-result');
                
                if (!route) {
                    alert('Por favor, selecione uma rota.');
                    return;
                }
                
                resultsDiv.html('<p>🔄 Testando rota...</p>');
                
                // Parse params JSON
                var parsedParams = {};
                if (params.trim()) {
                    try {
                        parsedParams = JSON.parse(params);
                    } catch (e) {
                        resultsDiv.html('<div class="test-result error">❌ Erro no JSON dos parâmetros: ' + e.message + '</div>');
                        return;
                    }
                }
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'test_api_route',
                        route: route,
                        method: method,
                        params: parsedParams,
                        nonce: '<?php echo wp_create_nonce('fuerza_api_nonce'); ?>'
                    },
                    success: function(response) {
                        displayTestResult(response, resultsDiv);
                    },
                    error: function() {
                        resultsDiv.html('<div class="test-result error">❌ Erro de conexão</div>');
                    }
                });
            });
            
            // Botões de teste rápido na tabela
            $('.test-single-route-btn').on('click', function() {
                var route = $(this).data('route');
                var methods = $(this).data('methods').split(',');
                
                $('#route-select').val(route);
                $('#method-select').val(methods[0]); // Usar primeiro método disponível
                $('#params-input').val('');
                
                // Scroll to form
                $('html, body').animate({
                    scrollTop: $('#test-single-route').offset().top - 100
                }, 500);
            });
            
            // Atualizar métodos disponíveis quando rota mudar
            $('#route-select').on('change', function() {
                var selectedOption = $(this).find('option:selected');
                var methods = selectedOption.data('methods');
                
                if (methods) {
                    var methodsArray = methods.split(',');
                    var methodSelect = $('#method-select');
                    
                    methodSelect.find('option').prop('disabled', true);
                    methodsArray.forEach(function(method) {
                        methodSelect.find('option[value="' + method + '"]').prop('disabled', false);
                    });
                    
                    if (methodsArray.length > 0) {
                        methodSelect.val(methodsArray[0]);
                    }
                }
            });
            
            function displayTestResult(response, container) {
                var resultClass = response.success ? 'success' : 'error';
                var statusIcon = response.success ? '✅' : '❌';
                
                var html = '<div class="test-result ' + resultClass + '">';
                html += '<h4>' + statusIcon + ' Resultado do Teste</h4>';
                html += '<p><strong>Status:</strong> ' + (response.status_code || 'N/A') + '</p>';
                html += '<p><strong>Tempo de Resposta:</strong> ' + (response.response_time || 'N/A') + 'ms</p>';
                html += '<p><strong>URL:</strong> <code>' + (response.url || 'N/A') + '</code></p>';
                
                if (response.parsed_body) {
                    html += '<div class="response-details">';
                    html += '<strong>Resposta:</strong><br>';
                    html += JSON.stringify(response.parsed_body, null, 2);
                    html += '</div>';
                } else if (response.body) {
                    html += '<div class="response-details">';
                    html += '<strong>Resposta:</strong><br>';
                    html += response.body;
                    html += '</div>';
                }
                
                if (response.error) {
                    html += '<p><strong>Erro:</strong> ' + response.error + '</p>';
                }
                
                html += '</div>';
                container.html(html);
            }
            
            function displayAllTestResults(results, container) {
                var html = '<h4>📊 Resultados dos Testes</h4>';
                var successCount = 0;
                var totalCount = results.length;
                
                results.forEach(function(test) {
                    if (test.result.success) successCount++;
                    
                    var resultClass = test.result.success ? 'success' : 'error';
                    var statusIcon = test.result.success ? '✅' : '❌';
                    
                    html += '<div class="test-result ' + resultClass + '" style="margin-bottom: 10px;">';
                    html += '<strong>' + statusIcon + ' ' + test.method + ' ' + test.route + '</strong>';
                    html += '<br><small>' + test.description + '</small>';
                    html += '<br>Status: ' + test.result.status_code + ' | Tempo: ' + test.result.response_time + 'ms';
                    html += '</div>';
                });
                
                var summary = '<div class="test-result ' + (successCount === totalCount ? 'success' : 'error') + '">';
                summary += '<strong>📈 Resumo: ' + successCount + '/' + totalCount + ' testes passaram</strong>';
                summary += '</div>';
                
                container.html(summary + html);
            }
        });
        </script>
        <?php
    }
    
    /**
     * Renderizar página de configurações
     */
    public function render_settings() {
        ?>
        <div class="wrap">
            <h1>⚙️ Configurações da API</h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('fuerza_api_settings');
                do_settings_sections('fuerza_api_settings');
                ?>
                
                <div class="fuerza-card">
                    <h2>Configurações Gerais</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Habilitar Cache</th>
                            <td>
                                <input type="checkbox" name="fuerza_cache_enabled" value="1" <?php checked(get_option('fuerza_cache_enabled', true)); ?> />
                                <p class="description">Habilita o sistema de cache para melhor performance.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Habilitar Rate Limiting</th>
                            <td>
                                <input type="checkbox" name="fuerza_rate_limiting_enabled" value="1" <?php checked(get_option('fuerza_rate_limiting_enabled', true)); ?> />
                                <p class="description">Protege contra abuso limitando requisições por IP.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Habilitar Logs</th>
                            <td>
                                <input type="checkbox" name="fuerza_api_logging_enabled" value="1" <?php checked(get_option('fuerza_api_logging_enabled', false)); ?> />
                                <p class="description">Registra atividades da API em logs detalhados.</p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <?php submit_button('Salvar Configurações'); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * AJAX: Obter estatísticas da API
     */
    public function get_api_stats() {
        check_ajax_referer('fuerza_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Permissão negada');
        }
        
        $monitor_stats = Fuerza_Monitor::get_instance()->get_performance_stats();
        $cache_stats = Fuerza_Cache::get_instance()->get_stats();
        $rate_limit_stats = Fuerza_Rate_Limiter::get_instance()->get_stats();
        
        $stats = [
            'total_requests' => $monitor_stats['overview']['total_requests'] ?? 0,
            'avg_response_time' => $monitor_stats['overview']['avg_response_time'] ?? 0,
            'cache_hit_rate' => $cache_stats['hit_rate'] ?? 0,
            'active_ips' => $rate_limit_stats['unique_ips'] ?? 0,
        ];
        
        wp_send_json_success($stats);
    }
    
    /**
     * AJAX: Toggle de recursos
     */
    public function toggle_feature() {
        check_ajax_referer('fuerza_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Permissão negada');
        }
        
        $feature = sanitize_text_field($_POST['feature']);
        $enabled = (bool) $_POST['enabled'];
        
        $option_map = [
            'cache' => 'fuerza_cache_enabled',
            'rate_limit' => 'fuerza_rate_limiting_enabled',
            'logging' => 'fuerza_api_logging_enabled',
            'monitoring' => 'fuerza_api_monitoring_enabled',
            'docs' => 'fuerza_api_docs_enabled',
        ];
        
        if (isset($option_map[$feature])) {
            update_option($option_map[$feature], $enabled);
            wp_send_json_success('Configuração atualizada');
        } else {
            wp_send_json_error('Recurso inválido');
        }
    }
    
    /**
     * AJAX: Testar todas as rotas da API
     */
    public function ajax_test_all_routes() {
        check_ajax_referer('fuerza_api_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Permissão negada');
        }
        
        $api_tester = Fuerza_API_Tester::get_instance();
        $results = $api_tester->test_all_routes();
        
        wp_send_json_success($results);
    }
}

// Inicializar dashboard administrativo
Fuerza_Admin_Dashboard::get_instance();
