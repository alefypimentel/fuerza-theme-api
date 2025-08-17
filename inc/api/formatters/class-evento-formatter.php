<?php
/**
 * Formatador para dados de Eventos
 * 
 * Gerado automaticamente em 2025-08-17 20:23:50
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once get_template_directory() . '/inc/api/formatters/class-base-formatter.php';

class Evento_Formatter extends Base_Formatter {
    
    /**
     * Formatar dados de um Evento
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
        
        // Adicionar dados específicos
        $evento['imagem_destacada'] = self::format_featured_image($post_id);
        $evento['autor'] = self::format_author($post_id);
        $evento['categorias'] = self::format_evento_categories($post_id);
        $evento['acf'] = self::format_acf_fields($post_id);
        
        if ($post_id !== get_the_ID()) {
            wp_reset_postdata();
        }
        
        return $evento;
    }
    
    /**
     * Formatar categorias específicas
     */
    public static function format_evento_categories($post_id) {
        $taxonomies = ['categoria_evento', 'category'];
        return self::format_terms($post_id, $taxonomies);
    }
    
    /**
     * Formatar múltiplos Eventos
     */
    public static function format_Eventos($posts) {
        $Eventos = [];
        
        foreach ($posts as $post) {
            $Eventos[] = self::format_evento($post->ID);
        }
        
        return $Eventos;
    }
    
    /**
     * Formatar resposta de paginação
     */
    public static function format_pagination($query, $page, $per_page) {
        return [
            'total_Eventos' => $query->found_posts,
            'total_paginas' => $query->max_num_pages,
            'pagina_atual' => $page,
            'Eventos_por_pagina' => $per_page,
            'tem_proxima_pagina' => $page < $query->max_num_pages,
            'tem_pagina_anterior' => $page > 1,
        ];
    }
}
