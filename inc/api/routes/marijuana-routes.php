<?php
/**
 * API Routes for Marijuanas
 * 
 * Auto-generated on 2025-08-20 18:37:48
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once get_template_directory() . '/inc/api/handlers/class-marijuana-handler.php';

// Obter instância do gerenciador de API
$api_manager = API_Manager::get_instance();

// Registrar rota para listar Marijuanas
$api_manager->add_route(
    '/Marijuanas',
    'GET',
    [Marijuana_Handler::class, 'get_Marijuanas'],
    Marijuana_Handler::validate_Marijuanas_params(),
    [$api_manager, 'public_permissions'] // Permitir acesso público
);

// Registrar rota para obter Marijuana específico
$api_manager->add_route(
    '/Marijuanas/(?P<id>\d+)',
    'GET',
    [Marijuana_Handler::class, 'get_marijuana'],
    Marijuana_Handler::validate_marijuana_params(),
    [$api_manager, 'public_permissions'] // Permitir acesso público
);