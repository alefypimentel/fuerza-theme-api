<?php
/**
 * Rotas da API para Produtos
 * 
 * Gerado automaticamente em 2025-08-17 01:02:25
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once get_template_directory() . '/inc/api/handlers/class-produto-handler.php';

// Obter instância do gerenciador de API
$api_manager = API_Manager::get_instance();

// Registrar rota para listar Produtos
$api_manager->add_route(
    '/Produtos',
    'GET',
    [Produto_Handler::class, 'get_Produtos'],
    Produto_Handler::validate_Produtos_params(),
    [$api_manager, 'public_permissions'] // Permitir acesso público
);

// Registrar rota para obter Produto específico
$api_manager->add_route(
    '/Produtos/(?P<id>\d+)',
    'GET',
    [Produto_Handler::class, 'get_produto'],
    Produto_Handler::validate_produto_params(),
    [$api_manager, 'public_permissions'] // Permitir acesso público
);
