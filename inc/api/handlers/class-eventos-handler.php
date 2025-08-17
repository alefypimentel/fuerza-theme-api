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
        $per_page = min(100, max(1, absint($request->get_param('per_page') ?: 10)));
        $page = max(1, absint($request->get_param('page') ?: 1));
        $orderby = sanitize_text_field($request->get_param('orderby') ?: 'date');
        $order = in_array(strtoupper($request->get_param('order') ?: 'DESC'), ['ASC', 'DESC']) 
            ? strtoupper($request->get_param('order')) : 'DESC';
        $categoria = sanitize_text_field($request->get_param('categoria') ?: '');
        $tag = sanitize_text_field($request->get_param('tag') ?: '');
        $busca = sanitize_text_field($request->get_param('busca') ?: '');
        
        // Verificar cache
        $cache_params = compact('per_page', 'page', 'orderby', 'order', 'categoria', 'tag', 'busca');
        $cached_result = Fuerza_Cache::get_cached_api_response('/eventos', $cache_params);
        if ($cached_result !== false) {
            return $cached_result;
        }

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
                'taxonomy' => 'post_tag',
                'field' => 'slug',
                'terms' => $tag,
            ];
        }

        if ($busca) {
            $args['s'] = $busca;
        }

        // Executar query
        $query = new WP_Query($args);
        
        if ($query->have_posts()) {
            $eventos = [];
            while ($query->have_posts()) {
                $query->the_post();
                $eventos[] = Eventos_Formatter::format_single(get_post());
            }
            wp_reset_postdata();
            
            $response = [
                'eventos' => $eventos,
                'pagination' => [
                    'total' => $query->found_posts,
                    'pages' => $query->max_num_pages,
                    'current_page' => $page,
                    'per_page' => $per_page,
                ],
                'filters' => [
                    'categoria' => $categoria,
                    'tag' => $tag,
                    'busca' => $busca,
                    'orderby' => $orderby,
                    'order' => $order,
                ]
            ];
            
            // Cache da resposta
            Fuerza_Cache::cache_api_response('/eventos', $cache_params, $response);
            
            return $response;
        }
        
        wp_reset_postdata();
        return new WP_Error('no_events', 'Nenhum evento encontrado', ['status' => 404]);
    }
    
    /**
     * Obter evento específico
     */
    public static function get_evento($request) {
        $id = absint($request->get_param('id'));
        
        if (!$id) {
            return new WP_Error('invalid_id', 'ID do evento é obrigatório', ['status' => 400]);
        }
        
        $evento = get_post($id);
        
        if (!$evento || $evento->post_type !== 'evento' || $evento->post_status !== 'publish') {
            return new WP_Error('event_not_found', 'Evento não encontrado', ['status' => 404]);
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
                'description' => 'Número de eventos por página (máximo 100)',
            ],
            'page' => [
                'default' => 1,
                'sanitize_callback' => 'absint',
                'description' => 'Número da página',
            ],
            'orderby' => [
                'default' => 'date',
                'sanitize_callback' => 'sanitize_text_field',
                'description' => 'Ordenar por: date, title, menu_order, rand, modified',
            ],
            'order' => [
                'default' => 'DESC',
                'sanitize_callback' => 'sanitize_text_field',
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
                'description' => 'Buscar eventos por termo (2-100 caracteres)',
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
                'description' => 'ID do evento',
            ],
        ];
    }
}
