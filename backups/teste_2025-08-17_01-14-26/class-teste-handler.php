<?php
/**
 * Manipulador para rotas de Testes
 * 
 * Gerado automaticamente em 2025-08-17 01:13:54
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once get_template_directory() . '/inc/api/formatters/class-teste-formatter.php';

class Teste_Handler {
    
    /**
     * Obter Testes formatados
     */
    public static function get_Testes($request) {
        $per_page = $request->get_param('per_page');
        $page = $request->get_param('page');
        $orderby = $request->get_param('orderby');
        $order = $request->get_param('order');
        $categoria = $request->get_param('categoria');
        $busca = $request->get_param('busca');

        // Montar argumentos da query
        $args = [
            'post_type' => 'teste',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'orderby' => $orderby,
            'order' => $order,
        ];

        // Adicionar filtros se especificados
        if ($categoria) {
            $args['tax_query'][] = [
                'taxonomy' => 'categoria_teste',
                'field' => 'slug',
                'terms' => $categoria,
            ];
        }

        if ($busca) {
            $args['s'] = $busca;
        }

        // Executar query
        $query = new WP_Query($args);
        $Testes = [];

        if ($query->have_posts()) {
            $Testes = Teste_Formatter::format_Testes($query->posts);
        }

        // Formatar resposta com paginação
        return [
            'Testes' => $Testes,
            'paginacao' => Teste_Formatter::format_pagination($query, $page, $per_page),
        ];
    }
    
    /**
     * Obter um Teste específico
     */
    public static function get_teste($request) {
        $id = $request->get_param('id');
        
        $post = get_post($id);
        
        if (!$post || $post->post_type !== 'teste' || $post->post_status !== 'publish') {
            return new WP_Error(
                'teste_not_found',
                'Teste não encontrado.',
                ['status' => 404]
            );
        }
        
        return Teste_Formatter::format_teste($id);
    }
    
    /**
     * Validar parâmetros da requisição
     */
    public static function validate_Testes_params() {
        return [
            'per_page' => [
                'default' => 10,
                'sanitize_callback' => 'absint',
                'validate_callback' => function($param) {
                    return is_numeric($param) && $param > 0 && $param <= 100;
                },
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
     * Validar parâmetros de Teste específico
     */
    public static function validate_teste_params() {
        return [
            'id' => [
                'required' => true,
                'sanitize_callback' => 'absint',
                'validate_callback' => function($param) {
                    return is_numeric($param) && $param > 0;
                },
            ],
        ];
    }
}
