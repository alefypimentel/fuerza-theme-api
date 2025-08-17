<?php
/**
 * Template principal do Fuerza Theme API
 * 
 * @package FuerzaThemeAPI
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header(); ?>

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
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
            border: 1px solid rgba(255, 255, 255, 0.18);
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
        .api-links {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        .api-link {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 0.8rem 1.5rem;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        .api-link:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }
        .api-status {
            margin-top: 2rem;
            padding: 1rem;
            background: rgba(46, 204, 113, 0.2);
            border-radius: 10px;
            border: 1px solid rgba(46, 204, 113, 0.3);
        }
        .status-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            background: #2ecc71;
            border-radius: 50%;
            margin-right: 8px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
    </style>
</head>
<body>
    <div class="api-container">
        <h1 class="api-title"><?php bloginfo('name'); ?></h1>
        <p class="api-description">
            <?php echo get_bloginfo('description') ?: 'API REST Profissional com WordPress'; ?>
        </p>
        
        <div class="api-links">
            <a href="<?php echo home_url('/wp-json/' . API_Manager::get_namespace()); ?>" class="api-link" target="_blank">
                📡 Explorar API
            </a>
            <a href="<?php echo home_url('/wp-json/' . API_Manager::get_namespace() . '/ping'); ?>" class="api-link" target="_blank">
                🏓 Ping Test
            </a>
            <a href="<?php echo admin_url(); ?>" class="api-link">
                ⚙️ Admin
            </a>
        </div>
        
        <div class="api-status">
            <span class="status-indicator"></span>
            <strong>API Status:</strong> Online e Funcionando
        </div>
    </div>
    
    <?php wp_footer(); ?>
</body>
</html>
