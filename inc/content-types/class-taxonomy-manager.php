<?php
/**
 * Gerenciador dinâmico de Taxonomias
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

class Taxonomy_Manager {
    
    /**
     * Lista de taxonomias registradas
     */
    private static $registered_taxonomies = [];
    
    /**
     * Instância singleton
     */
    private static $instance = null;
    
    /**
     * Obtém instância singleton
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Construtor privado
     */
    private function __construct() {
        add_action('init', [$this, 'register_all_taxonomies']);
        $this->load_taxonomy_definitions();
    }
    
    /**
     * Carrega todas as definições de taxonomias
     */
    private function load_taxonomy_definitions() {
        $taxonomies_dir = get_template_directory() . '/inc/content-types/taxonomies/';
        
        if (!is_dir($taxonomies_dir)) {
            return;
        }
        
        $taxonomy_files = glob($taxonomies_dir . '*.php');
        
        foreach ($taxonomy_files as $file) {
            require_once $file;
        }
    }
    
    /**
     * Registra uma nova taxonomia
     */
    public static function register_taxonomy($taxonomy, $post_types, $config) {
        self::$registered_taxonomies[$taxonomy] = [
            'post_types' => (array) $post_types,
            'config' => $config
        ];
    }
    
    /**
     * Registra todas as taxonomias no WordPress
     */
    public function register_all_taxonomies() {
        foreach (self::$registered_taxonomies as $taxonomy => $data) {
            $this->register_single_taxonomy($taxonomy, $data['post_types'], $data['config']);
        }
    }
    
    /**
     * Registra uma taxonomia individual
     */
    private function register_single_taxonomy($taxonomy, $post_types, $config) {
        // Configurações padrão
        $defaults = [
            'labels' => $this->generate_labels($taxonomy, $config),
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_nav_menus' => true,
            'show_in_rest' => true,
            'rest_base' => $taxonomy,
            'show_tagcloud' => true,
            'show_in_quick_edit' => true,
            'show_admin_column' => true,
            'hierarchical' => false,
            'query_var' => true,
            'rewrite' => ['slug' => $taxonomy],
        ];
        
        // Mesclar configurações
        $args = wp_parse_args($config, $defaults);
        
        // Registrar a taxonomia
        register_taxonomy($taxonomy, $post_types, $args);
        
        // Aplicar configurações adicionais se especificadas
        $this->apply_additional_configs($taxonomy, $config);
    }
    
    /**
     * Gera labels automaticamente
     */
    private function generate_labels($taxonomy, $config) {
        $singular = $config['singular_name'] ?? ucfirst($taxonomy);
        $plural = $config['plural_name'] ?? $singular . 's';
        
        $labels = [
            'name' => $plural,
            'singular_name' => $singular,
            'menu_name' => $plural,
            'all_items' => 'Todos os ' . $plural,
            'edit_item' => 'Editar ' . $singular,
            'view_item' => 'Ver ' . $singular,
            'update_item' => 'Atualizar ' . $singular,
            'add_new_item' => 'Adicionar Novo ' . $singular,
            'new_item_name' => 'Nome do Novo ' . $singular,
            'parent_item' => $singular . ' Pai',
            'parent_item_colon' => $singular . ' Pai:',
            'search_items' => 'Buscar ' . $plural,
            'popular_items' => $plural . ' Populares',
            'separate_items_with_commas' => 'Separar ' . strtolower($plural) . ' com vírgulas',
            'add_or_remove_items' => 'Adicionar ou remover ' . strtolower($plural),
            'choose_from_most_used' => 'Escolher dos ' . strtolower($plural) . ' mais usados',
            'not_found' => 'Nenhum ' . strtolower($singular) . ' encontrado.',
            'no_terms' => 'Nenhum ' . strtolower($singular),
            'items_list_navigation' => 'Navegação da lista de ' . strtolower($plural),
            'items_list' => 'Lista de ' . strtolower($plural),
        ];
        
        // Permitir override de labels específicos
        if (isset($config['labels']) && is_array($config['labels'])) {
            $labels = array_merge($labels, $config['labels']);
        }
        
        return $labels;
    }
    
    /**
     * Aplicar configurações adicionais
     */
    private function apply_additional_configs($taxonomy, $config) {
        // Configurar campos personalizados para termos
        if (isset($config['term_meta_fields']) && is_array($config['term_meta_fields'])) {
            $this->setup_term_meta_fields($taxonomy, $config['term_meta_fields']);
        }
        
        // Configurar colunas administrativas personalizadas
        if (isset($config['admin_columns']) && is_array($config['admin_columns'])) {
            $this->setup_admin_columns($taxonomy, $config['admin_columns']);
        }
        
        // Configurar hooks personalizados
        if (isset($config['hooks']) && is_array($config['hooks'])) {
            $this->setup_custom_hooks($taxonomy, $config['hooks']);
        }
    }
    
    /**
     * Configurar campos meta para termos
     */
    private function setup_term_meta_fields($taxonomy, $meta_fields) {
        foreach ($meta_fields as $field) {
            // Adicionar campo ao formulário de criação
            add_action("{$taxonomy}_add_form_fields", function() use ($field) {
                $this->render_term_meta_field($field, 'add');
            });
            
            // Adicionar campo ao formulário de edição
            add_action("{$taxonomy}_edit_form_fields", function($term) use ($field) {
                $this->render_term_meta_field($field, 'edit', $term);
            });
            
            // Salvar campo
            add_action("created_{$taxonomy}", function($term_id) use ($field) {
                $this->save_term_meta_field($term_id, $field);
            });
            
            add_action("edited_{$taxonomy}", function($term_id) use ($field) {
                $this->save_term_meta_field($term_id, $field);
            });
        }
    }
    
    /**
     * Renderizar campo meta do termo
     */
    private function render_term_meta_field($field, $context, $term = null) {
        $value = $term ? get_term_meta($term->term_id, $field['key'], true) : '';
        $field_id = $field['key'];
        $field_name = $field['key'];
        $field_label = $field['label'] ?? ucfirst($field['key']);
        $field_type = $field['type'] ?? 'text';
        
        if ($context === 'add') {
            echo '<div class="form-field">';
            echo '<label for="' . $field_id . '">' . $field_label . '</label>';
        } else {
            echo '<tr class="form-field">';
            echo '<th scope="row"><label for="' . $field_id . '">' . $field_label . '</label></th>';
            echo '<td>';
        }
        
        switch ($field_type) {
            case 'textarea':
                echo '<textarea id="' . $field_id . '" name="' . $field_name . '" rows="5" cols="50">' . esc_textarea($value) . '</textarea>';
                break;
            case 'select':
                echo '<select id="' . $field_id . '" name="' . $field_name . '">';
                if (isset($field['options'])) {
                    foreach ($field['options'] as $option_value => $option_label) {
                        echo '<option value="' . esc_attr($option_value) . '"' . selected($value, $option_value, false) . '>' . esc_html($option_label) . '</option>';
                    }
                }
                echo '</select>';
                break;
            default:
                echo '<input type="' . $field_type . '" id="' . $field_id . '" name="' . $field_name . '" value="' . esc_attr($value) . '" />';
                break;
        }
        
        if (isset($field['description'])) {
            echo '<p class="description">' . $field['description'] . '</p>';
        }
        
        if ($context === 'add') {
            echo '</div>';
        } else {
            echo '</td></tr>';
        }
    }
    
    /**
     * Salvar campo meta do termo
     */
    private function save_term_meta_field($term_id, $field) {
        if (isset($_POST[$field['key']])) {
            $value = sanitize_text_field($_POST[$field['key']]);
            update_term_meta($term_id, $field['key'], $value);
        }
    }
    
    /**
     * Configurar colunas administrativas
     */
    private function setup_admin_columns($taxonomy, $columns) {
        // Adicionar colunas
        add_filter("manage_edit-{$taxonomy}_columns", function($existing_columns) use ($columns) {
            return array_merge($existing_columns, $columns);
        });
        
        // Popular colunas
        add_filter("manage_{$taxonomy}_custom_column", function($content, $column, $term_id) use ($columns) {
            if (isset($columns[$column]) && isset($columns[$column]['callback'])) {
                return call_user_func($columns[$column]['callback'], $content, $column, $term_id);
            }
            return $content;
        }, 10, 3);
    }
    
    /**
     * Configurar hooks personalizados
     */
    private function setup_custom_hooks($taxonomy, $hooks) {
        foreach ($hooks as $hook => $callback) {
            add_action($hook, $callback);
        }
    }
    
    /**
     * Obter todas as taxonomias registradas
     */
    public static function get_registered_taxonomies() {
        return self::$registered_taxonomies;
    }
    
    /**
     * Verificar se uma taxonomia está registrada
     */
    public static function is_taxonomy_registered($taxonomy) {
        return isset(self::$registered_taxonomies[$taxonomy]);
    }
    
    /**
     * Obter configuração de uma taxonomia específica
     */
    public static function get_taxonomy_config($taxonomy) {
        return self::$registered_taxonomies[$taxonomy] ?? null;
    }
}

// Inicializar o gerenciador
Taxonomy_Manager::get_instance();
