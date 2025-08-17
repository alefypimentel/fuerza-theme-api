<?php
/**
 * Formatador para dados de eventos
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once get_template_directory() . '/inc/api/formatters/class-base-formatter.php';

class Eventos_Formatter extends Base_Formatter {
    
    /**
     * Formatar dados de um evento
     */
    public static function format_evento($post_id = null) {
        if ($post_id) {
            $post = get_post($post_id);
            setup_postdata($post);
        } else {
            $post_id = get_the_ID();
        }
        
        // Dados básicos do post
        $evento = self::format_basic_post_data($post_id);
        
        // Adicionar dados específicos de eventos
        $evento['imagem_destacada'] = self::format_featured_image($post_id);
        $evento['autor'] = self::format_author($post_id);
        $evento['categorias'] = self::format_evento_categories($post_id);
        $evento['tags'] = self::format_evento_tags($post_id);
        $evento['acf'] = self::format_acf_fields($post_id);
        
        if ($post_id !== get_the_ID()) {
            wp_reset_postdata();
        }
        
        return $evento;
    }
    
    /**
     * Formatar categorias específicas de eventos
     */
    public static function format_evento_categories($post_id) {
        $taxonomies = ['categoria_evento', 'category'];
        return self::format_terms($post_id, $taxonomies);
    }
    
    /**
     * Formatar tags específicas de eventos
     */
    public static function format_evento_tags($post_id) {
        $taxonomies = ['tag_evento', 'post_tag'];
        return self::format_terms($post_id, $taxonomies);
    }
    
    /**
     * Formatar múltiplos eventos
     */
    public static function format_eventos($posts) {
        $eventos = [];
        
        foreach ($posts as $post) {
            $eventos[] = self::format_evento($post->ID);
        }
        
        return $eventos;
    }
    
    /**
     * Formatar resposta de paginação
     */
    public static function format_pagination($query, $page, $per_page) {
        return [
            'total_eventos' => $query->found_posts,
            'total_paginas' => $query->max_num_pages,
            'pagina_atual' => $page,
            'eventos_por_pagina' => $per_page,
            'tem_proxima_pagina' => $page < $query->max_num_pages,
            'tem_pagina_anterior' => $page > 1,
        ];
    }
}
