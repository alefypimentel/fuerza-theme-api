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
        
        wp_enqueue_style('fuerza-admin', '', [], '1.0.0');
        
        // Adicionar CSS inline
        wp_add_inline_style('fuerza-admin', $this->get_admin_css());
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
        .fuerza-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .fuerza-info-card {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        .fuerza-info-title {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 8px;
            display: block;
        }
        .fuerza-info-description {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        .fuerza-api-links {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 20px;
        }
        .fuerza-api-link {
            background: #f0f0f1;
            color: #2c3338;
            padding: 10px 15px;
            border-radius: 5px;
            text-decoration: none;
            transition: background 0.3s;
        }
        .fuerza-api-link:hover {
            background: #dcdcde;
            color: #2c3338;
        }
        ';
    }
    
    /**
     * Renderizar dashboard principal
     */
    public function render_dashboard() {
        ?>
        <div class="wrap fuerza-dashboard">
            <h1>🚀 Fuerza API Dashboard</h1>
            
            <div class="fuerza-info-grid">
                <div class="fuerza-info-card">
                    <span class="fuerza-info-title">📡 API REST</span>
                    <span class="fuerza-info-description">Sistema de API simplificado e eficiente</span>
                </div>
                
                <div class="fuerza-info-card">
                    <span class="fuerza-info-title">🏗️ Custom Post Types</span>
                    <span class="fuerza-info-description">Gerenciamento de conteúdo estruturado</span>
                </div>
                
                <div class="fuerza-info-card">
                    <span class="fuerza-info-title">🎯 Endpoints</span>
                    <span class="fuerza-info-description">Rotas organizadas e documentadas</span>
                </div>
                
                <div class="fuerza-info-card">
                    <span class="fuerza-info-title">⚡ Performance</span>
                    <span class="fuerza-info-description">Sistema otimizado sem overhead</span>
                </div>
            </div>
            
            <div class="fuerza-card">
                <h2>📖 Informações da API</h2>
                <p>O Fuerza Theme API fornece uma interface REST simplificada para acessar o conteúdo do seu site WordPress.</p>
                
                <h3>Namespace da API:</h3>
                <code><?php echo API_Manager::get_namespace(); ?></code>
                
                <h3>URL Base:</h3>
                <code><?php echo home_url('/wp-json/' . API_Manager::get_namespace()); ?></code>
                
                <div class="fuerza-api-links">
                    <a href="<?php echo home_url('/wp-json/' . API_Manager::get_namespace() . '/ping'); ?>" target="_blank" class="fuerza-api-link">
                        🏓 Testar API (Ping)
                    </a>
                    <a href="<?php echo admin_url('edit.php?post_type=evento'); ?>" class="fuerza-api-link">
                        📝 Gerenciar Eventos
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=fuerza-api-settings'); ?>" class="fuerza-api-link">
                        ⚙️ Configurações
                    </a>
                </div>
            </div>
            
            <div class="fuerza-card">
                <h2>🔗 Endpoints Disponíveis</h2>
                <p>Principais endpoints da API:</p>
                
                <ul>
                    <li><strong>GET</strong> <code>/ping</code> - Verifica status da API</li>
                    <li><strong>GET</strong> <code>/eventos</code> - Lista eventos</li>
                    <li><strong>GET</strong> <code>/eventos/{id}</code> - Obter evento específico</li>
                </ul>
                
                <p><em>Para mais detalhes sobre parâmetros e respostas, consulte a documentação técnica ou teste diretamente os endpoints.</em></p>
            </div>
            
            <div class="fuerza-card">
                <h2>💡 Próximos Passos</h2>
                <ul>
                    <li>Configure novos Custom Post Types conforme necessário</li>
                    <li>Teste os endpoints da API com ferramentas como Postman ou cURL</li>
                    <li>Integre a API com seu frontend ou aplicações externas</li>
                    <li>Monitore o uso através dos logs do WordPress se necessário</li>
                </ul>
            </div>
        </div>
        <?php
    }
    
    /**
     * Renderizar página de configurações
     */
    public function render_settings() {
        if (isset($_POST['submit'])) {
            check_admin_referer('fuerza_api_settings');
            
            // Salvar configurações básicas
            update_option('fuerza_api_enabled', isset($_POST['fuerza_api_enabled']));
            
            echo '<div class="notice notice-success"><p>Configurações salvas com sucesso!</p></div>';
        }
        
        $api_enabled = get_option('fuerza_api_enabled', true);
        ?>
        <div class="wrap">
            <h1>⚙️ Configurações da API</h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('fuerza_api_settings'); ?>
                
                <div class="fuerza-card">
                    <h2>Configurações Gerais</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Habilitar API</th>
                            <td>
                                <input type="checkbox" name="fuerza_api_enabled" value="1" <?php checked($api_enabled); ?> />
                                <p class="description">Habilita ou desabilita completamente a API REST do tema.</p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div class="fuerza-card">
                    <h2>Informações do Sistema</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">WordPress Version</th>
                            <td><?php echo get_bloginfo('version'); ?></td>
                        </tr>
                        <tr>
                            <th scope="row">Tema Ativo</th>
                            <td><?php echo get_option('stylesheet'); ?></td>
                        </tr>
                        <tr>
                            <th scope="row">PHP Version</th>
                            <td><?php echo PHP_VERSION; ?></td>
                        </tr>
                        <tr>
                            <th scope="row">API Namespace</th>
                            <td><?php echo API_Manager::get_namespace(); ?></td>
                        </tr>
                    </table>
                </div>
                
                <?php submit_button('Salvar Configurações'); ?>
            </form>
        </div>
        <?php
    }
}

// Inicializar dashboard administrativo
Fuerza_Admin_Dashboard::get_instance();
