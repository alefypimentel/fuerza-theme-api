<?php
/**
 * Formatter for data of Eventos
 * 
 * Auto-generated on 2025-08-17 20:49:44
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once get_template_directory() . '/inc/api/formatters/class-base-formatter.php';

class Evento_Formatter extends Base_Formatter {
    
    /**
     * Format data for a Evento
     */
    public static function format_evento($post_id = null) {
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
        $item['categorias'] = self::format_evento_categories($post_id);
        $item['acf'] = self::format_acf_fields($post_id);
        
        if ($post_id !== get_the_ID()) {
            wp_reset_postdata();
        }
        
        return $item;
    }
    
    /**
     * Format specific categories
     */
    public static function format_evento_categories($post_id) {
        $taxonomies = ['categoria_evento', 'category'];
        return self::format_terms($post_id, $taxonomies);
    }
    
    /**
     * Format list of Eventos
     */
    public static function format_single($post) {
        return self::format_evento($post->ID);
    }
    
    /**
     * Format multiple Eventos
     */
    public static function format_multiple($posts) {
        $items = [];
        
        foreach ($posts as $post) {
            $items[] = self::format_single($post);
        }
        
        return $items;
    }
}