<?php
/**
 * Rotas básicas da API
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

// Obter instância do gerenciador de API
$api_manager = API_Manager::get_instance();

// Registrar rota de ping
$api_manager->add_route(
    '/ping',
    'GET',
    function() {
        return [
            'status' => 'ok',
            'message' => 'API funcionando 🚀',
            'timestamp' => current_time('Y-m-d H:i:s'),
            'version' => '1.0.0'
        ];
    },
    [], // Sem parâmetros
    [$api_manager, 'public_permissions'] // Permitir acesso público
);
