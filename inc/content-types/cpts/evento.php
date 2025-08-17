<?php
/**
 * Custom Post Type: Eventos
 * 
 * Gerado automaticamente em 2025-08-17 20:23:50
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

// Registrar CPT de Eventos usando o sistema dinâmico
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
    'menu_icon' => 'dashicons-admin-post',
    'menu_position' => 20,
    'hierarchical' => false,
    'query_var' => true,
    'capability_type' => 'post',
    'map_meta_cap' => true,
    
    // Configurações administrativas personalizadas
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
    
    // Hooks personalizados
    'hooks' => [
        'save_post_evento' => function($post_id) {
            // Lógica executada quando um Evento é salvo
            if (function_exists('wp_cache_delete')) {
                wp_cache_delete('evento_list', 'fuerza_theme');
            }
        }
    ],
    
    // Labels personalizados específicos
    'labels' => [
        'featured_image' => 'Imagem do Evento',
        'set_featured_image' => 'Definir imagem do Evento',
        'remove_featured_image' => 'Remover imagem do Evento',
        'use_featured_image' => 'Usar como imagem do Evento',
    ]
]);
