# 🚀 Fuerza Theme API

[![WordPress](https://img.shields.io/badge/WordPress-6.0+-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-8.0+-green.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2-orange.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Version](https://img.shields.io/badge/Version-2.0-brightgreen.svg)](https://github.com/fuerza-studio/fuerza-theme-api)

> **Tema WordPress profissional para APIs REST com sistema modular de CPTs, Taxonomias e suporte multilíngue completo**

## 📋 Índice

- [Visão Geral](#-visão-geral)
- [✨ Funcionalidades Principais](#-funcionalidades-principais)
- [🏗️ Arquitetura](#️-arquitetura)
- [📁 Estrutura do Projeto](#-estrutura-do-projeto)
- [🚀 Instalação](#-instalação)
- [⚙️ Configuração](#️-configuração)
- [🔧 Uso](#-uso)
- [🌍 Sistema de Traduções](#-sistema-de-traduções)
- [🔍 API REST](#-api-rest)
- [📊 Custom Post Types](#-custom-post-types)
- [🧪 Testes](#-testes)
- [📚 Documentação](#-documentação)
- [🤝 Contribuição](#-contribuição)
- [📄 Licença](#-licença)

## 🌟 Visão Geral

O **Fuerza Theme API** é um tema WordPress profissional desenvolvido especificamente para projetos que necessitam de uma API REST robusta, sistema de traduções multilíngue e gerenciamento avançado de Custom Post Types (CPTs).

### 🎯 **Para que serve?**

- **APIs REST** para aplicações mobile e frontend
- **Sites multilíngue** com WPML ou Polylang
- **Gerenciamento de conteúdo** via API
- **Headless WordPress** para aplicações modernas
- **Sistemas de e-commerce** com produtos multilíngue
- **Portais de conteúdo** com taxonomias avançadas

### 🏆 **Por que escolher?**

- ✅ **API-First**: Desenvolvido pensando em APIs desde o início
- ✅ **Multilíngue Nativo**: Suporte completo a traduções
- ✅ **Modular**: Sistema de CPTs e taxonomias extensível
- ✅ **Profissional**: Código limpo e bem documentado
- ✅ **Performance**: Otimizado para alta performance
- ✅ **SEO Ready**: Integração completa com Yoast SEO

## ✨ Funcionalidades Principais

### 🌍 **Sistema de Traduções**

- **Suporte completo ao WPML** e **Polylang**
- **Traduções automáticas** de conteúdo e campos ACF
- **API multilíngue** com todas as traduções em uma requisição
- **Fallback inteligente** para idiomas não traduzidos

### 🔌 **API REST Avançada**

- **Endpoints automáticos** para todos os CPTs
- **Formatação inteligente** de dados
- **Validação de parâmetros** robusta
- **Sistema de permissões** configurável
- **Rate limiting** e cache integrados

### 📝 **Custom Post Types**

- **Sistema modular** de CPTs
- **Geração automática** de rotas da API
- **Taxonomias personalizadas** integradas
- **Campos ACF** com suporte completo
- **SEO integrado** com Yoast

### 🎨 **Interface Administrativa**

- **Dashboard personalizado** para gerenciamento
- **Monitoramento** de performance da API
- **Configurações avançadas** de CPTs
- **Logs e estatísticas** em tempo real

## 🏗️ Arquitetura

```
fuerza-theme-api/
├── 📁 inc/                    # Core do tema
│   ├── 📁 api/               # Sistema da API REST
│   ├── 📁 content-types/     # Gerenciamento de CPTs
│   └── 📄 class-*.php        # Classes principais
├── 📁 scripts/               # Scripts de teste e utilidades
├── 📄 functions.php          # Arquivo principal do tema
├── 📄 index.php              # Template principal
└── 📄 package.json           # Dependências e scripts
```

### 🔄 **Fluxo de Dados**

```
WordPress → CPT Manager → API Manager → Formatters → REST Response
    ↓              ↓            ↓           ↓           ↓
  Content    →  Structure  →  Routes  →  Format  →  JSON/XML
```

## 📁 Estrutura do Projeto

### **📂 `inc/` - Core do Tema**

#### **`api/` - Sistema da API REST**

- **`class-api-manager.php`** - Gerenciador principal da API
- **`routes/`** - Definição das rotas da API
- **`handlers/`** - Lógica de processamento das requisições
- **`formatters/`** - Formatação dos dados de resposta

#### **`content-types/` - Gerenciamento de Conteúdo**

- **`class-cpt-manager.php`** - Gerenciador de Custom Post Types
- **`class-taxonomy-manager.php`** - Gerenciador de Taxonomias
- **`class-content-manager.php`** - Gerenciador geral de conteúdo
- **`cpts/`** - Definições dos CPTs
- **`taxonomies/`** - Definições das taxonomias

#### **Classes Principais**

- **`class-translation-support.php`** - Sistema de traduções multilíngue
- **`admin-dashboard.php`** - Interface administrativa personalizada
- **`setup.php`** - Configurações iniciais do tema
- **`helpers.php`** - Funções auxiliares

### **📂 `scripts/` - Utilitários e Testes**

- **`test-translations.js`** - Teste do sistema de traduções
- **`test-seo.js`** - Teste dos dados de SEO
- **`api-test.js`** - Teste geral da API
- **`create-cpt.js`** - Criação de novos CPTs
- **`help.js`** - Sistema de ajuda

## 🚀 Instalação

### **Requisitos do Sistema**

- **WordPress**: 6.0 ou superior
- **PHP**: 8.0 ou superior
- **MySQL**: 5.7 ou superior
- **Node.js**: 16.0 ou superior (para scripts)

### **Passo a Passo**

1. **Clone o repositório**

```bash
git clone https://github.com/fuerza-studio/fuerza-theme-api.git
cd fuerza-theme-api
```

2. **Instale as dependências**

```bash
npm install
```

3. **Ative o tema no WordPress**

```bash
# Copie para wp-content/themes/
cp -r . /path/to/wordpress/wp-content/themes/fuerza-theme-api/
```

4. **Configure o banco de dados**

```bash
npm run db:migrate
```

5. **Ative o tema no painel WordPress**

- Vá para **Aparência > Temas**
- Ative o **Fuerza Theme API**

## ⚙️ Configuração

### **Configuração Básica**

1. **Configurar idiomas** (se usar multilíngue)
2. **Configurar CPTs** conforme necessário
3. **Configurar taxonomias** personalizadas
4. **Configurar campos ACF** (se necessário)

### **Configuração da API**

```php
// Em functions.php ou arquivo de configuração
define('FUERZA_API_VERSION', 'v1');
define('FUERZA_API_NAMESPACE', 'fuerza-theme');
define('FUERZA_API_CACHE_ENABLED', true);
define('FUERZA_API_RATE_LIMIT', 1000); // requests per hour
```

### **Configuração de Traduções**

```php
// Suporte automático para WPML e Polylang
// Configurações adicionais em inc/class-translation-support.php
```

## 🔧 Uso

### **Scripts NPM Disponíveis**

```bash
# Desenvolvimento
npm run dev              # Modo desenvolvimento
npm run watch            # Watch mode para arquivos

# Build
npm run build            # Build completo para produção
npm run build:css        # Build apenas CSS
npm run build:js         # Build apenas JavaScript

# Testes
npm run test             # Todos os testes
npm run test:translations # Teste de traduções
npm run test:seo         # Teste de SEO
npm run test:api         # Teste da API

# Gerenciamento de CPTs
npm run cpt:create       # Criar novo CPT
npm run cpt:list         # Listar CPTs existentes
npm run cpt:remove       # Remover CPT

# Utilitários
npm run help             # Sistema de ajuda
npm run docs             # Gerar documentação
```

### **Uso da API REST**

#### **Endpoints Disponíveis**

```bash
# Health Check
GET /wp-json/fuerza-theme/v1/ping

# Produtos
GET /wp-json/fuerza-theme/v1/Produtos
GET /wp-json/fuerza-theme/v1/Produtos/{id}

# Eventos
GET /wp-json/fuerza-theme/v1/eventos
GET /wp-json/fuerza-theme/v1/eventos/{id}

# Idiomas
GET /wp-json/fuerza-theme/v1/languages
```

#### **Exemplo de Uso**

```javascript
// Buscar produtos com traduções
const response = await fetch("/wp-json/fuerza-theme/v1/Produtos");
const data = await response.json();

// Dados retornados incluem:
// - Conteúdo principal
// - Todas as traduções disponíveis
// - Dados de SEO
// - Campos ACF traduzidos
```

## 🌍 Sistema de Traduções

### **Funcionalidades**

- ✅ **Traduções automáticas** de conteúdo
- ✅ **Campos ACF traduzidos**
- ✅ **Dados de SEO multilíngue**
- ✅ **Fallback inteligente**
- ✅ **Suporte WPML/Polylang**

### **Estrutura da Resposta**

```json
{
  "id": 87,
  "titulo": "Produto Um",
  "language": "pt-br",
  "seo": { ... },
  "translations": {
    "en": {
      "titulo": "Product One",
      "conteudo": "Product content",
      "seo": { ... }
    },
    "es": {
      "titulo": "Producto Uno",
      "conteudo": "Contenido del producto",
      "seo": { ... }
    }
  }
}
```

## 🔍 API REST

### **Características**

- **RESTful** por padrão
- **JSON** como formato principal
- **Validação** de parâmetros
- **Cache** integrado
- **Rate limiting** configurável
- **Logs** detalhados

### **Parâmetros Comuns**

```bash
# Paginação
?page=1&per_page=10

# Ordenação
?orderby=date&order=DESC

# Filtros
?categoria=tecnologia&busca=smartphone

# Idioma
?lang=en
```

### **Respostas Padrão**

```json
{
  "data": [...],
  "pagination": {
    "total": 100,
    "pages": 10,
    "current_page": 1,
    "per_page": 10
  },
  "language_info": {
    "current_language": "pt-br",
    "available_languages": ["en", "es", "pt-br"]
  }
}
```

## 📊 Custom Post Types

### **CPTs Padrão**

- **`produto`** - Sistema de produtos
- **`evento`** - Sistema de eventos
- **Extensível** para novos tipos

### **Taxonomias**

- **`categoria_produto`** - Categorias de produtos
- **`categoria_evento`** - Categorias de eventos
- **Extensível** para novas taxonomias

### **Criando Novos CPTs**

```bash
# Via linha de comando
npm run cpt:create

# Ou manualmente em inc/content-types/cpts/
```

## 🧪 Testes

### **Executando Testes**

```bash
# Teste completo
npm run test

# Teste específico
npm run test:translations
npm run test:seo
npm run test:api
```

### **Scripts de Teste**

- **`test-translations.js`** - Valida sistema de traduções
- **`test-seo.js`** - Valida dados de SEO
- **`api-test.js`** - Valida endpoints da API

## 📚 Documentação

### **Documentação Técnica**

- **`TRANSLATIONS_README.md`** - Sistema de traduções
- **`inc/api/README.md`** - Sistema da API
- **`inc/content-types/README.md`** - Sistema de CPTs

### **Gerando Documentação**

```bash
npm run docs
npm run api:docs
```

## 🤝 Contribuição

### **Como Contribuir**

1. **Fork** o projeto
2. **Crie** uma branch para sua feature
3. **Commit** suas mudanças
4. **Push** para a branch
5. **Abra** um Pull Request

### **Padrões de Código**

- **PSR-12** para PHP
- **ESLint** para JavaScript
- **Stylelint** para SCSS/CSS
- **Documentação** em português

### **Estrutura de Commits**

```
feat: adiciona novo sistema de cache
fix: corrige bug nas traduções
docs: atualiza documentação da API
style: formata código PHP
refactor: refatora gerenciador de CPTs
test: adiciona testes para SEO
```

## 📄 Licença

Este projeto está licenciado sob a **GPL v2** - veja o arquivo [LICENSE](LICENSE) para detalhes.

### **Requisitos da Licença**

- ✅ **Software livre** e open source
- ✅ **Modificações** permitidas
- ✅ **Distribuição** permitida
- ✅ **Uso comercial** permitido
- ⚠️ **Derivados** devem manter a licença GPL

---

## 🆘 Suporte

### **Canais de Suporte**

- **Issues**: [GitHub Issues](https://github.com/fuerza-studio/fuerza-theme-api/issues)
- **Documentação**: [Wiki do Projeto](https://github.com/fuerza-studio/fuerza-theme-api/wiki)
- **Email**: suporte@fuerzastudio.com

### **Comunidade**

- **Discord**: [Fuerza Studio Community](https://discord.gg/fuerza-studio)
- **Telegram**: [Grupo de Desenvolvedores](https://t.me/fuerza-dev)

---

<div align="center">

**Desenvolvido com ❤️ pela [Fuerza Studio](https://fuerzastudio.com)**

[![Fuerza Studio](https://img.shields.io/badge/Fuerza%20Studio-Professional%20Development-blue.svg)](https://fuerzastudio.com)

</div>
