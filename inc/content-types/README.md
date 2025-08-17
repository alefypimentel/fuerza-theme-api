# Sistema Dinâmico de CPTs e Taxonomias

Este sistema permite criar e gerenciar Custom Post Types (CPTs) e Taxonomias de forma modular e dinâmica.

## 📁 Estrutura de Diretórios

```
inc/content-types/
├── class-content-manager.php      # 🎛️ Gerenciador principal
├── class-cpt-manager.php          # 📝 Gerenciador de CPTs
├── class-taxonomy-manager.php     # 🏷️ Gerenciador de taxonomias
├── cpts/                          # 📁 Custom Post Types
│   ├── eventos.php               # CPT de eventos
│   └── exemplo-produtos.php.exemplo
├── taxonomies/                    # 📁 Taxonomias
│   ├── categoria-evento.php      # Taxonomia de categorias de eventos
│   └── exemplo-categoria-produto.php.exemplo
└── README.md                      # 📖 Esta documentação
```

## 🚀 Como Funciona

### Carregamento Automático

- Todos os arquivos `.php` em `cpts/` e `taxonomies/` são carregados automaticamente
- Não é necessário incluir manualmente os arquivos
- Sistema baseado em convenções

### Gerenciamento Centralizado

- **Content_Manager**: Interface principal para todas as operações
- **CPT_Manager**: Especializado em Custom Post Types
- **Taxonomy_Manager**: Especializado em Taxonomias

## 📝 Como Adicionar Novos CPTs

### Método 1: Completo (Recomendado)

Crie um arquivo em `inc/content-types/cpts/meu-cpt.php`:

```php
<?php
Content_Manager::register_cpt('meu_cpt', [
    'singular_name' => 'Meu Item',
    'plural_name' => 'Meus Itens',
    'description' => 'Descrição do CPT',
    'public' => true,
    'show_in_rest' => true,
    'supports' => ['title', 'editor', 'thumbnail'],
    'menu_icon' => 'dashicons-admin-post',

    // Colunas administrativas personalizadas
    'admin_columns' => [
        'minha_coluna' => [
            'title' => 'Título da Coluna',
            'callback' => function($column, $post_id) {
                // Lógica para popular a coluna
            }
        ]
    ],

    // Hooks personalizados
    'hooks' => [
        'save_post_meu_cpt' => function($post_id) {
            // Lógica executada ao salvar
        }
    ]
]);
```

### Método 2: Simples (Para casos básicos)

```php
<?php
Content_Manager::create_simple_cpt(
    'meu_cpt',           // Post type
    'Meu Item',          // Nome singular
    'Meus Itens',        // Nome plural
    [                    // Opções extras (opcional)
        'menu_icon' => 'dashicons-admin-post',
        'supports' => ['title', 'editor', 'thumbnail'],
    ]
);
```

## 🏷️ Como Adicionar Novas Taxonomias

### Método 1: Completo (Recomendado)

Crie um arquivo em `inc/content-types/taxonomies/minha-taxonomia.php`:

```php
<?php
Content_Manager::register_taxonomy('minha_taxonomia', ['meu_cpt'], [
    'singular_name' => 'Minha Categoria',
    'plural_name' => 'Minhas Categorias',
    'hierarchical' => true,
    'show_in_rest' => true,
    'show_admin_column' => true,

    // Campos meta personalizados
    'term_meta_fields' => [
        [
            'key' => 'meu_campo',
            'label' => 'Meu Campo',
            'type' => 'text',
            'description' => 'Descrição do campo'
        ]
    ],

    // Colunas administrativas
    'admin_columns' => [
        'minha_coluna' => [
            'title' => 'Título',
            'callback' => function($content, $column, $term_id) {
                // Lógica da coluna
                return $content;
            }
        ]
    ]
]);
```

### Método 2: Simples (Para casos básicos)

```php
<?php
Content_Manager::create_simple_taxonomy(
    'minha_taxonomia',   // Taxonomia
    ['meu_cpt'],         // CPTs associados
    'Minha Categoria',   // Nome singular
    'Minhas Categorias', // Nome plural
    [                    // Opções extras (opcional)
        'hierarchical' => true,
        'show_admin_column' => true,
    ]
);
```

## 🛠️ Configurações Avançadas

### CPTs - Opções Disponíveis

- `singular_name`, `plural_name`: Nomes do CPT
- `description`: Descrição do CPT
- `public`: Visibilidade pública
- `show_in_rest`: Habilitar na API REST
- `supports`: Recursos suportados (title, editor, thumbnail, etc.)
- `has_archive`: Ter página de arquivo
- `menu_icon`: Ícone do menu administrativo
- `menu_position`: Posição no menu
- `rewrite`: Configurações de URL
- `admin_columns`: Colunas personalizadas no admin
- `hooks`: Hooks personalizados
- `meta_boxes`: Meta boxes personalizados

### Taxonomias - Opções Disponíveis

- `singular_name`, `plural_name`: Nomes da taxonomia
- `description`: Descrição da taxonomia
- `hierarchical`: Se é hierárquica (como categorias) ou não (como tags)
- `public`: Visibilidade pública
- `show_in_rest`: Habilitar na API REST
- `show_admin_column`: Mostrar coluna no admin do CPT
- `rewrite`: Configurações de URL
- `term_meta_fields`: Campos personalizados para termos
- `admin_columns`: Colunas personalizadas no admin
- `hooks`: Hooks personalizados

### Tipos de Campos Meta para Taxonomias

- `text`: Campo de texto
- `textarea`: Área de texto
- `number`: Campo numérico
- `email`: Campo de email
- `url`: Campo de URL
- `color`: Seletor de cor
- `select`: Lista suspensa (requer `options`)

## 🎯 Funções Utilitárias

```php
// Verificar se um CPT existe
Content_Manager::cpt_exists('meu_cpt');

// Verificar se uma taxonomia existe
Content_Manager::taxonomy_exists('minha_taxonomia');

// Obter todos os CPTs registrados
Content_Manager::get_all_cpts();

// Obter todas as taxonomias registradas
Content_Manager::get_all_taxonomies();

// Obter configuração específica
Content_Manager::get_cpt_config('meu_cpt');
Content_Manager::get_taxonomy_config('minha_taxonomia');
```

## ✅ Vantagens do Sistema

- **🔄 Autoload**: Carregamento automático de CPTs e taxonomias
- **🎨 Flexibilidade**: Configurações personalizáveis para cada caso
- **📋 Organização**: Cada CPT/taxonomia em seu próprio arquivo
- **🔧 Manutenibilidade**: Fácil encontrar e editar código específico
- **📈 Escalabilidade**: Adicionar novos tipos de conteúdo é simples
- **🎯 Reutilização**: Sistema baseado em classes reutilizáveis
- **🛡️ Validação**: Sistema robusto de validação e sanitização
- **📊 Admin**: Colunas e campos administrativos personalizados
- **🔗 Hooks**: Sistema de hooks para funcionalidades personalizadas

## 📖 Exemplos Práticos

Consulte os arquivos `.exemplo` para ver implementações completas de:

- CPT de Produtos com colunas administrativas
- Taxonomia de Categorias com campos meta personalizados
- Hooks personalizados para limpeza de cache
- Configurações avançadas de labels e permissões
