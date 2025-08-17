<?php
/**
 * Handler for routes of Eventos
 * 
 * Auto-generated on 2025-08-17 20:49:44
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once get_template_directory() . '/inc/api/formatters/class-evento-formatter.php';

class Evento_Handler {
    
    /**
     * Get formatted
     */
    public static function get_Eventos($request) {
        $per_page = $request->get_param('per_page');
        $page = $request->get_param('page');
        $orderby = $request->get_param('orderby');
        $order = $request->get_param('order');
        $categoria = $request->get_param('categoria');
        $busca = $request->get_param('busca');

        // Montar argumentos da query
        $args = [
            'post_type' => 'evento',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'orderby' => $orderby,
            'order' => $order,
        ];

        // Adicionar filtros se especificados
        if ($categoria) {
            $args['tax_query'][] = [
                'taxonomy' => 'categoria_evento',
                'field' => 'slug',
                'terms' => $categoria,
            ];
        }

        if ($busca) {
            $args['s'] = $busca;
        }

        // Executar query
        $query = new WP_Query($args);
        
        if ($query->have_posts()) {
            $items = [];
            while ($query->have_posts()) {
                $query->the_post();
                $items[] = Evento_Formatter::format_single(get_post());
            }
            wp_reset_postdata();
            
            return [
                'eventos' => $items,
                'pagination' => [
                    'total' => $query->found_posts,
                    'pages' => $query->max_num_pages,
                    'current_page' => $page,
                    'per_page' => $per_page,
                ],
            ];
        }
        
        wp_reset_postdata();
        return new WP_Error('no_eventos', 'Nenhum evento encontrado', ['status' => 404]);
    }
    
    /**
     * Get specific
     */
    public static function get_evento($request) {
        $id = absint($request->get_param('id'));
        
        if (!$id) {
            return new WP_Error('invalid_id', 'ID do evento é obrigatório', ['status' => 400]);
        }
        
        $item = get_post($id);
        
        if (!$item || $item->post_type !== 'evento' || $item->post_status !== 'publish') {
            return new WP_Error('evento_not_found', 'Evento não encontrado', ['status' => 404]);
        }
        
        return Evento_Formatter::format_evento($id);
    }
    
    /**
     * Validate parameters da requisição
     */
    public static function validate_Eventos_params() {
        return [
            'per_page' => [
                'default' => 10,
                'sanitize_callback' => 'absint',
            ],
            'page' => [
                'default' => 1,
                'sanitize_callback' => 'absint',
            ],
            'orderby' => [
                'default' => 'date',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'order' => [
                'default' => 'DESC',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'categoria' => [
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'busca' => [
                'sanitize_callback' => 'sanitize_text_field',
            ],
        ];
    }
    
    /**
     * Validate parameters de Evento específico
     */
    public static function validate_evento_params() {
        return [
            'id' => [
                'required' => true,
                'sanitize_callback' => 'absint',
            ],
        ];
    }
}