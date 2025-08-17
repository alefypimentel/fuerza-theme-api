<?php
function fuerza_theme_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    load_theme_textdomain('meu-tema-api', get_template_directory() . '/languages');
}
add_action('after_setup_theme', 'fuerza_theme_setup');
