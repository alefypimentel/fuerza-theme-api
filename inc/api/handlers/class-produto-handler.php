<?php
/**
 * Handler for routes of Produtos
 * 
 * Auto-generated on 2025-08-18 20:26:43
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once get_template_directory() . '/inc/api/formatters/class-produto-formatter.php';

class Produto_Handler {
    
    /**
     * Get formatted
     */
    public static function get_Produtos($request) {
        $per_page = $request->get_param('per_page');
        $page = $request->get_param('page');
        $orderby = $request->get_param('orderby');
        $order = $request->get_param('order');
        $categoria = $request->get_param('categoria');
        $busca = $request->get_param('busca');
        $lang = $request->get_param('lang');

        // Montar argumentos da query
        $args = [
            'post_type' => 'produto',
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
                'taxonomy' => 'categoria_produto',
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
                $formatted_post = Produto_Formatter::format_single(get_post());
                
                // Aplicar filtros de tradução aos dados formatados
                $formatted_post = apply_filters('fuerza_api_format_post', $formatted_post, get_post());
                
                $items[] = $formatted_post;
            }
            wp_reset_postdata();
            
            $response_data = [
                'produtos' => $items,
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
        return new WP_Error('no_produtos', 'Nenhum produto encontrado', ['status' => 404]);
    }
    
    /**
     * Get specific
     */
    public static function get_produto($request) {
        $id = absint($request->get_param('id'));
        
        if (!$id) {
            return new WP_Error('invalid_id', 'ID do produto é obrigatório', ['status' => 400]);
        }
        
        $item = get_post($id);
        
        if (!$item || $item->post_type !== 'produto' || $item->post_status !== 'publish') {
            return new WP_Error('produto_not_found', 'Produto não encontrado', ['status' => 404]);
        }
        
        $formatted_data = Produto_Formatter::format_produto($id);
        
        // Aplicar filtros de tradução
        $formatted_data = apply_filters('fuerza_api_format_post', $formatted_data, $item);
        
        return $formatted_data;
    }
    
    /**
     * Validate parameters da requisição
     */
    public static function validate_Produtos_params() {
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
     * Validate parameters de Produto específico
     */
    public static function validate_produto_params() {
        return [
            'id' => [
                'required' => true,
                'sanitize_callback' => 'absint',
            ],
        ];
    }
}