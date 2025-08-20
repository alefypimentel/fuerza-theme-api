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
        $lang = $request->get_param('lang');

        // Montar argumentos da query
        $args = [
            'post_type' => 'evento',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'orderby' => $orderby,
            'order' => $order,
        ];
        
        // Aplicar filtros de tradução
        $args = apply_filters('fuerza_api_get_posts_args', $args, $request);

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
                $formatted_post = Evento_Formatter::format_single(get_post());
                
                // Aplicar filtros de tradução aos dados formatados
                $formatted_post = apply_filters('fuerza_api_format_post', $formatted_post, get_post());
                
                $items[] = $formatted_post;
            }
            wp_reset_postdata();
            
            $response_data = [
                'eventos' => $items,
                'pagination' => [
                    'total' => $query->found_posts,
                    'pages' => $query->max_num_pages,
                    'current_page' => $page,
                    'per_page' => $per_page,
                ],
            ];
            
            // Adicionar informações de idioma se plugin de tradução estiver ativo
            if (class_exists('Fuerza_Translation_Support')) {
                $translation_support = Fuerza_Translation_Support::get_instance();
                if ($translation_support->has_translation_plugin()) {
                    $response_data['language_info'] = [
                        'current_language' => $translation_support->get_current_language(),
                        'available_languages' => array_keys($translation_support->get_translation_info()['languages']),
                        'requested_language' => $lang
                    ];
                }
            }
            
            return $response_data;
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
        
        $formatted_data = Evento_Formatter::format_evento($id);
        
        // Aplicar filtros de tradução
        $formatted_data = apply_filters('fuerza_api_format_post', $formatted_data, $item);
        
        return $formatted_data;
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
            'lang' => [
                'sanitize_callback' => 'sanitize_text_field',
                'description' => 'Código do idioma (ex: pt, en, es)',
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