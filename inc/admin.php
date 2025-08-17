<?php
function fuerza_theme_custom_admin_footer() {
    echo 'Desenvolvido por <a href="https://seudominio.com">Seu Nome</a>';
}
add_filter('admin_footer_text', 'fuerza_theme_custom_admin_footer');
