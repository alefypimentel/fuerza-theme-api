<?php
/**
 * Rotas da API para eventos
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once get_template_directory() . '/inc/api/handlers/class-eventos-handler.php';

// Obter instância do gerenciador de API
$api_manager = API_Manager::get_instance();

// Registrar rota para listar eventos
$api_manager->add_route(
    '/eventos',
    'GET',
    [Eventos_Handler::class, 'get_eventos'],
    Eventos_Handler::validate_eventos_params()
);

// Registrar rota para obter evento específico
$api_manager->add_route(
    '/eventos/(?P<id>\d+)',
    'GET',
    [Eventos_Handler::class, 'get_evento'],
    Eventos_Handler::validate_evento_params()
);
