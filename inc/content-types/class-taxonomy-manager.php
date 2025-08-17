<?php
/**
 * Dynamic Taxonomies Manager
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

class Taxonomy_Manager {
    
    /**
     * List of registered taxonomies
     */
    private static $registered_taxonomies = [];
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Private constructor
     */
    private function __construct() {
        add_action('init', [$this, 'register_all_taxonomies']);
        $this->load_taxonomy_definitions();
    }
    
    /**
     * Load all taxonomy definitions
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
     * Register a new taxonomy
     */
    public static function register_taxonomy($taxonomy, $post_types, $config) {
        self::$registered_taxonomies[$taxonomy] = [
            'post_types' => (array) $post_types,
            'config' => $config
        ];
    }
    
    /**
     * Register all taxonomies in WordPress
     */
    public function register_all_taxonomies() {
        foreach (self::$registered_taxonomies as $taxonomy => $data) {
            $this->register_single_taxonomy($taxonomy, $data['post_types'], $data['config']);
        }
    }
    
    /**
     * Register individual taxonomy
     */
    private function register_single_taxonomy($taxonomy, $post_types, $config) {
        // Default settings
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
        
        // Merge settings
        $args = wp_parse_args($config, $defaults);
        
        // Register the taxonomy
        register_taxonomy($taxonomy, $post_types, $args);
        
        // Apply additional configurations if specified
        $this->apply_additional_configs($taxonomy, $config);
    }
    
    /**
     * Generate labels automatically
     */
    private function generate_labels($taxonomy, $config) {
        $singular = $config['singular_name'] ?? ucfirst($taxonomy);
        $plural = $config['plural_name'] ?? $singular . 's';
        
        $labels = [
            'name' => $plural,
            'singular_name' => $singular,
            'menu_name' => $plural,
            'all_items' => sprintf(__('All %s', 'fuerza-theme'), $plural),
            'edit_item' => sprintf(__('Edit %s', 'fuerza-theme'), $singular),
            'view_item' => sprintf(__('View %s', 'fuerza-theme'), $singular),
            'update_item' => sprintf(__('Update %s', 'fuerza-theme'), $singular),
            'add_new_item' => sprintf(__('Add New %s', 'fuerza-theme'), $singular),
            'new_item_name' => sprintf(__('New %s Name', 'fuerza-theme'), $singular),
            'parent_item' => sprintf(__('Parent %s', 'fuerza-theme'), $singular),
            'parent_item_colon' => sprintf(__('Parent %s:', 'fuerza-theme'), $singular),
            'search_items' => sprintf(__('Search %s', 'fuerza-theme'), $plural),
            'popular_items' => sprintf(__('Popular %s', 'fuerza-theme'), $plural),
            'separate_items_with_commas' => sprintf(__('Separate %s with commas', 'fuerza-theme'), strtolower($plural)),
            'add_or_remove_items' => sprintf(__('Add or remove %s', 'fuerza-theme'), strtolower($plural)),
            'choose_from_most_used' => sprintf(__('Choose from most used %s', 'fuerza-theme'), strtolower($plural)),
            'not_found' => sprintf(__('No %s found.', 'fuerza-theme'), strtolower($plural)),
            'no_terms' => sprintf(__('No %s', 'fuerza-theme'), strtolower($plural)),
            'items_list_navigation' => sprintf(__('%s list navigation', 'fuerza-theme'), $plural),
            'items_list' => sprintf(__('%s list', 'fuerza-theme'), $plural),
        ];
        
        // Allow override of specific labels
        if (isset($config['labels']) && is_array($config['labels'])) {
            $labels = array_merge($labels, $config['labels']);
        }
        
        return $labels;
    }
    
    /**
     * Apply additional configurations
     */
    private function apply_additional_configs($taxonomy, $config) {
        // Configure custom fields for terms
        if (isset($config['term_meta_fields']) && is_array($config['term_meta_fields'])) {
            $this->setup_term_meta_fields($taxonomy, $config['term_meta_fields']);
        }
        
        // Configure custom admin columns
        if (isset($config['admin_columns']) && is_array($config['admin_columns'])) {
            $this->setup_admin_columns($taxonomy, $config['admin_columns']);
        }
        
        // Configure custom hooks
        if (isset($config['hooks']) && is_array($config['hooks'])) {
            $this->setup_custom_hooks($taxonomy, $config['hooks']);
        }
    }
    
    /**
     * Configure meta fields for terms
     */
    private function setup_term_meta_fields($taxonomy, $meta_fields) {
        foreach ($meta_fields as $field) {
            // Add field to creation form
            add_action("{$taxonomy}_add_form_fields", function() use ($field) {
                $this->render_term_meta_field($field, 'add');
            });
            
            // Add field to edit form
            add_action("{$taxonomy}_edit_form_fields", function($term) use ($field) {
                $this->render_term_meta_field($field, 'edit', $term);
            });
            
            // Save field
            add_action("created_{$taxonomy}", function($term_id) use ($field) {
                $this->save_term_meta_field($term_id, $field);
            });
            
            add_action("edited_{$taxonomy}", function($term_id) use ($field) {
                $this->save_term_meta_field($term_id, $field);
            });
        }
    }
    
    /**
     * Render term meta field
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
     * Save term meta field
     */
    private function save_term_meta_field($term_id, $field) {
        if (isset($_POST[$field['key']])) {
            $value = sanitize_text_field($_POST[$field['key']]);
            update_term_meta($term_id, $field['key'], $value);
        }
    }
    
    /**
     * Configure admin columns
     */
    private function setup_admin_columns($taxonomy, $columns) {
        // Add columns
        add_filter("manage_edit-{$taxonomy}_columns", function($existing_columns) use ($columns) {
            return array_merge($existing_columns, $columns);
        });
        
        // Populate columns
        add_filter("manage_{$taxonomy}_custom_column", function($content, $column, $term_id) use ($columns) {
            if (isset($columns[$column]) && isset($columns[$column]['callback'])) {
                return call_user_func($columns[$column]['callback'], $content, $column, $term_id);
            }
            return $content;
        }, 10, 3);
    }
    
    /**
     * Configure custom hooks
     */
    private function setup_custom_hooks($taxonomy, $hooks) {
        foreach ($hooks as $hook => $callback) {
            add_action($hook, $callback);
        }
    }
    
    /**
     * Get all registered taxonomies
     */
    public static function get_registered_taxonomies() {
        return self::$registered_taxonomies;
    }
    
    /**
     * Check if a taxonomy is registered
     */
    public static function is_taxonomy_registered($taxonomy) {
        return isset(self::$registered_taxonomies[$taxonomy]);
    }
    
    /**
     * Get specific taxonomy configuration
     */
    public static function get_taxonomy_config($taxonomy) {
        return self::$registered_taxonomies[$taxonomy] ?? null;
    }
}

// Initialize the manager
Taxonomy_Manager::get_instance();
