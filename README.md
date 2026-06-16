# Almaden Bookster - Plugin Architecture Guidelines

## Cómo crear páginas públicas integradas con el Tema Activo (Ej. Bookshelf)

Al crear nuevas páginas front-end públicas (como el `Bookshelf`) que deben verse integradas dentro del sitio del usuario (manteniendo el encabezado, menú, pie de página y los contenedores del tema activo, incluyendo los de FSE/Block Themes como Twenty Twenty-Four), **NUNCA** se debe interceptar la página completa con `template_redirect` y reemplazarla por una vista estática independiente.

Hacerlo rompe los contenedores estándar del tema y causa que el contenido se desborde o se vea "horrible".

### La técnica correcta (Filter `the_content`):

**1. Registrar la página físicamente en la BD (si no existe)**
Enganchar a `init` y usar `wp_insert_post` para asegurar que el slug existe en la base de datos de WordPress como una página real vacía.

```php
function almaden_bookster_create_page() {
    $page = get_page_by_path( 'mi-pagina-publica' );
    if ( ! $page ) {
        wp_insert_post( array(
            'post_title'   => 'Mi Página',
            'post_name'    => 'mi-pagina-publica',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_content' => '<!-- Contenido dinámico -->'
        ) );
    }
}
add_action( 'init', 'almaden_bookster_create_page' );
```

**2. Inyectar el contenido usando el filtro `the_content`**
En lugar de secuestrar la página, enganchamos nuestro template *dentro* del flujo normal de contenido del tema. Así, el tema envolverá nuestro código con las etiquetas de layout correctas (`<main>`, contenedores de bloque, wrappers, etc).

```php
function almaden_render_mi_pagina( $content ) {
    if ( is_page( 'mi-pagina-publica' ) && in_the_loop() && is_main_query() ) {
        ob_start();
        
        // Incluir la vista PHP que contiene el HTML de nuestra app/funcionalidad
        require_once dirname( __FILE__ ) . '/../templates/mi-pagina-app.php';
        
        return ob_get_clean();
    }
    return $content;
}
add_filter( 'the_content', 'almaden_render_mi_pagina' );
```

**3. El archivo del Template (`templates/mi-pagina-app.php`)**
* **NO** debe incluir `get_header()`, `get_footer()`, ni tags `<html>` o `<body>`. El tema ya se encargó de eso.
* **SÍ** debe incluir todo el HTML del widget/app empaquetado en un div contenedor único (ej. `<div class="almaden-mi-app-wrapper">`).
* **SÍ** debe utilizar estilos CSS "scoped" (con nombres de clase únicos prefijados con `almaden-`) para evitar conflictos bidireccionales con el tema del usuario.
* **NO** debe cargar frameworks masivos como Tailwind CDN genérico en el frontend público a menos que esté estrictamente segmentado (preflight desactivado/prefixado), ya que reseteará los estilos globales del tema del usuario. Es preferible CSS nativo y semántico.

### Excepciones: Aplicaciones de Escritorio / Dashboards Internos
Para páginas que actúan como aplicaciones web *standalone* (como el panel de edición `Almaden Booklist`), **SÍ** se usa la técnica de `template_redirect` para ocultar todo el tema de WordPress (`exit;` después de cargar el template) y renderizar un entorno HTML limpio desde cero con `cdn.tailwindcss.com`.
