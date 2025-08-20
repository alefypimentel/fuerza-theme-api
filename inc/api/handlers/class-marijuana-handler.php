<?php
/**
 * Handler for routes of Marijuanas
 * 
 * Auto-generated on 2025-08-20 18:37:48
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once get_template_directory() . '/inc/api/formatters/class-marijuana-formatter.php';

class Marijuana_Handler {
    
    /**
     * Get formatted
     */
    public static function get_Marijuanas($request) {
        $per_page = $request->get_param('per_page');
        $page = $request->get_param('page');
        $orderby = $request->get_param('orderby');
        $order = $request->get_param('order');
        $categoria = $request->get_param('categoria');
        $busca = $request->get_param('busca');

        // Montar argumentos da query
        $args = [
            'post_type' => 'marijuana',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'orderby' => $orderby,
            'order' => $order,
        ];

        // Adicionar filtros se especificados
        if ($categoria) {
            $args['tax_query'][] = [
                'taxonomy' => 'categoria_marijuana',
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
                $items[] = Marijuana_Formatter::format_single(get_post());
            }
            wp_reset_postdata();
            
            return [
                'marijuanas' => $items,
                'pagination' => [
                    'total' => $query->found_posts,
                    'pages' => $query->max_num_pages,
                    'current_page' => $page,
                    'per_page' => $per_page,
                ],
            ];
        }
        
        wp_reset_postdata();
        return new WP_Error('no_marijuanas', 'Nenhum marijuana encontrado', ['status' => 404]);
    }
    
    /**
     * Get specific
     */
    public static function get_marijuana($request) {
        $id = absint($request->get_param('id'));
        
        if (!$id) {
            return new WP_Error('invalid_id', 'ID do marijuana é obrigatório', ['status' => 400]);
        }
        
        $item = get_post($id);
        
        if (!$item || $item->post_type !== 'marijuana' || $item->post_status !== 'publish') {
            return new WP_Error('marijuana_not_found', 'Marijuana não encontrado', ['status' => 404]);
        }
        
        return Marijuana_Formatter::format_marijuana($id);
    }
    
    /**
     * Validate parameters da requisição
     */
    public static function validate_Marijuanas_params() {
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
     * Validate parameters de Marijuana específico
     */
    public static function validate_marijuana_params() {
        return [
            'id' => [
                'required' => true,
                'sanitize_callback' => 'absint',
            ],
        ];
    }
}