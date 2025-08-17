<?php
/**
 * Formatador para dados de Eventos
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
        
        // Adicionar dados específicos
        $evento['imagem_destacada'] = self::format_featured_image($post_id);
        $evento['autor'] = self::format_author($post_id);
        $evento['categorias'] = self::format_evento_categories($post_id);
        $evento['acf'] = self::format_acf_fields($post_id);
        
        // Dados específicos de eventos
        $evento['data_evento'] = self::get_event_date($post_id);
        $evento['localizacao'] = self::get_event_location($post_id);
        
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
     * Formatar lista de eventos
     */
    public static function format_single($post) {
        return self::format_evento($post->ID);
    }
    
    /**
     * Obter data do evento (via ACF se disponível)
     */
    private static function get_event_date($post_id) {
        // Tentar obter via ACF primeiro
        if (function_exists('get_field')) {
            $data_evento = get_field('data_evento', $post_id);
            if ($data_evento) {
                return $data_evento;
            }
            
            // Tentar campo 'agenda' mencionado no CPT
            $agenda = get_field('agenda', $post_id);
            if ($agenda) {
                return $agenda;
            }
        }
        
        // Fallback para data de publicação
        return get_the_date('Y-m-d H:i:s', $post_id);
    }
    
    /**
     * Obter localização do evento (via ACF se disponível)
     */
    private static function get_event_location($post_id) {
        if (function_exists('get_field')) {
            $localizacao = get_field('localizacao', $post_id);
            if ($localizacao) {
                return $localizacao;
            }
            
            // Tentar outros nomes comuns
            $local = get_field('local', $post_id);
            if ($local) {
                return $local;
            }
            
            $endereco = get_field('endereco', $post_id);
            if ($endereco) {
                return $endereco;
            }
        }
        
        return null;
    }
    
    /**
     * Formatar múltiplos eventos
     */
    public static function format_multiple($posts) {
        $eventos = [];
        
        foreach ($posts as $post) {
            $eventos[] = self::format_single($post);
        }
        
        return $eventos;
    }
}
