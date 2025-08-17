#!/usr/bin/env php
<?php
/**
 * Script para listar CPTs e Taxonomias registrados
 * 
 * List all Custom Post Types e Taxonomias criados pelo sistema
 * 
 * Uso:
 * php scripts/list-cpts.php
 * php scripts/list-cpts.php --format=json
 * php scripts/list-cpts.php --only=cpts
 * php scripts/list-cpts.php --only=taxonomies
 * 
 * @package FuerzaThemeAPI
 */

// Verificar se está sendo executado via CLI
if (php_sapi_name() !== 'cli') {
    die("This script must be run via command line.\n");
}

class CPTLister {
    
    private $theme_path;
    private $options;
    
    public function __construct() {
        $this->theme_path = dirname(__DIR__);
        $this->options = $this->parseArguments();
        $this->run();
    }
    
    /**
     * Run the lister
     */
    private function run() {
        $this->printHeader();
        
        $cpts = $this->getCPTs();
        $taxonomies = $this->getTaxonomies();
        $apis = $this->getAPIFiles();
        
        $format = $this->options['format'] ?? 'table';
        $only = $this->options['only'] ?? 'all';
        
        if ($format === 'json') {
            $this->outputJSON($cpts, $taxonomies, $apis, $only);
        } else {
            $this->outputTable($cpts, $taxonomies, $apis, $only);
        }
    }
    
    /**
     * Parse command line arguments
     */
    private function parseArguments() {
        global $argv;
        $options = [];
        
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
     * Get CPTs list
     */
    private function getCPTs() {
        $cptsDir = $this->theme_path . '/inc/content-types/cpts';
        $cpts = [];
        
        if (!is_dir($cptsDir)) {
            return $cpts;
        }
        
        $files = glob($cptsDir . '/*.php');
        
        foreach ($files as $file) {
            $name = basename($file, '.php');
            $content = file_get_contents($file);
            
            // Extract file information
            $info = $this->extractCPTInfo($content, $name);
            $info['file'] = str_replace($this->theme_path . '/', '', $file);
            $info['size'] = $this->formatFileSize(filesize($file));
            $info['modified'] = date('Y-m-d H:i:s', filemtime($file));
            
            $cpts[$name] = $info;
        }
        
        return $cpts;
    }
    
    /**
     * Get taxonomies list
     */
    private function getTaxonomies() {
        $taxonomiesDir = $this->theme_path . '/inc/content-types/taxonomies';
        $taxonomies = [];
        
        if (!is_dir($taxonomiesDir)) {
            return $taxonomies;
        }
        
        $files = glob($taxonomiesDir . '/*.php');
        
        foreach ($files as $file) {
            $name = basename($file, '.php');
            $content = file_get_contents($file);
            
            // Extract file information
            $info = $this->extractTaxonomyInfo($content, $name);
            $info['file'] = str_replace($this->theme_path . '/', '', $file);
            $info['size'] = $this->formatFileSize(filesize($file));
            $info['modified'] = date('Y-m-d H:i:s', filemtime($file));
            
            $taxonomies[$name] = $info;
        }
        
        return $taxonomies;
    }
    
    /**
     * Get API files list
     */
    private function getAPIFiles() {
        $apiFiles = [];
        
        $directories = [
            'routes' => '/inc/api/routes',
            'handlers' => '/inc/api/handlers',
            'formatters' => '/inc/api/formatters'
        ];
        
        foreach ($directories as $type => $dir) {
            $fullDir = $this->theme_path . $dir;
            
            if (is_dir($fullDir)) {
                $files = glob($fullDir . '/*.php');
                
                foreach ($files as $file) {
                    $name = basename($file, '.php');
                    
                    // Extrair o nome base do CPT
                    $cptName = '';
                    if ($type === 'routes' && str_ends_with($name, '-routes')) {
                        $cptName = str_replace('-routes', '', $name);
                    } elseif ($type === 'handlers' && str_starts_with($name, 'class-') && str_ends_with($name, '-handler')) {
                        $cptName = str_replace(['class-', '-handler'], '', $name);
                    } elseif ($type === 'formatters' && str_starts_with($name, 'class-') && str_ends_with($name, '-formatter')) {
                        $cptName = str_replace(['class-', '-formatter'], '', $name);
                    }
                    
                    if ($cptName) {
                        if (!isset($apiFiles[$cptName])) {
                            $apiFiles[$cptName] = [];
                        }
                        
                        $apiFiles[$cptName][$type] = [
                            'file' => str_replace($this->theme_path . '/', '', $file),
                            'size' => $this->formatFileSize(filesize($file)),
                            'modified' => date('Y-m-d H:i:s', filemtime($file))
                        ];
                    }
                }
            }
        }
        
        return $apiFiles;
    }
    
    /**
     * Extract CPT information
     */
    private function extractCPTInfo($content, $name) {
        $info = [
            'name' => $name,
            'singular' => ucfirst($name),
            'plural' => ucfirst($name) . 's',
            'description' => 'N/A'
        ];
        
        // Extrair singular_name
        if (preg_match("/'singular_name'\s*=>\s*'([^']+)'/", $content, $matches)) {
            $info['singular'] = $matches[1];
        }
        
        // Extrair plural_name
        if (preg_match("/'plural_name'\s*=>\s*'([^']+)'/", $content, $matches)) {
            $info['plural'] = $matches[1];
        }
        
        // Extrair description
        if (preg_match("/'description'\s*=>\s*'([^']+)'/", $content, $matches)) {
            $info['description'] = $matches[1];
        }
        
        return $info;
    }
    
    /**
     * Extract taxonomy information
     */
    private function extractTaxonomyInfo($content, $name) {
        $info = [
            'name' => $name,
            'singular' => ucfirst($name),
            'plural' => ucfirst($name) . 's',
            'post_types' => [],
            'hierarchical' => false
        ];
        
        // Extrair singular_name
        if (preg_match("/'singular_name'\s*=>\s*'([^']+)'/", $content, $matches)) {
            $info['singular'] = $matches[1];
        }
        
        // Extrair plural_name
        if (preg_match("/'plural_name'\s*=>\s*'([^']+)'/", $content, $matches)) {
            $info['plural'] = $matches[1];
        }
        
        // Extrair post_types
        if (preg_match("/register_taxonomy\s*\(\s*'[^']+'\s*,\s*\[\s*'([^']+)'\s*\]/", $content, $matches)) {
            $info['post_types'] = [$matches[1]];
        }
        
        // Extrair hierarchical
        if (preg_match("/'hierarchical'\s*=>\s*(true|false)/", $content, $matches)) {
            $info['hierarchical'] = $matches[1] === 'true';
        }
        
        return $info;
    }
    
    /**
     * Format file size
     */
    private function formatFileSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    /**
     * JSON format output
     */
    private function outputJSON($cpts, $taxonomies, $apis, $only) {
        $output = [];
        
        if ($only === 'all' || $only === 'cpts') {
            $output['cpts'] = $cpts;
        }
        
        if ($only === 'all' || $only === 'taxonomies') {
            $output['taxonomies'] = $taxonomies;
        }
        
        if ($only === 'all') {
            $output['api_files'] = $apis;
            $output['summary'] = [
                'total_cpts' => count($cpts),
                'total_taxonomies' => count($taxonomies),
                'total_api_sets' => count($apis)
            ];
        }
        
        echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }
    
    /**
     * Table format output
     */
    private function outputTable($cpts, $taxonomies, $apis, $only) {
        if ($only === 'all' || $only === 'cpts') {
            $this->printCPTsTable($cpts);
        }
        
        if ($only === 'all' || $only === 'taxonomies') {
            $this->printTaxonomiesTable($taxonomies);
        }
        
        if ($only === 'all') {
            $this->printAPIFilesTable($apis);
            $this->printSummary($cpts, $taxonomies, $apis);
        }
    }
    
    /**
     * Print CPTs table
     */
    private function printCPTsTable($cpts) {
        if (empty($cpts)) {
            echo "📝 No Custom Post Type found.\n\n";
            return;
        }
        
        echo "📝 CUSTOM POST TYPES\n";
        echo str_repeat("=", 80) . "\n";
        
        $this->printTableHeader(['Nome', 'Singular', 'Plural', 'Arquivo', 'Tamanho', 'Modificado']);
        
        foreach ($cpts as $cpt) {
            $this->printTableRow([
                $cpt['name'],
                $cpt['singular'],
                $cpt['plural'],
                $cpt['file'],
                $cpt['size'],
                $cpt['modified']
            ]);
        }
        
        echo "\n";
    }
    
    /**
     * Print taxonomies table
     */
    private function printTaxonomiesTable($taxonomies) {
        if (empty($taxonomies)) {
            echo "🏷️  No taxonomy found.\n\n";
            return;
        }
        
        echo "🏷️  TAXONOMIAS\n";
        echo str_repeat("=", 80) . "\n";
        
        $this->printTableHeader(['Nome', 'Singular', 'Plural', 'Hierárquica', 'Arquivo', 'Modificado']);
        
        foreach ($taxonomies as $taxonomy) {
            $this->printTableRow([
                $taxonomy['name'],
                $taxonomy['singular'],
                $taxonomy['plural'],
                $taxonomy['hierarchical'] ? 'Sim' : 'Não',
                $taxonomy['file'],
                $taxonomy['modified']
            ]);
        }
        
        echo "\n";
    }
    
    /**
     * Print API files table
     */
    private function printAPIFilesTable($apis) {
        if (empty($apis)) {
            echo "📡 No API file found.\n\n";
            return;
        }
        
        echo "📡 ARQUIVOS DA API\n";
        echo str_repeat("=", 80) . "\n";
        
        foreach ($apis as $cptName => $files) {
            echo "CPT: {$cptName}\n";
            
            foreach ($files as $type => $info) {
                $typeIcon = [
                    'routes' => '🛣️ ',
                    'handlers' => '⚡',
                    'formatters' => '🎨'
                ];
                
                echo "  {$typeIcon[$type]} {$type}: {$info['file']} ({$info['size']})\n";
            }
            
            echo "\n";
        }
    }
    
    /**
     * Print summary
     */
    private function printSummary($cpts, $taxonomies, $apis) {
        echo "📊 RESUMO\n";
        echo str_repeat("=", 40) . "\n";
        echo "📝 Custom Post Types: " . count($cpts) . "\n";
        echo "🏷️  Taxonomias: " . count($taxonomies) . "\n";
        echo "📡 Conjuntos de API: " . count($apis) . "\n";
        
        // Verificar integridade
        $incomplete = [];
        foreach ($cpts as $cptName => $cpt) {
            if (!isset($apis[$cptName])) {
                $incomplete[] = $cptName;
            }
        }
        
        if (!empty($incomplete)) {
            echo "\n⚠️  CPTs without complete API: " . implode(', ', $incomplete) . "\n";
        }
        
        echo "\n";
    }
    
    /**
     * Print table header
     */
    private function printTableHeader($headers) {
        $widths = [15, 20, 20, 30, 10, 20];
        
        for ($i = 0; $i < count($headers); $i++) {
            echo str_pad($headers[$i], $widths[$i]);
        }
        echo "\n";
        
        for ($i = 0; $i < count($headers); $i++) {
            echo str_repeat("-", $widths[$i]);
        }
        echo "\n";
    }
    
    /**
     * Print table row
     */
    private function printTableRow($values) {
        $widths = [15, 20, 20, 30, 10, 20];
        
        for ($i = 0; $i < count($values); $i++) {
            $value = $values[$i] ?? '';
            if (strlen($value) > $widths[$i] - 1) {
                $value = substr($value, 0, $widths[$i] - 4) . '...';
            }
            echo str_pad($value, $widths[$i]);
        }
        echo "\n";
    }
    
    /**
     * Print header
     */
    private function printHeader() {
        if (isset($this->options['format']) && $this->options['format'] === 'json') {
            return;
        }
        
        echo "\n";
        echo "🗂️  CPTs and Taxonomies Listing\n";
        echo "==================================\n\n";
    }
}

// Verificar se é pedido de ajuda
if (in_array('--help', $argv)) {
    echo "\n";
    echo "🗂️  CPTs and Taxonomies Listing\n";
    echo "==================================\n\n";
    echo "Uso: php scripts/list-cpts.php [opções]\n\n";
    echo "Opções:\n";
    echo "  --format=json         JSON format output\n";
    echo "  --only=cpts           Listar apenas CPTs\n";
    echo "  --only=taxonomies     Listar apenas taxonomias\n";
    echo "  --help                Mostrar esta ajuda\n\n";
    echo "Exemplos:\n";
    echo "  php scripts/list-cpts.php\n";
    echo "  php scripts/list-cpts.php --format=json\n";
    echo "  php scripts/list-cpts.php --only=cpts\n";
    echo "  php scripts/list-cpts.php --only=taxonomies --format=json\n\n";
    exit(0);
}

// Run the lister
new CPTLister();
