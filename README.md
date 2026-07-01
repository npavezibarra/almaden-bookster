# Almaden Bookster — Plugin Architecture

Este plugin proporciona una suite completa para la creación, maquetación, visualización (eBook Reader) y exportación a PDF de libros directamente en WordPress.

---

## 📂 Estructura General del Plugin

El proyecto se rige por un principio de **Modularidad Extrema**, dividiendo la lógica en componentes específicos para garantizar la mantenibilidad y archivos con un límite estricto de **menos de 500 líneas de código**.

```
almaden-bookster/
├── admin/                 # Controladores del panel de administración (ej. Google Fonts)
├── assets/
│   ├── css/               # Hojas de estilo del editor, admin y vistas
│   └── js/                # Scripts de interactividad cliente (ver subcarpetas)
│       ├── admin/         # Controladores de administración y booklist
│       ├── editor/        # Lógica de la interfaz del editor (totalmente modularizada por componentes como pestañas, campos dinámicos y visor/regla)
│       ├── pdf/           # Motor de paginación virtual, algoritmos de layout de retícula de precisión y exportación PDF
│       ├── reader/        # Experiencia de lectura de eBooks
│       └── cover/         # Diseñador de portadas de libros
├── includes/              # Lógica de negocio de WordPress (AJAX, taxonomías, CPTs, exportaciones)
├── templates/             # Vistas y maquetación HTML de las aplicaciones
│   ├── admin/             # Plantilla del taller
│   ├── editor/            # Plantilla del editor de contenidos y modales de ajustes
│   ├── reader/            # Plantilla del visor de eBooks
│   ├── bookshelf/         # Plantilla del catálogo público
│   └── cover/             # Plantilla del editor de portadas
├── AGENT_GUIDELINES.md    # Directrices de desarrollo estrictas para Agentes AI
└── README.md              # Documentación principal de arquitectura
```

### 🔗 Índices de Navegación del Plugin
*   **Directrices del Agente**: [AGENT_GUIDELINES.md](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/AGENT_GUIDELINES.md)
*   **Lógica PHP de Backend**: [includes/](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/includes/)
*   **Manejadores AJAX de WordPress**: [includes/ajax/](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/includes/ajax/)
*   **Vistas HTML y Plantillas PHP**: [templates/](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/templates/)
    *   Taller: [templates/admin/](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/templates/admin/)
    *   Editor de Libros: [templates/editor/](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/templates/editor/)
    *   Lector eBook: [templates/reader/](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/templates/reader/)
    *   Librería Pública: [templates/bookshelf/](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/templates/bookshelf/)
    *   Editor de Portadas: [templates/cover/](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/templates/cover/)
*   **Scripts JavaScript (Assets)**: [assets/js/](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/)
    *   Taller & Admin: [assets/js/admin/](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/admin/)
    *   Editor de Libros: [assets/js/editor/](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/editor/)
    *   Lector eBook: [assets/js/reader/](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/reader/)
    *   Editor de Portadas: [assets/js/cover/](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/cover/)
    *   Motor de PDF (General): [assets/js/pdf/](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/pdf/)
    *   Motor de PDF (Core): [assets/js/pdf/core/](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/pdf/core/)

---

## 🛠️ Directrices para Crear Páginas Públicas Integradas con el Tema

Al crear nuevas páginas front-end públicas (como el `Bookshelf`) que deben verse integradas dentro del sitio del usuario (manteniendo el encabezado, menú, pie de página y los contenedores del tema activo, incluyendo los de FSE/Block Themes como Twenty Twenty-Four), **NUNCA** se debe interceptar la página completa con `template_redirect` y reemplazarla por una vista estática independiente.

Hacerlo rompe los contenedores estándar del tema y causa que el contenido se desborde o se rompa visualmente.

### La técnica correcta (Filtro `the_content`):

**1. Registrar la página físicamente en la base de datos**
Enganchar a `init` y usar `wp_insert_post` para asegurar que la página existe:

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
En lugar de secuestrar la página con `template_redirect`, enganchamos nuestro template *dentro* del flujo normal de contenido del tema para que este lo envuelva con las etiquetas de layout correctas del tema (`<main>`, contenedores de bloque, wrappers, etc.).

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
* **NO** debe incluir `get_header()`, `get_footer()`, ni tags `<html>` o `<body>`. El tema de WordPress ya se encarga de ello.
* **SÍ** debe incluir todo el HTML del widget/app empaquetado en un div contenedor único (ej. `<div class="almaden-mi-app-wrapper">`).
* **SÍ** debe utilizar estilos CSS "scoped" (con nombres de clase únicos prefijados con `almaden-`) para evitar conflictos bidireccionales con el tema del usuario.
* **NO** debe cargar frameworks masivos como Tailwind CDN genérico en el frontend público a menos que esté estrictamente segmentado (preflight desactivado o prefijado), ya que reseteará los estilos globales del tema del usuario.

### Excepciones: Aplicaciones de Escritorio / Dashboards Internos
Para páginas que actúan como aplicaciones web completas (como el editor visual `Almaden Booklist` o `Almaden Book Editor`), **SÍ** se usa la técnica de `template_redirect` para omitir por completo el tema de WordPress (`exit;` después de cargar el template) y renderizar un entorno HTML limpio desde cero con Tailwind.
