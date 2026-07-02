<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once dirname( __FILE__ ) . '/../../includes/helpers/editor-data-loader.php';

// At this point $book, $book_title, $pdf_settings are available.
$page_width = $pdf_settings['page_width'];
$page_height = $pdf_settings['page_height'];

// Total de páginas calculadas desde el Content Editor
$total_pages = get_post_meta( $book_id, '_almaden_total_pages', true );
$total_pages = ( $total_pages && intval( $total_pages ) > 0 ) ? intval( $total_pages ) : 100;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookCover Editor - <?php echo esc_attr( $book_title ); ?></title>
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <!-- FIX: usar $google_fonts_url en lugar de $fonts_url para que las fuentes web carguen -->
    <link href="<?php echo esc_url($google_fonts_url); ?>" rel="stylesheet">
    <!-- Urbanist Font for UI -->
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&amp;display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: "Urbanist", sans-serif;
            background-color: #0f172a;
            color: white;
        }
        .serif {
            font-family: inherit;
        }
        #workspace-container {
            overflow: auto;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #e5e7eb;
            position: relative;
        }
        #cover-scaler {
            position: relative;
            overflow: visible;
            transform-origin: center center;
            transition: transform 0.2s ease;
            padding: 40px;
        }
        #cover-stage {
            position: relative;
            display: inline-block;
        }
        #cover-spread {
            position: relative;
            display: flex;
            flex-direction: row;
            background: white;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
        #ruler-overlay {
            position: absolute;
            inset: 0;
            pointer-events: none;
            z-index: 35;
            display: none;
        }
        #ruler-corner {
            position: absolute;
            top: 0;
            left: 0;
            width: 24px;
            height: 24px;
            background: linear-gradient(135deg, #d1d5db 0%, #f8fafc 100%);
            border-right: 1px solid #cbd5e1;
            border-bottom: 1px solid #cbd5e1;
            box-sizing: border-box;
        }
        #ruler-horizontal {
            position: absolute;
            top: 0;
            left: 24px;
            right: 0;
            height: 24px;
            border-bottom: 1px solid #cbd5e1;
            background-color: rgba(248, 250, 252, 0.96);
            box-sizing: border-box;
            overflow: hidden;
        }
        #ruler-vertical {
            position: absolute;
            top: 24px;
            left: 0;
            bottom: 0;
            width: 24px;
            border-right: 1px solid #cbd5e1;
            background-color: rgba(248, 250, 252, 0.96);
            box-sizing: border-box;
            overflow: hidden;
        }
        .ruler-tick {
            position: absolute;
            background: rgba(15, 23, 42, 0.45);
        }
        .ruler-tick--minor {
            opacity: 0.45;
        }
        .ruler-tick--major {
            opacity: 0.8;
        }
        .ruler-label {
            position: absolute;
            color: #334155;
            font-size: 10px;
            line-height: 1;
            font-weight: 600;
            letter-spacing: 0.02em;
            user-select: none;
            pointer-events: none;
        }
        .ruler-label--horizontal {
            top: 4px;
            transform: translateX(-50%);
        }
        .ruler-label--vertical {
            left: 4px;
            transform: translateY(-50%);
        }
        .cover-part {
            position: relative;
            background-color: white;
            border: 1px dashed #d1d5db;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            flex-shrink: 0;
            z-index: 1;
        }
        #spine {
            background-color: #f9fafb;
            border-left: 1px solid #e5e7eb;
            border-right: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .cover-media-image {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }
        .cover-media-image--cover {
            object-fit: cover;
        }
        .cover-media-image--contain {
            object-fit: contain;
        }
    </style>
    
    <script>
        const coverData = {
            bookId: <?php echo intval($book_id); ?>,
            nonce: "<?php echo esc_js($cover_nonce); ?>",
            exportNonce: "<?php echo esc_js($cover_export_nonce); ?>",
            ajaxUrl: "<?php echo esc_url(admin_url('admin-ajax.php')); ?>",
            exportUrl: "<?php echo esc_url(admin_url('admin-post.php')); ?>",
            pageWidthCm: <?php echo floatval($page_width); ?>,
            pageHeightCm: <?php echo floatval($page_height); ?>,
            settings: <?php echo json_encode($cover_settings); ?>,
            installedFonts: <?php echo json_encode($installed_fonts); ?>
        };
    </script>
    <?php wp_head(); ?>
</head>
<body class="h-screen w-screen overflow-hidden flex flex-col">

    <!-- Navbar -->
    <?php include dirname( __FILE__ ) . '/cover-navbar.php'; ?>

    <!-- Main Content Area -->
    <div class="flex flex-1 overflow-hidden">
        <!-- Sidebar Izquierdo (Imágenes, Solapas, Propiedades) -->
        <?php include dirname( __FILE__ ) . '/cover-sidebar-left.php'; ?>

        <!-- Workspace (Lienzo interactivo) -->
        <?php include dirname( __FILE__ ) . '/cover-workspace.php'; ?>

        <!-- Sidebar Derecho (Listado de Capas) -->
        <?php include dirname( __FILE__ ) . '/cover-sidebar-right.php'; ?>
    </div>

    <!-- Scripts Modulares -->
    <script src="<?php echo esc_url( plugin_dir_url( dirname( dirname(__FILE__) ) ) . 'assets/js/cover/cover-state.js?v=' . time() ); ?>"></script>
    <script src="<?php echo esc_url( plugin_dir_url( dirname( dirname(__FILE__) ) ) . 'assets/js/cover/cover-utils.js?v=' . time() ); ?>"></script>
    <script src="<?php echo esc_url( plugin_dir_url( dirname( dirname(__FILE__) ) ) . 'assets/js/cover/cover-dimensions.js?v=' . time() ); ?>"></script>
    <script src="<?php echo esc_url( plugin_dir_url( dirname( dirname(__FILE__) ) ) . 'assets/js/cover/cover-media.js?v=' . time() ); ?>"></script>
    <script src="<?php echo esc_url( plugin_dir_url( dirname( dirname(__FILE__) ) ) . 'assets/js/cover/cover-layers.js?v=' . time() ); ?>"></script>
    <script src="<?php echo esc_url( plugin_dir_url( dirname( dirname(__FILE__) ) ) . 'assets/js/cover/cover-layers-canvas.js?v=' . time() ); ?>"></script>
    <script src="<?php echo esc_url( plugin_dir_url( dirname( dirname(__FILE__) ) ) . 'assets/js/cover/cover-layers-panel.js?v=' . time() ); ?>"></script>
    <script src="<?php echo esc_url( plugin_dir_url( dirname( dirname(__FILE__) ) ) . 'assets/js/cover/cover-save.js?v=' . time() ); ?>"></script>
    <script src="<?php echo esc_url( plugin_dir_url( dirname( dirname(__FILE__) ) ) . 'assets/js/cover/cover-export.js?v=' . time() ); ?>"></script>
    <?php wp_footer(); ?>
</body>
</html>
