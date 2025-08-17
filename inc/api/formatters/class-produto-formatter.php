<?php
/**
 * Formatador para dados de Produtos
 * 
 * Gerado automaticamente em 2025-08-17 01:02:25
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once get_template_directory() . '/inc/api/formatters/class-base-formatter.php';

class Produto_Formatter extends Base_Formatter {
    
    /**
     * Formatar dados de um Produto
     */
    public static function format_produto($post_id = null) {
        if ($post_id) {
            $post = get_post($post_id);
            setup_postdata($post);
        } else {
            $post_id = get_the_ID();
        }
        
        // Dados básicos do post
        $produto = self::format_basic_post_data($post_id);
        
        // Adicionar dados específicos
        $produto['imagem_destacada'] = self::format_featured_image($post_id);
        $produto['autor'] = self::format_author($post_id);
        $produto['categorias'] = self::format_produto_categories($post_id);
        $produto['acf'] = self::format_acf_fields($post_id);
        
        if ($post_id !== get_the_ID()) {
            wp_reset_postdata();
        }
        
        return $produto;
    }
    
    /**
     * Formatar categorias específicas
     */
    public static function format_produto_categories($post_id) {
        $taxonomies = ['categoria_produto', 'category'];
        return self::format_terms($post_id, $taxonomies);
    }
    
    /**
     * Formatar múltiplos Produtos
     */
    public static function format_Produtos($posts) {
        $Produtos = [];
        
        foreach ($posts as $post) {
            $Produtos[] = self::format_produto($post->ID);
        }
        
        return $Produtos;
    }
    
    /**
     * Formatar resposta de paginação
     */
    public static function format_pagination($query, $page, $per_page) {
        return [
            'total_Produtos' => $query->found_posts,
            'total_paginas' => $query->max_num_pages,
            'pagina_atual' => $page,
            'Produtos_por_pagina' => $per_page,
            'tem_proxima_pagina' => $page < $query->max_num_pages,
            'tem_pagina_anterior' => $page > 1,
        ];
    }
}
