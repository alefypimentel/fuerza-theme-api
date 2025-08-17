<?php
/**
 * Custom Post Type: Eventos
 * 
 * Auto-generated on 2025-08-17 20:49:44
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

// Register Eventos CPT using the dynamic system
Content_Manager::register_cpt('evento', [
    'singular_name' => 'Evento',
    'plural_name' => 'Eventos',
    'description' => 'Gerenciar Eventos do site',
    'public' => true,
    'show_in_rest' => true,
    'rest_base' => 'evento',
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
        'slug' => 'evento',
        'with_front' => false,
    ],
    'menu_icon' => 'dashicons-tag',
    'menu_position' => 20,
    'hierarchical' => false,
    'query_var' => true,
    'capability_type' => 'post',
    'map_meta_cap' => true,
    
    // Custom administrative settings
    'admin_columns' => [
        'evento_status' => [
            'title' => 'Status',
            'callback' => function($column, $post_id) {
                if ($column === 'evento_status') {
                    echo '<span style="color: green;">●</span> Ativo';
                }
            }
        ]
    ],
    
    // Custom hooks
    'hooks' => [
        'save_post_evento' => function($post_id) {
            // Logic executed when um Evento é salvo
            if (function_exists('wp_cache_delete')) {
                wp_cache_delete('evento_list', 'fuerza_theme');
            }
        }
    ],
    
    // Specific custom labels
    'labels' => [
        'featured_image' => 'Imagem do Evento',
        'set_featured_image' => 'Definir imagem do Evento',
        'remove_featured_image' => 'Remover imagem do Evento',
        'use_featured_image' => 'Usar como imagem do Evento',
    ]
]);