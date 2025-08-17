<?php
/**
 * Script para resetar rate limiting
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

echo "🔓 Resetando Rate Limiting da API Fuerza Theme...\n\n";

// Carregar classe de rate limiter
require_once get_template_directory() . '/inc/class-rate-limiter.php';

try {
    // Limpar todos os bloqueios
    if (class_exists('Fuerza_Rate_Limiter')) {
        $result = Fuerza_Rate_Limiter::clear_all_blocks();
        
        if ($result) {
            echo "✅ Todos os bloqueios de IP foram removidos!\n";
        } else {
            echo "⚠️  Nenhum bloqueio encontrado.\n";
        }
        
        // Resetar configurações para padrões
        update_option('fuerza_rate_limiting_enabled', false);
        update_option('fuerza_rate_limit_per_minute', 60);
        update_option('fuerza_rate_limit_per_hour', 1000);
        update_option('fuerza_rate_limit_burst', 10);
        update_option('fuerza_rate_limit_block_duration', 300);
        
        echo "✅ Configurações resetadas para os padrões!\n";
        echo "   - Rate limiting: DESABILITADO\n";
        echo "   - Limite por minuto: 60 requisições\n";
        echo "   - Limite por hora: 1000 requisições\n";
        echo "   - Burst limit: 10 requisições\n";
        echo "   - Duração do bloqueio: 5 minutos\n";
        
    } else {
        echo "❌ Classe Fuerza_Rate_Limiter não encontrada.\n";
        exit(1);
    }
    
    // Limpar tabela de rate limits se existir
    global $wpdb;
    $table_name = $wpdb->prefix . 'fuerza_rate_limits';
    
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
    if ($table_exists) {
        $deleted = $wpdb->query("DELETE FROM {$table_name}");
        echo "✅ {$deleted} registros removidos da tabela de rate limits!\n";
    }
    
    echo "\n🎉 Rate limiting resetado com sucesso!\n";
    echo "💡 Agora você pode acessar todas as rotas sem restrições.\n";
    
} catch (Exception $e) {
    echo "❌ Erro ao resetar rate limiting: " . $e->getMessage() . "\n";
    exit(1);
}
?>
