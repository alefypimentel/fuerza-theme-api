<?php
/**
 * Rotas da API para Teams
 * 
 * Gerado automaticamente em 2025-08-17 14:49:54
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once get_template_directory() . '/inc/api/handlers/class-team-handler.php';

// Obter instância do gerenciador de API
$api_manager = API_Manager::get_instance();

// Registrar rota para listar Teams
$api_manager->add_route(
    '/Teams',
    'GET',
    [Team_Handler::class, 'get_Teams'],
    Team_Handler::validate_Teams_params(),
    [$api_manager, 'public_permissions'] // Permitir acesso público
);

// Registrar rota para obter Team específico
$api_manager->add_route(
    '/Teams/(?P<id>\d+)',
    'GET',
    [Team_Handler::class, 'get_team'],
    Team_Handler::validate_team_params(),
    [$api_manager, 'public_permissions'] // Permitir acesso público
);
