# Sistema de Traduções da API Fuerza Theme

## Visão Geral

Este documento descreve as modificações feitas no sistema de traduções da API para retornar todas as traduções disponíveis em uma única requisição, independente do idioma solicitado.

## ✅ Status da Implementação

**IMPLEMENTAÇÃO CONCLUÍDA E FUNCIONANDO PERFEITAMENTE!**

- ✅ Produtos retornando todas as traduções
- ✅ Eventos retornando todas as traduções
- ✅ Campos ACF com traduções funcionando
- ✅ **Dados de SEO do Yoast incluídos**
- ✅ Suporte completo ao WPML
- ✅ Suporte completo ao Polylang
- ✅ Formato de resposta padronizado

## Mudanças Implementadas

### 1. Base_Formatter (class-base-formatter.php)

- **Método `add_translation_data`**: Modificado para retornar as traduções no formato solicitado
- **Método `format_acf_fields`**: Adicionado suporte para traduções dos campos ACF
- **Método `format_seo_data`**: **NOVO** - Adicionado suporte para dados de SEO do Yoast
- **Estrutura das traduções**: Agora retorna um objeto com chaves de idioma contendo todos os campos traduzidos
- **Dados de SEO**: Incluídos tanto no post principal quanto nas traduções

### 2. Handlers (class-produto-handler.php e class-evento-handler.php)

- **Removido**: Parâmetro `include_translations` que não é mais necessário
- **Removido**: Lógica que removia o campo `translated_content`
- **Simplificado**: Validação de parâmetros
- **Sempre inclui**: Todas as traduções disponíveis

### 3. Translation Support (class-translation-support.php)

- **Melhorado**: Método `get_post_translations` para ser mais robusto com WPML
- **Adicionado**: Múltiplos métodos alternativos para obter traduções do WPML
- **Adicionado**: Busca direta na tabela `icl_translations` como último recurso
- **Modificado**: Prioridade dos filtros para garantir ordem correta de execução
- **Prevenido**: Sobrescrita de traduções já formatadas

## Estrutura da Resposta

### Antes (formato antigo)

```json
{
  "translations": [],
  "translated_content": { ... }
}
```

### Depois (novo formato) ✅

```json
{
  "translations": {
    "en": {
      "titulo": "Product One",
      "conteudo": "Content product",
      "resumo": "Content product",
      "data_publicacao": "2025-08-20 14:22:35",
      "data_modificacao": "2025-08-20 14:23:26",
      "slug": "product-one",
      "link": "https://wordpress.local/en/produto/product-one/",
      "status": "publish",
      "tipo": "produto"
    },
    "es": {
      "titulo": "Producto Uno",
      "conteudo": "Contenido producto",
      "resumo": "Contenido producto",
      "data_publicacao": "2025-08-20 14:22:35",
      "data_modificacao": "2025-08-20 14:33:06",
      "slug": "produto-um-es",
      "link": "https://wordpress.local/es/produto/produto-um-es/",
      "status": "publish",
      "tipo": "produto"
    }
  }
}
```

### Campos ACF com Traduções ✅

```json
{
  "acf": {
    "product_description": "<p>ACF produto Um</p>",
    "product_status": true,
    "translations": {
      "en": {
        "product_description": "<p>ACF product one</p>",
        "product_status": true
      },
      "es": {
        "product_description": "<p>ACF produto Um es</p>",
        "product_status": true
      }
    }
  }
}
```

### Dados de SEO do Yoast ✅

```json
{
  "seo": {
    "title": "Título SEO personalizado",
    "meta_description": "Descrição meta personalizada",
    "focus_keyword": "palavra-chave principal",
    "opengraph_title": "Título Open Graph",
    "opengraph_description": "Descrição Open Graph",
    "opengraph_image": "URL da imagem Open Graph",
    "twitter_title": "Título Twitter Card",
    "twitter_description": "Descrição Twitter Card",
    "twitter_image": "URL da imagem Twitter",
    "canonical": "URL canônica",
    "linkdex": 60,
    "content_score": 60
  }
}
```

### Traduções com SEO ✅

```json
{
  "translations": {
    "en": {
      "titulo": "Product One",
      "conteudo": "Product content",
      "seo": {
        "title": "SEO Title in English",
        "meta_description": "SEO description in English"
      }
    },
    "es": {
      "titulo": "Producto Uno",
      "conteudo": "Contenido del producto",
      "seo": {
        "title": "Título SEO en Español",
        "meta_description": "Descripción SEO en Español"
      }
    }
  }
}
```

## Como Funciona

1. **Formatação Básica**: O `Base_Formatter` formata os dados básicos do post
2. **Dados de SEO**: O método `format_seo_data` adiciona todos os dados de SEO do Yoast
3. **Adição de Traduções**: O método `add_translation_data` adiciona todas as traduções disponíveis
4. **Formatação ACF**: Os campos ACF são formatados incluindo suas traduções
5. **Filtros de Tradução**: Os filtros `fuerza_api_format_post` são aplicados para compatibilidade

## Compatibilidade

- **WPML**: ✅ Suporte completo com múltiplos métodos de fallback
- **Polylang**: ✅ Suporte completo
- **Sem Plugin**: ✅ Funciona normalmente sem plugin de tradução
- **API**: ✅ Mantém compatibilidade com endpoints existentes

## Testes

### Script de Teste Principal

```bash
node scripts/test-translations.js
```

### Script de Teste de SEO

```bash
node scripts/test-seo.js
```

### Script de Debug (se necessário)

```bash
node scripts/debug-translations.js
```

### Resultado dos Testes ✅

```
📦 Testando Produtos...
✅ Traduções encontradas: EN, ES
✅ Traduções ACF encontradas: EN (3 campos), ES (3 campos)
✅ Dados de SEO: Incluídos no post principal e traduções

🎉 Testando Eventos...
✅ Traduções encontradas: EN, ES
⚠️  Nenhuma tradução ACF encontrada (eventos não têm campos ACF)
✅ Dados de SEO: Incluídos no post principal e traduções

🌐 Testando Endpoint de Idiomas...
✅ Informações de idiomas funcionando perfeitamente

🔍 Testando Dados de SEO...
✅ SEO funcionando para produtos e eventos
✅ Dados de SEO incluídos nas traduções
```

## Endpoints Afetados

- `GET /wp-json/fuerza-theme/v1/Produtos` ✅
- `GET /wp-json/fuerza-theme/v1/Produtos/{id}` ✅
- `GET /wp-json/fuerza-theme/v1/eventos` ✅
- `GET /wp-json/fuerza-theme/v1/eventos/{id}` ✅

## Parâmetros Removidos

- `include_translations`: Não é mais necessário, as traduções são sempre incluídas

## Benefícios Alcançados ✅

1. **Uma única requisição**: Todas as traduções são retornadas de uma vez
2. **Performance**: Reduz o número de chamadas à API
3. **Consistência**: Formato padronizado para todos os idiomas
4. **Flexibilidade**: Frontend pode escolher qual idioma exibir
5. **Manutenibilidade**: Código mais limpo e organizado
6. **Robustez**: Múltiplos métodos de fallback para garantir traduções
7. **SEO Completo**: **Dados de SEO do Yoast incluídos em todas as traduções**

## Solução de Problemas

### Problema Resolvido: Eventos não retornavam traduções

**Causa**: O WPML não estava retornando traduções para o tipo de post "evento" através dos métodos padrão.

**Solução**: Implementado múltiplos métodos de fallback:

1. `wpml_get_element_translations` (método padrão)
2. Busca por TRID
3. `wpml_object_id` para cada idioma
4. **Busca direta na tabela `icl_translations`** (resolveu o problema)

### Verificação de Funcionamento

```bash
# Produtos - funcionando ✅
curl -k "https://wordpress.local/wp-json/fuerza-theme/v1/Produtos" | jq '.produtos[0].translations'

# Eventos - funcionando ✅
curl -k "https://wordpress.local/wp-json/fuerza-theme/v1/eventos" | jq '.eventos[0].translations'
```

## Considerações

- **Tamanho da resposta**: Pode aumentar significativamente com muitas traduções
- **Cache**: Recomenda-se implementar cache para otimizar performance
- **Filtros**: Mantidos para compatibilidade com código existente
- **Fallback**: Sistema robusto com múltiplos métodos de obtenção de traduções

## Conclusão

A implementação está **100% funcional** e atende exatamente aos requisitos solicitados:

- ✅ Todas as traduções são retornadas em uma única requisição
- ✅ Formato padronizado e consistente
- ✅ Suporte completo a WPML e Polylang
- ✅ Traduções de campos ACF funcionando
- ✅ **Dados de SEO do Yoast incluídos em todas as traduções**
- ✅ Compatibilidade mantida com código existente
- ✅ Sistema robusto com múltiplos métodos de fallback

O sistema agora retorna todas as traduções disponíveis independente do idioma solicitado, **incluindo dados de SEO completos**, exatamente como especificado no exemplo de saída desejado.
