    <!-- Tailwind CSS -->
    <script>
        window.tailwind = window.tailwind || {};
        window.tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Urbanist"', 'sans-serif']
                    }
                }
            }
        };
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Librería para exportar PDF directamente -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <!-- PDF.js para renderizar el PDF Typst en vistas de una página y spread -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <!-- Markdown-it para el preview de Ebook dentro del editor -->
    <script src="https://cdn.jsdelivr.net/npm/markdown-it@13.0.1/dist/markdown-it.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/markdown-it-footnote@3.0.3/dist/markdown-it-footnote.min.js"></script>
    <?php if ( function_exists( 'almaden_bookster_get_bundled_fonts_stylesheet_url' ) ) : ?>
    <link rel="stylesheet" href="<?php echo esc_url( almaden_bookster_get_bundled_fonts_stylesheet_url() ); ?>">
    <?php endif; ?>
    <!-- Estilos dinámicos de maquetación del PDF -->
    <style id="dynamic-pdf-settings"></style>
    <style>
        html {
            margin-top: 0 !important;
        }
        .is-dragging-chapter .group * { pointer-events: none; }
    </style>
    <script>
        var ajaxurl = "<?php echo admin_url( 'admin-ajax.php' ); ?>";
    </script>
    <?php wp_head(); ?>
    <link rel="stylesheet" href="<?php echo esc_url( plugins_url( '../../assets/css/editor-style.css?v=' . time(), __FILE__ ) ); ?>">
    <style id="almaden-editor-overrides">
        html {
            margin-top: 0 !important;
        }
        main {
            background-color: #f9fafb;
        }
    </style>
