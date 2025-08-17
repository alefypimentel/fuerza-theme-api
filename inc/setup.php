<?php
function fuerza_theme_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    load_theme_textdomain('fuerza-theme-api', get_template_directory() . '/languages');
}
add_action('after_setup_theme', 'fuerza_theme_setup');

/**
 * Inicializar sistema de monitoramento
 */
function fuerza_initialize_monitoring() {
    // Carregar o monitor class se não estiver carregado
    if (!class_exists('Fuerza_Monitor')) {
        require_once get_template_directory() . '/inc/class-monitor.php';
    }
    
    // Inicializar as tabelas do banco de dados
    Fuerza_Monitor::initialize_database();
}
add_action('init', 'fuerza_initialize_monitoring');

/**
 * Flush rewrite rules quando necessário
 */
function fuerza_flush_rewrite_rules() {
    // Verificar se precisamos fazer flush das regras
    $version = get_option('fuerza_api_version');
    $current_version = '1.0.1'; // Incrementada para forçar update
    
    if ($version !== $current_version) {
        flush_rewrite_rules();
        update_option('fuerza_api_version', $current_version);
    }
}
add_action('init', 'fuerza_flush_rewrite_rules', 20);

/**
 * Forçar flush das regras quando o tema for ativado
 */
function fuerza_activation_flush() {
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'fuerza_activation_flush');
