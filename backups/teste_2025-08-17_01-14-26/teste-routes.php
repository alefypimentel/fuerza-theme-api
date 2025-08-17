<?php
/**
 * Rotas da API para Testes
 * 
 * Gerado automaticamente em 2025-08-17 01:13:54
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once get_template_directory() . '/inc/api/handlers/class-teste-handler.php';

// Obter instância do gerenciador de API
$api_manager = API_Manager::get_instance();

// Registrar rota para listar Testes
$api_manager->add_route(
    '/Testes',
    'GET',
    [Teste_Handler::class, 'get_Testes'],
    Teste_Handler::validate_Testes_params()
);

// Registrar rota para obter Teste específico
$api_manager->add_route(
    '/Testes/(?P<id>\d+)',
    'GET',
    [Teste_Handler::class, 'get_teste'],
    Teste_Handler::validate_teste_params()
);
