<?php
/**
 * Formatador para dados de equipes
 * 
 * Gerado automaticamente em 2025-08-17 17:01:05
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once get_template_directory() . '/inc/api/formatters/class-base-formatter.php';

class equipe_Formatter extends Base_Formatter {
    
    /**
     * Formatar dados de um equipe
     */
    public static function format_equipe($post_id = null) {
        if ($post_id) {
            $post = get_post($post_id);
            setup_postdata($post);
        } else {
            $post_id = get_the_ID();
        }
        
        // Dados básicos do post
        $item = self::format_basic_post_data($post_id);
        
        // Adicionar dados específicos
        $item['imagem_destacada'] = self::format_featured_image($post_id);
        $item['autor'] = self::format_author($post_id);
        $item['categorias'] = self::format_equipe_categories($post_id);
        $item['acf'] = self::format_acf_fields($post_id);
        
        if ($post_id !== get_the_ID()) {
            wp_reset_postdata();
        }
        
        return $item;
    }
    
    /**
     * Formatar categorias específicas
     */
    public static function format_equipe_categories($post_id) {
        $taxonomies = ['categoria_equipe', 'category'];
        return self::format_terms($post_id, $taxonomies);
    }
    
    /**
     * Formatar lista de equipes
     */
    public static function format_single($post) {
        return self::format_equipe($post->ID);
    }
    
    /**
     * Formatar múltiplos equipes
     */
    public static function format_multiple($posts) {
        $items = [];
        
        foreach ($posts as $post) {
            $items[] = self::format_single($post);
        }
        
        return $items;
    }
}