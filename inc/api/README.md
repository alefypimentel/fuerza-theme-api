# API REST - Estrutura Modular

Esta é a estrutura modular da API REST personalizada do tema.

## 📁 Estrutura de Diretórios

```
inc/api/
├── class-api-manager.php       # Gerenciador principal da API
├── formatters/                 # Formatadores de dados
│   ├── class-base-formatter.php
│   └── class-eventos-formatter.php
├── handlers/                   # Manipuladores de rotas
│   └── class-eventos-handler.php
├── routes/                     # Definições de rotas
│   ├── ping-routes.php
│   └── eventos-routes.php
└── README.md                   # Esta documentação
```

## 🔧 Como Funciona

### 1. API Manager

- **Arquivo**: `class-api-manager.php`
- **Função**: Gerencia todas as rotas da API
- **Padrão**: Singleton
- **Autoload**: Carrega automaticamente todos os arquivos de rotas

### 2. Formatters

- **Diretório**: `formatters/`
- **Função**: Formatam dados antes de retornar na API
- **Herança**: `Base_Formatter` → `Eventos_Formatter`

### 3. Handlers

- **Diretório**: `handlers/`
- **Função**: Contêm a lógica de negócio das rotas
- **Validação**: Incluem validação de parâmetros

### 4. Routes

- **Diretório**: `routes/`
- **Função**: Registram as rotas no sistema
- **Autoload**: Carregados automaticamente pelo API Manager

## 🚀 Rotas Disponíveis

### Ping

- **GET** `/wp-json/fuerza-theme/v1/ping`
- **Função**: Testar se a API está funcionando

### Eventos

- **GET** `/wp-json/fuerza-theme/v1/eventos`
  - Parâmetros: `per_page`, `page`, `orderby`, `order`, `categoria`, `tag`, `busca`
- **GET** `/wp-json/fuerza-theme/v1/eventos/{id}`
  - Obter evento específico por ID

## 📝 Como Adicionar Novas Rotas

### 1. Criar o Formatter (se necessário)

```php
// inc/api/formatters/class-meu-cpt-formatter.php
class Meu_CPT_Formatter extends Base_Formatter {
    public static function format_meu_cpt($post_id) {
        // Lógica de formatação
    }
}
```

### 2. Criar o Handler

```php
// inc/api/handlers/class-meu-cpt-handler.php
class Meu_CPT_Handler {
    public static function get_meu_cpt($request) {
        // Lógica da rota
    }

    public static function validate_params() {
        // Validação de parâmetros
    }
}
```

### 3. Registrar as Rotas

```php
// inc/api/routes/meu-cpt-routes.php
$api_manager = API_Manager::get_instance();

$api_manager->add_route(
    '/meu-cpt',
    'GET',
    [Meu_CPT_Handler::class, 'get_meu_cpt'],
    Meu_CPT_Handler::validate_params()
);
```

## ✅ Vantagens da Estrutura

- **Modularidade**: Cada CPT tem seus próprios arquivos
- **Manutenibilidade**: Fácil de encontrar e editar código específico
- **Escalabilidade**: Fácil adicionar novas rotas e CPTs
- **Organização**: Separação clara de responsabilidades
- **Autoload**: Sistema automático de carregamento
- **Reutilização**: Base formatters podem ser herdados
- **Validação**: Sistema robusto de validação de parâmetros

## 🔧 Funções Utilitárias

```php
// Obter namespace da API
fuerza_theme_get_api_namespace();

// Adicionar nova rota (método alternativo)
fuerza_theme_add_api_route($endpoint, $methods, $callback, $args);
```
