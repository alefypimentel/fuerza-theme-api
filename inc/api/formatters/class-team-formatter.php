<?php
/**
 * Formatador para dados de Teams
 * 
 * Gerado automaticamente em 2025-08-17 14:49:54
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once get_template_directory() . '/inc/api/formatters/class-base-formatter.php';

class Team_Formatter extends Base_Formatter {
    
    /**
     * Formatar dados de um Team
     */
    public static function format_team($post_id = null) {
        if ($post_id) {
            $post = get_post($post_id);
            setup_postdata($post);
        } else {
            $post_id = get_the_ID();
        }
        
        // Dados básicos do post
        $team = self::format_basic_post_data($post_id);
        
        // Adicionar dados específicos
        $team['imagem_destacada'] = self::format_featured_image($post_id);
        $team['autor'] = self::format_author($post_id);
        $team['categorias'] = self::format_team_categories($post_id);
        $team['acf'] = self::format_acf_fields($post_id);
        
        if ($post_id !== get_the_ID()) {
            wp_reset_postdata();
        }
        
        return $team;
    }
    
    /**
     * Formatar categorias específicas
     */
    public static function format_team_categories($post_id) {
        $taxonomies = ['categoria_team', 'category'];
        return self::format_terms($post_id, $taxonomies);
    }
    
    /**
     * Formatar múltiplos Teams
     */
    public static function format_Teams($posts) {
        $Teams = [];
        
        foreach ($posts as $post) {
            $Teams[] = self::format_team($post->ID);
        }
        
        return $Teams;
    }
    
    /**
     * Formatar resposta de paginação
     */
    public static function format_pagination($query, $page, $per_page) {
        return [
            'total_Teams' => $query->found_posts,
            'total_paginas' => $query->max_num_pages,
            'pagina_atual' => $page,
            'Teams_por_pagina' => $per_page,
            'tem_proxima_pagina' => $page < $query->max_num_pages,
            'tem_pagina_anterior' => $page > 1,
        ];
    }
}
