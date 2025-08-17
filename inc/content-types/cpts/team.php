<?php
/**
 * Custom Post Type: Teams
 * 
 * Gerado automaticamente em 2025-08-17 14:49:54
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

// Registrar CPT de Teams usando o sistema dinâmico
Content_Manager::register_cpt('team', [
    'singular_name' => 'Team',
    'plural_name' => 'Teams',
    'description' => 'Gerenciar Teams do site',
    'public' => true,
    'show_in_rest' => true,
    'rest_base' => 'team',
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
        'slug' => 'team',
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
        'team_status' => [
            'title' => 'Status',
            'callback' => function($column, $post_id) {
                if ($column === 'team_status') {
                    echo '<span style="color: green;">●</span> Ativo';
                }
            }
        ]
    ],
    
    // Hooks personalizados
    'hooks' => [
        'save_post_team' => function($post_id) {
            // Lógica executada quando um Team é salvo
            if (function_exists('wp_cache_delete')) {
                wp_cache_delete('team_list', 'fuerza_theme');
            }
        }
    ],
    
    // Labels personalizados específicos
    'labels' => [
        'featured_image' => 'Imagem do Team',
        'set_featured_image' => 'Definir imagem do Team',
        'remove_featured_image' => 'Remover imagem do Team',
        'use_featured_image' => 'Usar como imagem do Team',
    ]
]);
