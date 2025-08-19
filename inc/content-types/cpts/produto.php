<?php
/**
 * Custom Post Type: Produtos
 * 
 * Auto-generated on 2025-08-18 20:26:43
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

// Register Produtos CPT using the dynamic system
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
    'menu_icon' => 'dashicons-store',
    'menu_position' => 20,
    'hierarchical' => false,
    'query_var' => true,
    'capability_type' => 'post',
    'map_meta_cap' => true,
    
    // Custom administrative settings
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
    
    // Custom hooks
    'hooks' => [
        'save_post_produto' => function($post_id) {
            // Logic executed when um Produto é salvo
            if (function_exists('wp_cache_delete')) {
                wp_cache_delete('produto_list', 'fuerza_theme');
            }
        }
    ],
    
    // Specific custom labels
    'labels' => [
        'featured_image' => 'Imagem do Produto',
        'set_featured_image' => 'Definir imagem do Produto',
        'remove_featured_image' => 'Remover imagem do Produto',
        'use_featured_image' => 'Usar como imagem do Produto',
    ]
]);