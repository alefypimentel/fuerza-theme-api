<?php
/**
 * Formatador base para dados da API
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

class Base_Formatter {
    
    /**
     * Formatar imagem destacada
     */
    public static function format_featured_image($post_id) {
        if (!has_post_thumbnail($post_id)) {
            return null;
        }

        $thumbnail_id = get_post_thumbnail_id($post_id);
        $imagem = wp_get_attachment_image_src($thumbnail_id, 'full');
        $imagem_media = wp_get_attachment_image_src($thumbnail_id, 'medium');
        $imagem_thumb = wp_get_attachment_image_src($thumbnail_id, 'thumbnail');

        return [
            'id' => $thumbnail_id,
            'titulo' => get_the_title($thumbnail_id),
            'alt' => get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true),
            'url_completa' => $imagem ? $imagem[0] : '',
            'url_media' => $imagem_media ? $imagem_media[0] : '',
            'url_miniatura' => $imagem_thumb ? $imagem_thumb[0] : '',
            'largura' => $imagem ? $imagem[1] : 0,
            'altura' => $imagem ? $imagem[2] : 0,
        ];
    }
    
    /**
     * Formatar dados do autor
     */
    public static function format_author($post_id = null, $include_sensitive_data = false) {
        if ($post_id) {
            $author_id = get_post_field('post_author', $post_id);
        } else {
            $author_id = get_the_author_meta('ID');
        }
        
        $author_data = [
            'id' => (int) $author_id,
            'nome' => get_the_author_meta('display_name', $author_id),
            'username' => get_the_author_meta('user_login', $author_id),
            'avatar' => get_avatar_url($author_id),
        ];
        
        // Incluir dados sensíveis apenas se autorizado
        if ($include_sensitive_data && current_user_can('edit_users')) {
            $author_data['email'] = get_the_author_meta('email', $author_id);
        }
        
        return $author_data;
    }
    
    /**
     * Formatar taxonomias
     */
    public static function format_terms($post_id, $taxonomies) {
        $formatted_terms = [];
        
        foreach ($taxonomies as $taxonomy) {
            $terms = get_the_terms($post_id, $taxonomy);
            
            if (!$terms || is_wp_error($terms)) {
                continue;
            }
            
            foreach ($terms as $term) {
                $formatted_terms[] = [
                    'id' => $term->term_id,
                    'nome' => $term->name,
                    'slug' => $term->slug,
                    'descricao' => $term->description,
                    'taxonomia' => $term->taxonomy,
                    'parent' => $term->parent,
                    'count' => $term->count,
                ];
            }
        }
        
        return $formatted_terms;
    }
    
    /**
     * Formatar campos ACF
     */
    public static function format_acf_fields($post_id) {
        // Verificar se o ACF está ativo
        if (!function_exists('get_fields')) {
            return [];
        }

        // Obter todos os campos ACF do post
        $campos = get_fields($post_id);
        
        if (!$campos) {
            return [];
        }

        // Processar campos para formatar adequadamente
        return self::process_acf_fields($campos);
    }
    
    /**
     * Processar campos ACF
     */
    private static function process_acf_fields($campos) {
        $campos_processados = [];

        foreach ($campos as $nome_campo => $valor) {
            $campos_processados[$nome_campo] = self::format_acf_value($valor);
        }

        return $campos_processados;
    }
    
    /**
     * Formatar valor ACF
     */
    private static function format_acf_value($valor) {
        // Se for um array (como imagem, galeria, etc.)
        if (is_array($valor)) {
            // Verificar se é uma imagem do ACF
            if (isset($valor['url']) && isset($valor['alt']) && isset($valor['width'])) {
                return [
                    'id' => $valor['ID'] ?? null,
                    'titulo' => $valor['title'] ?? '',
                    'alt' => $valor['alt'] ?? '',
                    'url_completa' => $valor['url'] ?? '',
                    'url_media' => $valor['sizes']['medium'] ?? $valor['url'] ?? '',
                    'url_miniatura' => $valor['sizes']['thumbnail'] ?? $valor['url'] ?? '',
                    'largura' => $valor['width'] ?? 0,
                    'altura' => $valor['height'] ?? 0,
                    'mime_type' => $valor['mime_type'] ?? '',
                    'tamanho_arquivo' => $valor['filesize'] ?? 0,
                ];
            }
            
            // Verificar se é um link/URL do ACF
            if (isset($valor['url']) && isset($valor['title']) && isset($valor['target'])) {
                return [
                    'url' => $valor['url'],
                    'titulo' => $valor['title'],
                    'target' => $valor['target'],
                ];
            }

            // Verificar se é um post object do ACF
            if (isset($valor['ID']) && isset($valor['post_title'])) {
                return [
                    'id' => $valor['ID'],
                    'titulo' => $valor['post_title'],
                    'slug' => $valor['post_name'] ?? '',
                    'tipo' => $valor['post_type'] ?? '',
                    'link' => get_permalink($valor['ID']),
                ];
            }

            // Verificar se é um termo/taxonomy do ACF
            if (isset($valor['term_id']) && isset($valor['name'])) {
                return [
                    'id' => $valor['term_id'],
                    'nome' => $valor['name'],
                    'slug' => $valor['slug'] ?? '',
                    'taxonomia' => $valor['taxonomy'] ?? '',
                    'descricao' => $valor['description'] ?? '',
                ];
            }

            // Verificar se é um usuário do ACF
            if (isset($valor['ID']) && isset($valor['display_name'])) {
                return [
                    'id' => $valor['ID'],
                    'nome' => $valor['display_name'],
                    'username' => $valor['user_login'] ?? '',
                    'email' => $valor['user_email'] ?? '',
                ];
            }

            // Se for array comum, processar recursivamente
            $array_processado = [];
            foreach ($valor as $chave => $item) {
                $array_processado[$chave] = self::format_acf_value($item);
            }
            return $array_processado;
        }

        // Se for objeto DateTime (date picker do ACF)
        if ($valor instanceof DateTime) {
            return $valor->format('Y-m-d H:i:s');
        }

        // Para valores simples (string, number, boolean), retornar como está
        return $valor;
    }
    
    /**
     * Formatar dados básicos de um post
     */
    public static function format_basic_post_data($post_id = null) {
        if ($post_id) {
            $post = get_post($post_id);
            setup_postdata($post);
        } else {
            $post_id = get_the_ID();
        }
        
        // Sistema simplificado sem cache
        
        $data = [
            'id' => $post_id,
            'titulo' => get_the_title($post_id),
            'conteudo' => get_the_content('', false, $post_id),
            'resumo' => get_the_excerpt($post_id),
            'data_publicacao' => get_the_date('Y-m-d H:i:s', $post_id),
            'data_modificacao' => get_the_modified_date('Y-m-d H:i:s', $post_id),
            'slug' => get_post_field('post_name', $post_id),
            'link' => get_permalink($post_id),
            'status' => get_post_status($post_id),
            'tipo' => get_post_type($post_id),
        ];
        
        // Adicionar informações de tradução se disponível
        $data = self::add_translation_data($data, $post_id);
        
        // Dados formatados (sem cache)
        
        if ($post_id !== get_the_ID()) {
            wp_reset_postdata();
        }
        
        return $data;
    }
    
    /**
     * Adicionar dados de tradução ao post
     */
    protected static function add_translation_data($data, $post_id) {
        // Verificar se há plugin de tradução ativo
        if (!class_exists('Fuerza_Translation_Support')) {
            return $data;
        }
        
        $translation_support = Fuerza_Translation_Support::get_instance();
        
        if (!$translation_support->has_translation_plugin()) {
            return $data;
        }
        
        // Adicionar informações de idioma
        $data['language'] = $translation_support->get_post_language($post_id);
        $data['translations'] = $translation_support->get_post_translations($post_id);
        $data['is_default_language'] = $data['language'] === $translation_support->get_translation_info()['default_language'];
        
        // Adicionar conteúdo completo das traduções
        if (!empty($data['translations'])) {
            $translated_content = [];
            
            foreach ($data['translations'] as $lang => $translation) {
                $translation_id = $translation['id'];
                $translated_post = get_post($translation_id);
                
                if ($translated_post && $translated_post->post_status === 'publish') {
                    $translated_content[$lang] = [
                        'id' => $translation_id,
                        'language' => $lang,
                        'titulo' => get_the_title($translation_id),
                        'conteudo' => apply_filters('the_content', $translated_post->post_content),
                        'resumo' => get_the_excerpt($translated_post),
                        'slug' => $translated_post->post_name,
                        'url' => get_permalink($translation_id),
                        'api_url' => home_url('/wp-json/' . API_Manager::get_namespace() . '/' . get_post_type($post_id) . '/' . $translation_id),
                        'data_publicacao' => get_the_date('Y-m-d H:i:s', $translation_id),
                        'data_modificacao' => get_the_modified_date('Y-m-d H:i:s', $translation_id)
                    ];
                }
            }
            
            $data['translated_content'] = $translated_content;
            
            // Manter translated_versions para compatibilidade (deprecado)
            $translated_urls = [];
            foreach ($data['translations'] as $lang => $translation) {
                $translated_urls[$lang] = [
                    'id' => $translation['id'],
                    'url' => $translation['url'],
                    'language' => $lang,
                    'api_url' => home_url('/wp-json/' . API_Manager::get_namespace() . '/' . get_post_type($post_id) . '/' . $translation['id'])
                ];
            }
            $data['translated_versions'] = $translated_urls;
        }
        
        return $data;
    }
}
