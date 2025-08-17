<?php
/**
 * Custom Post Type: equipes
 * 
 * Gerado automaticamente em 2025-08-17 17:01:05
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

// Registrar CPT de equipes usando o sistema dinâmico
Content_Manager::register_cpt('equipe', [
    'singular_name' => 'equipe',
    'plural_name' => 'equipes',
    'description' => 'Gerenciar equipes do site',
    'public' => true,
    'show_in_rest' => true,
    'rest_base' => 'equipe',
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
        'slug' => 'equipe',
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
        'equipe_status' => [
            'title' => 'Status',
            'callback' => function($column, $post_id) {
                if ($column === 'equipe_status') {
                    echo '<span style="color: green;">●</span> Ativo';
                }
            }
        ]
    ],
    
    // Hooks personalizados
    'hooks' => [
        'save_post_equipe' => function($post_id) {
            // Lógica executada quando um equipe é salvo
            if (function_exists('wp_cache_delete')) {
                wp_cache_delete('equipe_list', 'fuerza_theme');
            }
        }
    ],
    
    // Labels personalizados específicos
    'labels' => [
        'featured_image' => 'Imagem do equipe',
        'set_featured_image' => 'Definir imagem do equipe',
        'remove_featured_image' => 'Remover imagem do equipe',
        'use_featured_image' => 'Usar como imagem do equipe',
    ]
]);