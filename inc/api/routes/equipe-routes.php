<?php
/**
 * Rotas da API para equipes
 * 
 * Gerado automaticamente em 2025-08-17 17:01:05
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once get_template_directory() . '/inc/api/handlers/class-equipe-handler.php';

// Obter instância do gerenciador de API
$api_manager = API_Manager::get_instance();

// Registrar rota para listar equipes
$api_manager->add_route(
    '/equipes',
    'GET',
    [equipe_Handler::class, 'get_equipes'],
    equipe_Handler::validate_equipes_params(),
    [$api_manager, 'public_permissions'] // Permitir acesso público
);

// Registrar rota para obter equipe específico
$api_manager->add_route(
    '/equipes/(?P<id>\d+)',
    'GET',
    [equipe_Handler::class, 'get_equipe'],
    equipe_Handler::validate_equipe_params(),
    [$api_manager, 'public_permissions'] // Permitir acesso público
);