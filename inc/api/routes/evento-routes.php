<?php
/**
 * API Routes for Eventos
 * 
 * Auto-generated on 2025-08-17 20:49:44
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once get_template_directory() . '/inc/api/handlers/class-evento-handler.php';

// Obter instância do gerenciador de API
$api_manager = API_Manager::get_instance();

// Registrar rota para listar Eventos
$api_manager->add_route(
    '/Eventos',
    'GET',
    [Evento_Handler::class, 'get_Eventos'],
    Evento_Handler::validate_Eventos_params(),
    [$api_manager, 'public_permissions'] // Permitir acesso público
);

// Registrar rota para obter Evento específico
$api_manager->add_route(
    '/Eventos/(?P<id>\d+)',
    'GET',
    [Evento_Handler::class, 'get_evento'],
    Evento_Handler::validate_evento_params(),
    [$api_manager, 'public_permissions'] // Permitir acesso público
);