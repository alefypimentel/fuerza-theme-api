<?php
/**
 * Manipulador para rotas de eventos
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once get_template_directory() . '/inc/api/formatters/class-eventos-formatter.php';

class Eventos_Handler {
    
    /**
     * Obter eventos formatados
     */
    public static function get_eventos($request) {
        $per_page = $request->get_param('per_page');
        $page = $request->get_param('page');
        $orderby = $request->get_param('orderby');
        $order = $request->get_param('order');
        $categoria = $request->get_param('categoria');
        $tag = $request->get_param('tag');
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

        // Adicionar filtros de taxonomia se especificados
        if ($categoria) {
            $args['tax_query'][] = [
                'taxonomy' => 'categoria_evento',
                'field' => 'slug',
                'terms' => $categoria,
            ];
        }

        if ($tag) {
            $args['tax_query'][] = [
                'taxonomy' => 'tag_evento',
                'field' => 'slug',
                'terms' => $tag,
            ];
        }

        // Adicionar busca se especificada
        if ($busca) {
            $args['s'] = $busca;
        }

        // Se houver múltiplas queries de taxonomia, usar AND
        if (isset($args['tax_query']) && count($args['tax_query']) > 1) {
            $args['tax_query']['relation'] = 'AND';
        }

        // Executar query
        $query = new WP_Query($args);
        $eventos = [];

        if ($query->have_posts()) {
            $eventos = Eventos_Formatter::format_eventos($query->posts);
        }

        // Formatar resposta com paginação
        return [
            'eventos' => $eventos,
            'paginacao' => Eventos_Formatter::format_pagination($query, $page, $per_page),
        ];
    }
    
    /**
     * Obter um evento específico
     */
    public static function get_evento($request) {
        $id = $request->get_param('id');
        
        $post = get_post($id);
        
        if (!$post || $post->post_type !== 'evento' || $post->post_status !== 'publish') {
            return new WP_Error(
                'evento_not_found',
                'Evento não encontrado.',
                ['status' => 404]
            );
        }
        
        return Eventos_Formatter::format_evento($id);
    }
    
    /**
     * Validar parâmetros da requisição de eventos
     */
    public static function validate_eventos_params() {
        return [
            'per_page' => [
                'default' => 10,
                'sanitize_callback' => 'absint',
                'validate_callback' => function($param) {
                    return is_numeric($param) && $param > 0 && $param <= 100;
                },
                'description' => 'Número de eventos por página (máximo 100)',
            ],
            'page' => [
                'default' => 1,
                'sanitize_callback' => 'absint',
                'validate_callback' => function($param) {
                    return is_numeric($param) && $param > 0;
                },
                'description' => 'Número da página',
            ],
            'orderby' => [
                'default' => 'date',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function($param) {
                    $allowed = ['date', 'title', 'menu_order', 'rand', 'modified'];
                    return in_array($param, $allowed);
                },
                'description' => 'Ordenar por: date, title, menu_order, rand, modified',
            ],
            'order' => [
                'default' => 'DESC',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function($param) {
                    return in_array(strtoupper($param), ['ASC', 'DESC']);
                },
                'description' => 'Ordem: ASC ou DESC',
            ],
            'categoria' => [
                'sanitize_callback' => 'sanitize_text_field',
                'description' => 'Filtrar por slug da categoria de evento',
            ],
            'tag' => [
                'sanitize_callback' => 'sanitize_text_field',
                'description' => 'Filtrar por slug da tag de evento',
            ],
            'busca' => [
                'sanitize_callback' => 'sanitize_text_field',
                'description' => 'Buscar eventos por termo',
            ],
        ];
    }
    
    /**
     * Validar parâmetros da requisição de evento específico
     */
    public static function validate_evento_params() {
        return [
            'id' => [
                'required' => true,
                'sanitize_callback' => 'absint',
                'validate_callback' => function($param) {
                    return is_numeric($param) && $param > 0;
                },
                'description' => 'ID do evento',
            ],
        ];
    }
}
