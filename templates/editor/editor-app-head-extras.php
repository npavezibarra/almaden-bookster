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
    <!-- Google Fonts: Inter para la interfaz, Merriweather para el estilo de libro PDF -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link id="google-fonts-stylesheet" href="<?php echo esc_url( $google_fonts_url ); ?>" rel="stylesheet">
    <!-- Urbanist Font for UI -->
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&amp;display=swap" rel="stylesheet">
    <!-- Font Awesome Icons para UI -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo esc_url( plugins_url( '../../assets/css/editor-style.css?v=' . time(), __FILE__ ) ); ?>">
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
    <style id="almaden-editor-overrides">
        html {
            margin-top: 0 !important;
        }
        main {
            background-color: #f9fafb;
        }
    </style>
