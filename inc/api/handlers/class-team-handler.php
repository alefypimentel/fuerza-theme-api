<?php
/**
 * Manipulador para rotas de Teams
 * 
 * Gerado automaticamente em 2025-08-17 14:49:54
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once get_template_directory() . '/inc/api/formatters/class-team-formatter.php';

class Team_Handler {
    
    /**
     * Obter Teams formatados
     */
    public static function get_Teams($request) {
        $per_page = $request->get_param('per_page');
        $page = $request->get_param('page');
        $orderby = $request->get_param('orderby');
        $order = $request->get_param('order');
        $categoria = $request->get_param('categoria');
        $busca = $request->get_param('busca');

        // Montar argumentos da query
        $args = [
            'post_type' => 'team',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'orderby' => $orderby,
            'order' => $order,
        ];

        // Adicionar filtros se especificados
        if ($categoria) {
            $args['tax_query'][] = [
                'taxonomy' => 'categoria_team',
                'field' => 'slug',
                'terms' => $categoria,
            ];
        }

        if ($busca) {
            $args['s'] = $busca;
        }

        // Executar query
        $query = new WP_Query($args);
        $Teams = [];

        if ($query->have_posts()) {
            $Teams = Team_Formatter::format_Teams($query->posts);
        }

        // Formatar resposta com paginação
        return [
            'Teams' => $Teams,
            'paginacao' => Team_Formatter::format_pagination($query, $page, $per_page),
        ];
    }
    
    /**
     * Obter um Team específico
     */
    public static function get_team($request) {
        $id = $request->get_param('id');
        
        $post = get_post($id);
        
        if (!$post || $post->post_type !== 'team' || $post->post_status !== 'publish') {
            return new WP_Error(
                'team_not_found',
                'Team não encontrado.',
                ['status' => 404]
            );
        }
        
        return Team_Formatter::format_team($id);
    }
    
    /**
     * Validar parâmetros da requisição
     */
    public static function validate_Teams_params() {
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
     * Validar parâmetros de Team específico
     */
    public static function validate_team_params() {
        return [
            'id' => [
                'required' => true,
                'sanitize_callback' => 'absint',
            ],
        ];
    }
}
