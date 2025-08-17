<?php
/**
 * Funções principais do tema Fuerza Theme API
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

// Carregar configurações básicas do tema
require_once get_template_directory() . '/inc/setup.php';

// Carregar custom post types e taxonomias
require_once get_template_directory() . '/inc/custom-post-types.php';
require_once get_template_directory() . '/inc/taxonomies.php';

// Carregar sistema de API
require_once get_template_directory() . '/inc/rest-api.php';

// Carregar funcionalidades administrativas
require_once get_template_directory() . '/inc/admin.php';
require_once get_template_directory() . '/inc/helpers.php';

// Carregar sistema de logging, monitoramento, cache, rate limiting, documentação e performance
require_once get_template_directory() . '/inc/class-logger.php';
require_once get_template_directory() . '/inc/class-monitor.php';
require_once get_template_directory() . '/inc/class-cache.php';
require_once get_template_directory() . '/inc/class-rate-limiter.php';
require_once get_template_directory() . '/inc/class-api-docs.php';
require_once get_template_directory() . '/inc/class-performance.php';
require_once get_template_directory() . '/inc/class-api-tester.php';

// Carregar dashboard administrativo
require_once get_template_directory() . '/inc/admin-dashboard.php';
