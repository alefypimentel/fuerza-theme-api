<?php
/**
 * Funções principais do tema Meu Tema API
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
