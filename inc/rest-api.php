<?php
/**
 * Inicializador da API REST personalizada
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

// Carregar o gerenciador principal da API
require_once get_template_directory() . '/inc/api/class-api-manager.php';

/**
 * Função de compatibilidade para themes antigos
 * 
 * @deprecated Usar API_Manager diretamente
 */
function fuerza_theme_register_api_routes() {
    // Manter compatibilidade, mas a funcionalidade agora é gerenciada pelo API_Manager
    do_action('fuerza_theme_api_loaded');
}

// Hook mantido para compatibilidade
add_action('rest_api_init', 'fuerza_theme_register_api_routes');

/**
 * Função utilitária para obter o namespace da API
 */
function fuerza_theme_get_api_namespace() {
    return API_Manager::get_namespace();
}

/**
 * Função utilitária para adicionar novas rotas
 */
function fuerza_theme_add_api_route($endpoint, $methods, $callback, $args = []) {
    $api_manager = API_Manager::get_instance();
    $api_manager->add_route($endpoint, $methods, $callback, $args);
}