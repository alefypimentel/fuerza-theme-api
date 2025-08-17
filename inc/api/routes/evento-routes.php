<?php
/**
 * Rotas da API para Eventos
 * 
 * Gerado automaticamente em 2025-08-17 20:23:50
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
    Evento_Handler::validate_Eventos_params()
);

// Registrar rota para obter Evento específico
$api_manager->add_route(
    '/Eventos/(?P<id>\d+)',
    'GET',
    [Evento_Handler::class, 'get_evento'],
    Evento_Handler::validate_evento_params()
);
