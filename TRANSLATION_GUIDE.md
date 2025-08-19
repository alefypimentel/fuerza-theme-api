# 🌍 Guia de Suporte a Tradução - Fuerza Theme API

Este tema oferece suporte automático aos principais plugins de tradução do WordPress: **WPML** e **Polylang**.

## 🚀 Configuração Automática

O suporte a tradução é **ativado automaticamente** quando você instala um dos plugins compatíveis:

### WPML

- Instale o plugin WPML
- Todos os CPTs criados pelo tema serão automaticamente configurados como traduzíveis
- As strings do tema serão registradas para tradução

### Polylang

- Instale o plugin Polylang
- Os CPTs serão automaticamente detectados
- Configure os idiomas no painel do Polylang

## 📡 API Multilíngue

### Parâmetros da API

Todos os endpoints agora suportam o parâmetro `lang`:

```bash
# Buscar eventos em português
GET /wp-json/fuerza-theme/v1/eventos?lang=pt

# Buscar eventos em inglês
GET /wp-json/fuerza-theme/v1/eventos?lang=en

# Buscar eventos em todos os idiomas
GET /wp-json/fuerza-theme/v1/eventos?lang=all

# Buscar com conteúdo completo das traduções (padrão)
GET /wp-json/fuerza-theme/v1/eventos?include_translations=full

# Buscar apenas com links das traduções
GET /wp-json/fuerza-theme/v1/eventos?include_translations=links
```

### Novos Endpoints de Idioma

```bash
# Listar idiomas disponíveis
GET /wp-json/fuerza-theme/v1/languages

# Alternar idioma (para sessão)
POST /wp-json/fuerza-theme/v1/language/pt
```

## 📋 Estrutura de Resposta

### Posts com Informações de Tradução

#### Com Conteúdo Completo das Traduções (`include_translations=full`)

```json
{
  "id": 123,
  "titulo": "Meu Evento",
  "conteudo": "Conteúdo do evento...",
  "language": "pt-br",
  "translations": {
    "en": {
      "id": 124,
      "language": "en",
      "url": "https://site.com/en/my-event"
    },
    "es": {
      "id": 125,
      "language": "es",
      "url": "https://site.com/es/mi-evento"
    }
  },
  "is_default_language": true,
  "translated_content": {
    "en": {
      "id": 124,
      "language": "en",
      "titulo": "My Event",
      "conteudo": "Event content...",
      "resumo": "Event summary...",
      "slug": "my-event",
      "url": "https://site.com/en/my-event",
      "api_url": "https://site.com/wp-json/fuerza-theme/v1/evento/124",
      "data_publicacao": "2025-08-18 17:27:06",
      "data_modificacao": "2025-08-18 17:27:06"
    },
    "es": {
      "id": 125,
      "language": "es",
      "titulo": "Mi Evento",
      "conteudo": "Contenido del evento...",
      "resumo": "Resumen del evento...",
      "slug": "mi-evento",
      "url": "https://site.com/es/mi-evento",
      "api_url": "https://site.com/wp-json/fuerza-theme/v1/evento/125",
      "data_publicacao": "2025-08-18 17:27:06",
      "data_modificacao": "2025-08-18 17:27:06"
    }
  },
  "translated_versions": {
    "en": {
      "id": 124,
      "url": "https://site.com/en/my-event",
      "language": "en",
      "api_url": "https://site.com/wp-json/fuerza-theme/v1/evento/124"
    }
  }
}
```

#### Apenas com Links (`include_translations=links`)

```json
{
  "id": 123,
  "titulo": "Meu Evento",
  "conteudo": "Conteúdo do evento...",
  "language": "pt-br",
  "translations": {
    "en": {
      "id": 124,
      "language": "en",
      "url": "https://site.com/en/my-event"
    }
  },
  "translated_versions": {
    "en": {
      "id": 124,
      "url": "https://site.com/en/my-event",
      "language": "en",
      "api_url": "https://site.com/wp-json/fuerza-theme/v1/evento/124"
    }
  }
}
```

### Lista com Informações de Idioma

```json
{
  "eventos": [...],
  "pagination": {...},
  "language_info": {
    "current_language": "pt",
    "available_languages": ["pt", "en", "es"],
    "requested_language": "pt"
  }
}
```

### Endpoint de Idiomas

```json
{
  "languages": {
    "pt": {
      "code": "pt",
      "native_name": "Português",
      "url": "https://site.com"
    },
    "en": {
      "code": "en",
      "native_name": "English",
      "url": "https://site.com/en"
    }
  },
  "default": "pt",
  "current": "pt",
  "plugin": "wpml"
}
```

## ⚙️ Configuração Manual

### WPML

Se precisar configurar manualmente:

```php
// No arquivo de configuração do WPML
add_action('init', function() {
    // Registrar CPT para tradução
    do_action('wpml_register_single_string', 'fuerza-theme', 'evento_label', 'Evento');

    // Configurar como traduzível
    global $sitepress_settings;
    $sitepress_settings['custom_posts_sync_option']['evento'] = 1;
});
```

### Polylang

Para Polylang, adicione no functions.php:

```php
// Registrar strings para tradução
add_action('init', function() {
    if (function_exists('pll_register_string')) {
        pll_register_string('evento_label', 'Evento', 'Fuerza Theme');
    }
});
```

## 🔧 Personalização

### Filtros Disponíveis

```php
// Filtrar argumentos de query por idioma
add_filter('fuerza_api_get_posts_args', function($args, $request) {
    // Sua lógica personalizada
    return $args;
}, 10, 2);

// Adicionar dados personalizados de tradução
add_filter('fuerza_api_format_post', function($post_data, $post) {
    // Adicionar informações customizadas
    return $post_data;
}, 10, 2);
```

### Hooks Específicos

```php
// Detectar quando plugin de tradução é identificado
add_action('fuerza_translation_plugin_detected', function($plugin) {
    if ($plugin === 'wpml') {
        // Configurações específicas para WPML
    } elseif ($plugin === 'polylang') {
        // Configurações específicas para Polylang
    }
});
```

## 📝 Exemplos de Uso

### Frontend JavaScript

```javascript
// Buscar eventos no idioma atual com conteúdo completo das traduções
const fetchEvents = async (language = "pt", includeTranslations = "full") => {
  const response = await fetch(
    `/wp-json/fuerza-theme/v1/eventos?lang=${language}&include_translations=${includeTranslations}`
  );
  const data = await response.json();
  return data;
};

// Buscar apenas com links (mais rápido)
const fetchEventsLight = async (language = "pt") => {
  return fetchEvents(language, "links");
};

// Alternar idioma
const switchLanguage = async (lang) => {
  const response = await fetch(`/wp-json/fuerza-theme/v1/language/${lang}`, {
    method: "POST",
  });
  return response.json();
};
```

### React/Vue/Angular

```javascript
// Hook para idiomas (React)
const useLanguages = () => {
  const [languages, setLanguages] = useState([]);

  useEffect(() => {
    fetch("/wp-json/fuerza-theme/v1/languages")
      .then((res) => res.json())
      .then((data) => setLanguages(data.languages));
  }, []);

  return languages;
};
```

## 🐛 Solução de Problemas

### Problema: CPTs não aparecem para tradução

**Solução:**

1. Verifique se o plugin de tradução está ativo
2. Vá em WP Admin > Fuerza API > Dashboard
3. Verifique se aparece "Plugin de tradução detectado"

### Problema: API retorna sempre mesmo idioma

**Solução:**

1. Confirme que está passando o parâmetro `lang`
2. Verifique se o idioma solicitado existe no site
3. Para WPML, verifique se o CPT está configurado como traduzível

### Problema: Traduções não aparecem na API

**Solução:**

1. Crie traduções dos posts no painel admin
2. Verifique se as traduções estão publicadas
3. Confirme que os posts estão vinculados corretamente

## 📚 Recursos Adicionais

- [Documentação WPML](https://wpml.org/documentation/)
- [Documentação Polylang](https://polylang.pro/documentation/)
- [WordPress REST API](https://developer.wordpress.org/rest-api/)

## 🆘 Suporte

Para problemas específicos de tradução:

1. Verifique os logs do WordPress
2. Teste com plugins de tradução desativados
3. Confirme que os CPTs estão registrados corretamente

---

## 💡 Parâmetro include_translations

O novo parâmetro `include_translations` permite controlar o que é retornado:

- **`full` (padrão):** Retorna o conteúdo completo de todas as traduções
- **`links`:** Retorna apenas links para as traduções (mais rápido)

### Exemplos Práticos:

```bash
# Uma única requisição com todo o conteúdo traduzido
GET /wp-json/fuerza-theme/v1/produtos/123?include_translations=full

# Resposta otimizada apenas com links
GET /wp-json/fuerza-theme/v1/produtos?include_translations=links

# Combinando filtros
GET /wp-json/fuerza-theme/v1/eventos?lang=pt&include_translations=full&per_page=5
```

### Vantagens do `include_translations=full`:

- ✅ **Uma única requisição** retorna todo o conteúdo multilíngue
- ✅ **Reduz número de chamadas** da API
- ✅ **Ideal para aplicações** que precisam mostrar múltiplos idiomas
- ✅ **Economiza bandwidth** em aplicações complexas

### Quando usar `include_translations=links`:

- ⚡ **Performance otimizada** para listas grandes
- ⚡ **Menos dados transferidos** quando só precisa de links
- ⚡ **Ideal para navegação** entre idiomas

---

**Nota:** Este sistema funciona automaticamente com qualquer CPT criado através do sistema do tema. Não é necessária configuração adicional na maioria dos casos.
