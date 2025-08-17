<?php
/**
 * Manipulador para rotas de equipes
 * 
 * Gerado automaticamente em 2025-08-17 17:01:05
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once get_template_directory() . '/inc/api/formatters/class-equipe-formatter.php';

class equipe_Handler {
    
    /**
     * Obter equipes formatados
     */
    public static function get_equipes($request) {
        $per_page = $request->get_param('per_page');
        $page = $request->get_param('page');
        $orderby = $request->get_param('orderby');
        $order = $request->get_param('order');
        $categoria = $request->get_param('categoria');
        $busca = $request->get_param('busca');

        // Montar argumentos da query
        $args = [
            'post_type' => 'equipe',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'orderby' => $orderby,
            'order' => $order,
        ];

        // Adicionar filtros se especificados
        if ($categoria) {
            $args['tax_query'][] = [
                'taxonomy' => 'categoria_equipe',
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
                $items[] = equipe_Formatter::format_single(get_post());
            }
            wp_reset_postdata();
            
            return [
                'equipes' => $items,
                'pagination' => [
                    'total' => $query->found_posts,
                    'pages' => $query->max_num_pages,
                    'current_page' => $page,
                    'per_page' => $per_page,
                ],
            ];
        }
        
        wp_reset_postdata();
        return new WP_Error('no_equipes', 'Nenhum equipe encontrado', ['status' => 404]);
    }
    
    /**
     * Obter equipe específico
     */
    public static function get_equipe($request) {
        $id = absint($request->get_param('id'));
        
        if (!$id) {
            return new WP_Error('invalid_id', 'ID do equipe é obrigatório', ['status' => 400]);
        }
        
        $item = get_post($id);
        
        if (!$item || $item->post_type !== 'equipe' || $item->post_status !== 'publish') {
            return new WP_Error('equipe_not_found', 'equipe não encontrado', ['status' => 404]);
        }
        
        return equipe_Formatter::format_equipe($id);
    }
    
    /**
     * Validar parâmetros da requisição
     */
    public static function validate_equipes_params() {
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
     * Validar parâmetros de equipe específico
     */
    public static function validate_equipe_params() {
        return [
            'id' => [
                'required' => true,
                'sanitize_callback' => 'absint',
            ],
        ];
    }
}