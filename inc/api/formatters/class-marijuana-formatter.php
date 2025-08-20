<?php
/**
 * Formatter for data of Marijuanas
 * 
 * Auto-generated on 2025-08-20 18:37:48
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once get_template_directory() . '/inc/api/formatters/class-base-formatter.php';

class Marijuana_Formatter extends Base_Formatter {
    
    /**
     * Format data for a Marijuana
     */
    public static function format_marijuana($post_id = null) {
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
        $item['categorias'] = self::format_marijuana_categories($post_id);
        $item['acf'] = self::format_acf_fields($post_id);
        
        if ($post_id !== get_the_ID()) {
            wp_reset_postdata();
        }
        
        return $item;
    }
    
    /**
     * Format specific categories
     */
    public static function format_marijuana_categories($post_id) {
        $taxonomies = ['categoria_marijuana', 'category'];
        return self::format_terms($post_id, $taxonomies);
    }
    
    /**
     * Format list of Marijuanas
     */
    public static function format_single($post) {
        return self::format_marijuana($post->ID);
    }
    
    /**
     * Format multiple Marijuanas
     */
    public static function format_multiple($posts) {
        $items = [];
        
        foreach ($posts as $post) {
            $items[] = self::format_single($post);
        }
        
        return $items;
    }
}