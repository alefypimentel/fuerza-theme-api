<?php
/**
 * Formatador para dados de Testes
 * 
 * Gerado automaticamente em 2025-08-17 01:13:54
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once get_template_directory() . '/inc/api/formatters/class-base-formatter.php';

class Teste_Formatter extends Base_Formatter {
    
    /**
     * Formatar dados de um Teste
     */
    public static function format_teste($post_id = null) {
        if ($post_id) {
            $post = get_post($post_id);
            setup_postdata($post);
        } else {
            $post_id = get_the_ID();
        }
        
        // Dados básicos do post
        $teste = self::format_basic_post_data($post_id);
        
        // Adicionar dados específicos
        $teste['imagem_destacada'] = self::format_featured_image($post_id);
        $teste['autor'] = self::format_author($post_id);
        $teste['categorias'] = self::format_teste_categories($post_id);
        $teste['acf'] = self::format_acf_fields($post_id);
        
        if ($post_id !== get_the_ID()) {
            wp_reset_postdata();
        }
        
        return $teste;
    }
    
    /**
     * Formatar categorias específicas
     */
    public static function format_teste_categories($post_id) {
        $taxonomies = ['categoria_teste', 'category'];
        return self::format_terms($post_id, $taxonomies);
    }
    
    /**
     * Formatar múltiplos Testes
     */
    public static function format_Testes($posts) {
        $Testes = [];
        
        foreach ($posts as $post) {
            $Testes[] = self::format_teste($post->ID);
        }
        
        return $Testes;
    }
    
    /**
     * Formatar resposta de paginação
     */
    public static function format_pagination($query, $page, $per_page) {
        return [
            'total_Testes' => $query->found_posts,
            'total_paginas' => $query->max_num_pages,
            'pagina_atual' => $page,
            'Testes_por_pagina' => $per_page,
            'tem_proxima_pagina' => $page < $query->max_num_pages,
            'tem_pagina_anterior' => $page > 1,
        ];
    }
}
