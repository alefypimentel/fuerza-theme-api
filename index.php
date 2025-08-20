<?php
/**
 * Template principal do Fuerza Theme API
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

// get_header(); 
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .api-container {
            text-align: center;
            color: white;
            padding: 2rem;
            max-width: 600px;
        }
        .api-title {
            font-size: 3rem;
            margin-bottom: 1rem;
            background: linear-gradient(45deg, #fff, #f0f0f0);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .api-description {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="api-container">
        <h1 class="api-title"><?php bloginfo('name'); ?></h1>
        <p class="api-description">
            <?php echo get_bloginfo('description') ?: 'API REST Profissional com WordPress'; ?>
        </p>
        <?php wp_footer(); ?>
    </div>
</body>
</html>
