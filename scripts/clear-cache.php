<?php
/**
 * Script para limpar cache da API
 */

// Verificar se está sendo executado via CLI
if (php_sapi_name() !== 'cli') {
    // Se via web, carregar WordPress
    require_once dirname(__DIR__, 3) . '/wp-load.php';
} else {
    // Se via CLI, carregar WordPress
    require_once dirname(__DIR__, 3) . '/wp-config.php';
    require_once dirname(__DIR__, 3) . '/wp-load.php';
}

echo "🧹 Limpando cache da API Fuerza Theme...\n\n";

// Carregar classe de cache
require_once get_template_directory() . '/inc/class-cache.php';

try {
    // Limpar cache da API
    if (class_exists('Fuerza_Cache')) {
        $result = Fuerza_Cache::clear_all_cache();
        
        if ($result) {
            echo "✅ Cache da API limpo com sucesso!\n";
        } else {
            echo "⚠️  Nenhum cache encontrado para limpar.\n";
        }
    } else {
        echo "❌ Classe Fuerza_Cache não encontrada.\n";
        exit(1);
    }
    
    // Limpar cache do WordPress
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
        echo "✅ Cache do WordPress limpo!\n";
    }
    
    // Limpar transients relacionados
    $transients = [
        'fuerza_api_routes',
        'fuerza_api_stats',
        'fuerza_performance_data',
        'fuerza_rate_limits'
    ];
    
    $cleared = 0;
    foreach ($transients as $transient) {
        if (delete_transient($transient)) {
            $cleared++;
        }
    }
    
    if ($cleared > 0) {
        echo "✅ {$cleared} transients removidos!\n";
    }
    
    echo "\n🎉 Limpeza de cache concluída com sucesso!\n";
    
} catch (Exception $e) {
    echo "❌ Erro ao limpar cache: " . $e->getMessage() . "\n";
    exit(1);
}
?>
