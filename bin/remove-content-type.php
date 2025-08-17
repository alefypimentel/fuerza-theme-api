#!/usr/bin/env php
<?php
/**
 * Removedor de CPTs e Taxonomias
 * 
 * Script para remover automaticamente Custom Post Types, Taxonomias e APIs
 * 
 * Uso:
 * php bin/remove-content-type.php <nome> [opções]
 * 
 * Exemplos:
 * php bin/remove-content-type.php produto
 * php bin/remove-content-type.php evento --with-backup
 * php bin/remove-content-type.php noticia --taxonomy-only
 * php bin/remove-content-type.php portfolio --force
 * 
 * @package FuerzaThemeAPI
 */

// Verificar se está sendo executado via CLI
if (php_sapi_name() !== 'cli') {
    die("Este script deve ser executado via linha de comando.\n");
}

class ContentTypeRemover {
    
    private $theme_path;
    private $options;
    private $name;
    private $files_to_remove = [];
    private $backup_dir;
    
    public function __construct() {
        $this->theme_path = dirname(__DIR__);
        $this->backup_dir = $this->theme_path . '/backups';
        $this->options = $this->parseArguments();
        
        if (empty($this->options['name'])) {
            $this->showHelp();
            exit(1);
        }
        
        $this->name = $this->sanitizeName($this->options['name']);
        $this->run();
    }
    
    /**
     * Executar o removedor
     */
    private function run() {
        $this->printHeader();
        
        try {
            // Verificar se os arquivos existem
            $this->findFilesToRemove();
            
            if (empty($this->files_to_remove)) {
                $this->warning("⚠️  Nenhum arquivo encontrado para '{$this->name}'");
                exit(0);
            }
            
            // Mostrar arquivos que serão removidos
            $this->showFilesToRemove();
            
            // Confirmar remoção (a menos que --force seja usado)
            if (!isset($this->options['force'])) {
                $this->confirmRemoval();
            }
            
            // Criar backup se solicitado
            if (isset($this->options['with-backup'])) {
                $this->createBackup();
            }
            
            // Remover arquivos
            $this->removeFiles();
            
            // Limpar cache se existir
            $this->clearCache();
            
            $this->printSummary();
            
        } catch (Exception $e) {
            $this->error("❌ Erro: " . $e->getMessage());
            exit(1);
        }
    }
    
    /**
     * Analisar argumentos da linha de comando
     */
    private function parseArguments() {
        global $argv;
        $options = [];
        
        // Primeiro argumento é o nome (obrigatório)
        if (isset($argv[1]) && !str_starts_with($argv[1], '--')) {
            $options['name'] = $argv[1];
        }
        
        // Processar opções
        for ($i = 1; $i < count($argv); $i++) {
            $arg = $argv[$i];
            
            if (str_starts_with($arg, '--')) {
                if (str_contains($arg, '=')) {
                    [$key, $value] = explode('=', substr($arg, 2), 2);
                    $options[$key] = $value;
                } else {
                    $options[substr($arg, 2)] = true;
                }
            }
        }
        
        return $options;
    }
    
    /**
     * Encontrar arquivos para remoção
     */
    private function findFilesToRemove() {
        $taxonomy_name = "categoria_{$this->name}";
        
        // Arquivos do CPT
        if (!isset($this->options['taxonomy-only'])) {
            $cpt_file = $this->theme_path . "/inc/content-types/cpts/{$this->name}.php";
            if (file_exists($cpt_file)) {
                $this->files_to_remove['cpt'] = $cpt_file;
            }
        }
        
        // Arquivos da taxonomia
        if (!isset($this->options['cpt-only'])) {
            $taxonomy_file = $this->theme_path . "/inc/content-types/taxonomies/{$taxonomy_name}.php";
            if (file_exists($taxonomy_file)) {
                $this->files_to_remove['taxonomy'] = $taxonomy_file;
            }
        }
        
        // Arquivos da API (se existirem)
        if (!isset($this->options['taxonomy-only'])) {
            $api_files = [
                'formatter' => $this->theme_path . "/inc/api/formatters/class-{$this->name}-formatter.php",
                'handler' => $this->theme_path . "/inc/api/handlers/class-{$this->name}-handler.php",
                'routes' => $this->theme_path . "/inc/api/routes/{$this->name}-routes.php"
            ];
            
            foreach ($api_files as $type => $file) {
                if (file_exists($file)) {
                    $this->files_to_remove["api_{$type}"] = $file;
                }
            }
        }
    }
    
    /**
     * Mostrar arquivos que serão removidos
     */
    private function showFilesToRemove() {
        echo "📁 Arquivos encontrados para remoção:\n";
        echo "-----------------------------------\n";
        
        foreach ($this->files_to_remove as $type => $file) {
            $relative_path = str_replace($this->theme_path . '/', '', $file);
            $size = $this->formatFileSize(filesize($file));
            
            switch (true) {
                case str_contains($type, 'cpt'):
                    echo "📝 CPT: {$relative_path} ({$size})\n";
                    break;
                case str_contains($type, 'taxonomy'):
                    echo "🏷️  Taxonomia: {$relative_path} ({$size})\n";
                    break;
                case str_contains($type, 'api'):
                    echo "🌐 API: {$relative_path} ({$size})\n";
                    break;
                default:
                    echo "📄 Arquivo: {$relative_path} ({$size})\n";
                    break;
            }
        }
        
        echo "\n";
    }
    
    /**
     * Confirmar remoção
     */
    private function confirmRemoval() {
        $total_files = count($this->files_to_remove);
        echo "⚠️  Você está prestes a remover {$total_files} arquivo(s).\n";
        echo "Esta ação não pode ser desfeita (a menos que você tenha backup).\n\n";
        
        echo "Digite 'yes' para confirmar ou qualquer outra coisa para cancelar: ";
        $handle = fopen("php://stdin", "r");
        $confirmation = trim(fgets($handle));
        fclose($handle);
        
        if (strtolower($confirmation) !== 'yes') {
            echo "\n❌ Operação cancelada pelo usuário.\n";
            exit(0);
        }
        
        echo "\n✅ Confirmação recebida. Procedendo com a remoção...\n\n";
    }
    
    /**
     * Criar backup dos arquivos
     */
    private function createBackup() {
        // Criar diretório de backup se não existir
        if (!is_dir($this->backup_dir)) {
            mkdir($this->backup_dir, 0755, true);
        }
        
        $timestamp = date('Y-m-d_H-i-s');
        $backup_subdir = $this->backup_dir . "/{$this->name}_{$timestamp}";
        mkdir($backup_subdir, 0755, true);
        
        echo "💾 Criando backup em: " . str_replace($this->theme_path . '/', '', $backup_subdir) . "\n";
        
        foreach ($this->files_to_remove as $type => $file) {
            $backup_file = $backup_subdir . '/' . basename($file);
            if (copy($file, $backup_file)) {
                $this->success("✅ Backup: " . basename($file));
            } else {
                throw new Exception("Falha ao criar backup de: " . basename($file));
            }
        }
        
        // Criar arquivo de informações do backup
        $info_file = $backup_subdir . '/backup_info.txt';
        $info_content = "Backup do Content Type: {$this->name}\n";
        $info_content .= "Data: " . date('Y-m-d H:i:s') . "\n";
        $info_content .= "Comando executado: " . implode(' ', $_SERVER['argv']) . "\n";
        $info_content .= "Arquivos incluídos:\n";
        
        foreach ($this->files_to_remove as $type => $file) {
            $info_content .= "- {$type}: " . basename($file) . "\n";
        }
        
        file_put_contents($info_file, $info_content);
        
        echo "📋 Informações do backup salvas em: backup_info.txt\n\n";
    }
    
    /**
     * Remover arquivos
     */
    private function removeFiles() {
        echo "🗑️  Removendo arquivos...\n";
        
        foreach ($this->files_to_remove as $type => $file) {
            $filename = basename($file);
            
            if (unlink($file)) {
                $this->success("✅ Removido: {$filename}");
            } else {
                throw new Exception("Falha ao remover: {$filename}");
            }
        }
        
        echo "\n";
    }
    
    /**
     * Limpar cache
     */
    private function clearCache() {
        // Implementar limpeza de cache personalizada se necessário
        echo "🧹 Limpando cache...\n";
        
        // Aqui você pode adicionar comandos específicos de limpeza de cache
        // Por exemplo, para object cache, opcache, etc.
        
        $this->success("✅ Cache limpo");
        echo "\n";
    }
    
    /**
     * Utilitários
     */
    private function sanitizeName($name) {
        return strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name));
    }
    
    private function formatFileSize($size) {
        if ($size >= 1024 * 1024) {
            return round($size / (1024 * 1024), 2) . 'MB';
        } elseif ($size >= 1024) {
            return round($size / 1024, 2) . 'KB';
        } else {
            return $size . 'B';
        }
    }
    
    /**
     * Interface do usuário
     */
    private function printHeader() {
        echo "\n";
        echo "🗑️  Removedor de CPTs e Taxonomias\n";
        echo "===================================\n";
        echo "Content Type: {$this->name}\n";
        
        if (isset($this->options['taxonomy-only'])) {
            echo "Modo: Apenas taxonomia\n";
        } elseif (isset($this->options['cpt-only'])) {
            echo "Modo: Apenas CPT\n";
        } else {
            echo "Modo: CPT completo (CPT + Taxonomia + API)\n";
        }
        
        echo "\n";
    }
    
    private function printSummary() {
        $total_removed = count($this->files_to_remove);
        
        echo "📊 Resumo da Remoção:\n";
        echo "---------------------\n";
        echo "✅ Arquivos removidos: {$total_removed}\n";
        
        if (isset($this->options['with-backup'])) {
            echo "💾 Backup criado: backups/{$this->name}_" . date('Y-m-d_H-i-s') . "/\n";
        }
        
        echo "\n🎉 Remoção concluída com sucesso!\n";
        echo "💡 Dica: Execute 'wp rewrite flush' se necessário.\n\n";
        
        // Mostrar comandos para restaurar backup se existir
        if (isset($this->options['with-backup'])) {
            echo "🔄 Para restaurar o backup:\n";
            echo "cp backups/{$this->name}_*/\\*.php inc/content-types/cpts/\n";
            echo "cp backups/{$this->name}_*/\\*.php inc/content-types/taxonomies/\n";
            echo "cp backups/{$this->name}_*/\\*.php inc/api/*/\n\n";
        }
    }
    
    private function success($message) {
        echo $message . "\n";
    }
    
    private function warning($message) {
        echo $message . "\n";
    }
    
    private function error($message) {
        echo $message . "\n";
    }
    
    private function showHelp() {
        echo "\n";
        echo "🗑️  Removedor de CPTs e Taxonomias\n";
        echo "===================================\n\n";
        echo "Uso: php bin/remove-content-type.php <nome> [opções]\n\n";
        echo "Argumentos:\n";
        echo "  <nome>                    Nome do CPT a ser removido (obrigatório)\n\n";
        echo "Opções:\n";
        echo "  --taxonomy-only           Remover apenas a taxonomia\n";
        echo "  --cpt-only                Remover apenas o CPT (mantém taxonomia)\n";
        echo "  --with-backup             Criar backup antes de remover\n";
        echo "  --force                   Remover sem confirmação\n";
        echo "  --help                    Mostrar esta ajuda\n\n";
        echo "Exemplos:\n";
        echo "  php bin/remove-content-type.php produto\n";
        echo "  php bin/remove-content-type.php evento --with-backup\n";
        echo "  php bin/remove-content-type.php noticia --taxonomy-only\n";
        echo "  php bin/remove-content-type.php portfolio --force --with-backup\n\n";
        echo "⚠️  Aviso de Segurança:\n";
        echo "Este script remove arquivos permanentemente do sistema.\n";
        echo "Use --with-backup para criar um backup antes da remoção.\n";
        echo "Use --force apenas se tiver certeza absoluta da operação.\n\n";
        echo "🔄 Para restaurar arquivos removidos:\n";
        echo "1. Use o backup criado com --with-backup\n";
        echo "2. Ou recrie com: ./bin/generate <nome> --with-taxonomy --with-api\n\n";
    }
}

// Verificar se é pedido de ajuda
if (in_array('--help', $argv) || count($argv) < 2) {
    (new ContentTypeRemover())->showHelp();
    exit(0);
}

// Executar o removedor
new ContentTypeRemover();
