<?php
/**
 * Custom Post Type: Produtos
 * 
 * Gerado automaticamente em 2025-08-17 01:02:25
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

// Registrar CPT de Produtos usando o sistema dinâmico
Content_Manager::register_cpt('produto', [
    'singular_name' => 'Produto',
    'plural_name' => 'Produtos',
    'description' => 'Gerenciar Produtos do site',
    'public' => true,
    'show_in_rest' => true,
    'rest_base' => 'produto',
    'supports' => [
        'title',
        'editor',
        'thumbnail',
        'excerpt',
        'author',
        'comments',
        'revisions'
    ],
    'has_archive' => true,
    'rewrite' => [
        'slug' => 'produto',
        'with_front' => false,
    ],
    'menu_icon' => 'dashicons-admin-post',
    'menu_position' => 20,
    'hierarchical' => false,
    'query_var' => true,
    'capability_type' => 'post',
    'map_meta_cap' => true,
    
    // Configurações administrativas personalizadas
    'admin_columns' => [
        'produto_status' => [
            'title' => 'Status',
            'callback' => function($column, $post_id) {
                if ($column === 'produto_status') {
                    echo '<span style="color: green;">●</span> Ativo';
                }
            }
        ]
    ],
    
    // Hooks personalizados
    'hooks' => [
        'save_post_produto' => function($post_id) {
            // Lógica executada quando um Produto é salvo
            if (function_exists('wp_cache_delete')) {
                wp_cache_delete('produto_list', 'fuerza_theme');
            }
        }
    ],
    
    // Labels personalizados específicos
    'labels' => [
        'featured_image' => 'Imagem do Produto',
        'set_featured_image' => 'Definir imagem do Produto',
        'remove_featured_image' => 'Remover imagem do Produto',
        'use_featured_image' => 'Usar como imagem do Produto',
    ]
]);
