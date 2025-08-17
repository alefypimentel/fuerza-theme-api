<?php
/**
 * Taxonomia: Categoria de Eventos
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

// Registrar Taxonomia de Categoria de Eventos usando o sistema dinâmico
Content_Manager::register_taxonomy('categoria_evento', ['evento'], [
    'singular_name' => 'Categoria de Evento',
    'plural_name' => 'Categorias de Eventos',
    'description' => 'Categorias para organizar eventos',
    'hierarchical' => true,
    'public' => true,
    'show_in_rest' => true,
    'rest_base' => 'categoria_evento',
    'show_ui' => true,
    'show_in_menu' => true,
    'show_in_nav_menus' => true,
    'show_tagcloud' => true,
    'show_in_quick_edit' => true,
    'show_admin_column' => true,
    'query_var' => true,
    'rewrite' => [
        'slug' => 'categoria-evento',
        'with_front' => false,
        'hierarchical' => true,
    ],
    
    // Campos meta personalizados para termos
    'term_meta_fields' => [
        [
            'key' => 'categoria_cor',
            'label' => 'Cor da Categoria',
            'type' => 'color',
            'description' => 'Cor que representa esta categoria de evento'
        ],
        [
            'key' => 'categoria_icone',
            'label' => 'Ícone da Categoria',
            'type' => 'text',
            'description' => 'Classe CSS do ícone (ex: fas fa-music)'
        ],
        [
            'key' => 'categoria_destaque',
            'label' => 'Categoria em Destaque',
            'type' => 'select',
            'options' => [
                'nao' => 'Não',
                'sim' => 'Sim'
            ],
            'description' => 'Marcar como categoria em destaque'
        ]
    ],
    
    // Colunas administrativas personalizadas
    'admin_columns' => [
        'categoria_cor' => [
            'title' => 'Cor',
            'callback' => function($content, $column, $term_id) {
                if ($column === 'categoria_cor') {
                    $cor = get_term_meta($term_id, 'categoria_cor', true);
                    if ($cor) {
                        return '<span style="display:inline-block;width:20px;height:20px;background-color:' . esc_attr($cor) . ';border-radius:50%;border:1px solid #ddd;"></span> ' . esc_html($cor);
                    }
                    return '—';
                }
                return $content;
            }
        ],
        'categoria_destaque' => [
            'title' => 'Destaque',
            'callback' => function($content, $column, $term_id) {
                if ($column === 'categoria_destaque') {
                    $destaque = get_term_meta($term_id, 'categoria_destaque', true);
                    if ($destaque === 'sim') {
                        return '<span style="color:#0073aa;">★ Destaque</span>';
                    }
                    return '—';
                }
                return $content;
            }
        ]
    ],
    
    // Hooks personalizados
    'hooks' => [
        'created_categoria_evento' => function($term_id) {
            // Hook executado quando uma categoria é criada
            // Limpar cache relacionado
            if (function_exists('wp_cache_delete')) {
                wp_cache_delete('categorias_evento_list', 'fuerza_theme');
            }
        },
        
        'edited_categoria_evento' => function($term_id) {
            // Hook executado quando uma categoria é editada
            if (function_exists('wp_cache_delete')) {
                wp_cache_delete('categorias_evento_list', 'fuerza_theme');
            }
        },
        
        'delete_categoria_evento' => function($term_id) {
            // Hook executado quando uma categoria é deletada
            if (function_exists('wp_cache_delete')) {
                wp_cache_delete('categorias_evento_list', 'fuerza_theme');
            }
        }
    ],
    
    // Labels personalizados específicos
    'labels' => [
        'name' => 'Categorias de Eventos',
        'singular_name' => 'Categoria de Evento',
        'menu_name' => 'Categorias',
        'all_items' => 'Todas as Categorias',
        'edit_item' => 'Editar Categoria',
        'view_item' => 'Ver Categoria',
        'update_item' => 'Atualizar Categoria',
        'add_new_item' => 'Adicionar Nova Categoria',
        'new_item_name' => 'Nome da Nova Categoria',
        'parent_item' => 'Categoria Pai',
        'parent_item_colon' => 'Categoria Pai:',
        'search_items' => 'Buscar Categorias',
        'not_found' => 'Nenhuma categoria encontrada.',
    ]
]);
