<?php
/**
 * Custom Post Type: Testes
 * 
 * Gerado automaticamente em 2025-08-17 01:13:54
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

// Registrar CPT de Testes usando o sistema dinâmico
Content_Manager::register_cpt('teste', [
    'singular_name' => 'Teste',
    'plural_name' => 'Testes',
    'description' => 'Gerenciar Testes do site',
    'public' => true,
    'show_in_rest' => true,
    'rest_base' => 'teste',
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
        'slug' => 'teste',
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
        'teste_status' => [
            'title' => 'Status',
            'callback' => function($column, $post_id) {
                if ($column === 'teste_status') {
                    echo '<span style="color: green;">●</span> Ativo';
                }
            }
        ]
    ],
    
    // Hooks personalizados
    'hooks' => [
        'save_post_teste' => function($post_id) {
            // Lógica executada quando um Teste é salvo
            if (function_exists('wp_cache_delete')) {
                wp_cache_delete('teste_list', 'fuerza_theme');
            }
        }
    ],
    
    // Labels personalizados específicos
    'labels' => [
        'featured_image' => 'Imagem do Teste',
        'set_featured_image' => 'Definir imagem do Teste',
        'remove_featured_image' => 'Remover imagem do Teste',
        'use_featured_image' => 'Usar como imagem do Teste',
    ]
]);
