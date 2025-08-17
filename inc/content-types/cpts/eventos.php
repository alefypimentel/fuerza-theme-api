<?php
/**
 * Custom Post Type: Eventos
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
    'description' => 'Gerenciar eventos do site',
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
        'revisions',
        'page-attributes'
    ],
    'has_archive' => true,
    'rewrite' => [
        'slug' => 'evento',
        'with_front' => false,
    ],
    'menu_icon' => 'dashicons-calendar-alt',
    'menu_position' => 20,
    'hierarchical' => false,
    'query_var' => true,
    'capability_type' => 'post',
    'map_meta_cap' => true,
    
    // Configurações administrativas personalizadas
    'admin_columns' => [
        'evento_data' => [
            'title' => 'Data do Evento',
            'callback' => function($column, $post_id) {
                if ($column === 'evento_data') {
                    // Se usar ACF, buscar campo de data
                    if (function_exists('get_field')) {
                        $data_evento = get_field('agenda', $post_id);
                        if ($data_evento) {
                            echo esc_html($data_evento);
                            return;
                        }
                    }
                    
                    // Fallback para data de publicação
                    echo get_the_date('d/m/Y H:i', $post_id);
                }
            }
        ],
        'evento_categorias' => [
            'title' => 'Categorias',
            'callback' => function($column, $post_id) {
                if ($column === 'evento_categorias') {
                    $categories = get_the_terms($post_id, 'categoria_evento');
                    if ($categories && !is_wp_error($categories)) {
                        $cat_names = array_map(function($cat) {
                            return $cat->name;
                        }, $categories);
                        echo implode(', ', $cat_names);
                    } else {
                        echo '—';
                    }
                }
            }
        ]
    ],
    
    // Hooks personalizados para eventos
    'hooks' => [
        'save_post_evento' => function($post_id) {
            // Hook executado quando um evento é salvo
            // Aqui você pode adicionar lógica personalizada
            
            // Exemplo: limpar cache relacionado a eventos
            if (function_exists('wp_cache_delete')) {
                wp_cache_delete('eventos_list', 'fuerza_theme');
            }
            
            // Exemplo: enviar notificação para administradores
            if (get_post_status($post_id) === 'publish') {
                // Lógica para notificação (se necessário)
            }
        },
        
        'delete_post' => function($post_id) {
            if (get_post_type($post_id) === 'evento') {
                // Limpeza quando um evento é deletado
                if (function_exists('wp_cache_delete')) {
                    wp_cache_delete('eventos_list', 'fuerza_theme');
                }
            }
        }
    ],
    
    // Labels personalizados específicos
    'labels' => [
        'featured_image' => 'Imagem do Evento',
        'set_featured_image' => 'Definir imagem do evento',
        'remove_featured_image' => 'Remover imagem do evento',
        'use_featured_image' => 'Usar como imagem do evento',
    ]
]);
