<?php
/**
 * Custom Post Type: Marijuanas
 * 
 * Auto-generated on 2025-08-20 18:37:48
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

// Register Marijuanas CPT using the dynamic system
Content_Manager::register_cpt('marijuana', [
    'singular_name' => 'Marijuana',
    'plural_name' => 'Marijuanas',
    'description' => 'Gerenciar Marijuanas do site',
    'public' => true,
    'show_in_rest' => true,
    'rest_base' => 'marijuana',
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
        'slug' => 'marijuana',
        'with_front' => false,
    ],
    'menu_icon' => 'dashicons-star-filled',
    'menu_position' => 20,
    'hierarchical' => false,
    'query_var' => true,
    'capability_type' => 'post',
    'map_meta_cap' => true,
    
    // Custom administrative settings
    'admin_columns' => [
        'marijuana_status' => [
            'title' => 'Status',
            'callback' => function($column, $post_id) {
                if ($column === 'marijuana_status') {
                    echo '<span style="color: green;">●</span> Ativo';
                }
            }
        ]
    ],
    
    // Custom hooks
    'hooks' => [
        'save_post_marijuana' => function($post_id) {
            // Logic executed when um Marijuana é salvo
            if (function_exists('wp_cache_delete')) {
                wp_cache_delete('marijuana_list', 'fuerza_theme');
            }
        }
    ],
    
    // Specific custom labels
    'labels' => [
        'featured_image' => 'Imagem do Marijuana',
        'set_featured_image' => 'Definir imagem do Marijuana',
        'remove_featured_image' => 'Remover imagem do Marijuana',
        'use_featured_image' => 'Usar como imagem do Marijuana',
    ]
]);