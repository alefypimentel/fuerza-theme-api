<?php
/**
 * Inicializador de Custom Post Types
 * 
 * @package FuerzaThemeAPI
 * @deprecated Use Content_Manager em vez deste arquivo
 */

if (!defined('ABSPATH')) {
    exit;
}

// Carregar o novo sistema de gerenciamento de conteúdo
require_once get_template_directory() . '/inc/content-types/class-content-manager.php';

/**
 * Função de compatibilidade para temas antigos
 * 
 * @deprecated Usar Content_Manager::register_cpt() diretamente
 */
function fuerza_theme_register_cpt_eventos() {
    // Esta função é mantida apenas para compatibilidade
    // A funcionalidade agora é gerenciada pelo Content_Manager
    do_action('fuerza_theme_cpts_loaded');
}

// Hook mantido para compatibilidade
add_action('init', 'fuerza_theme_register_cpt_eventos');