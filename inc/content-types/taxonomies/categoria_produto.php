<?php
/**
 * Taxonomia: Categoria de Produtos
 * 
 * Gerado automaticamente em 2025-08-17 01:02:25
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

// Registrar Taxonomia de Categoria de Produtos usando o sistema dinâmico
Content_Manager::register_taxonomy('categoria_produto', ['produto'], [
    'singular_name' => 'Categoria de Produto',
    'plural_name' => 'Categorias de Produtos',
    'description' => 'Categorias para organizar Produtos',
    'hierarchical' => true,
    'public' => true,
    'show_in_rest' => true,
    'rest_base' => 'categoria_produto',
    'show_ui' => true,
    'show_in_menu' => true,
    'show_in_nav_menus' => true,
    'show_tagcloud' => true,
    'show_in_quick_edit' => true,
    'show_admin_column' => true,
    'query_var' => true,
    'rewrite' => [
        'slug' => 'categoria-produto',
        'with_front' => false,
        'hierarchical' => true,
    ],
    
    // Campos meta personalizados para termos
    'term_meta_fields' => [
        [
            'key' => 'categoria_cor',
            'label' => 'Cor da Categoria',
            'type' => 'color',
            'description' => 'Cor que representa esta categoria'
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
        ]
    ],
    
    // Hooks personalizados
    'hooks' => [
        'created_categoria_produto' => function($term_id) {
            if (function_exists('wp_cache_delete')) {
                wp_cache_delete('categoria_produto_list', 'fuerza_theme');
            }
        }
    ]
]);
