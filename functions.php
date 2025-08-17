<?php
/**
 * Main functions for Fuerza Theme API
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

// Load theme text domain for translations
add_action('after_setup_theme', function() {
    load_theme_textdomain('fuerza-theme', get_template_directory() . '/languages');
});

// Load theme basic configurations
require_once get_template_directory() . '/inc/setup.php';

// Load custom post types and taxonomies
require_once get_template_directory() . '/inc/custom-post-types.php';
require_once get_template_directory() . '/inc/taxonomies.php';

// Load API system
require_once get_template_directory() . '/inc/rest-api.php';

// Load administrative functionalities
require_once get_template_directory() . '/inc/admin.php';
require_once get_template_directory() . '/inc/helpers.php';

// Load logging, monitoring, cache, rate limiting, documentation and performance systems
require_once get_template_directory() . '/inc/class-logger.php';
require_once get_template_directory() . '/inc/class-monitor.php';
require_once get_template_directory() . '/inc/class-cache.php';
require_once get_template_directory() . '/inc/class-rate-limiter.php';
require_once get_template_directory() . '/inc/class-api-docs.php';
require_once get_template_directory() . '/inc/class-performance.php';
require_once get_template_directory() . '/inc/class-api-tester.php';

// Load administrative dashboard
require_once get_template_directory() . '/inc/admin-dashboard.php';
