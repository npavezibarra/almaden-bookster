<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once dirname( __FILE__ ) . '/../includes/editor-data-loader.php';

// At this point $book, $book_title, $pdf_settings are available.
$page_width = $pdf_settings['page_width'];
$page_height = $pdf_settings['page_height'];

// Total de páginas calculadas desde el Content Editor
$total_pages = get_post_meta( $book_id, '_almaden_total_pages', true );
$total_pages = $total_pages ? intval( $total_pages ) : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookCover Editor - <?php echo esc_attr( $book_title ); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
            color: #111;
        }
        .serif {
            font-family: 'Playfair Display', serif;
        }
        
        /* Contenedor del workspace con scroll y centrado */
        #workspace-container {
            height: calc(100vh - 64px); /* 64px es el alto del navbar */
            overflow: auto;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #e5e7eb;
        }

        /* Envoltura escalable para el zoom */
        #cover-scaler {
            transform-origin: center center;
            transition: transform 0.2s ease;
            padding: 40px; /* Margen de seguridad visual */
        }
        
        /* El "spread" de la portada (Contraportada + Lomo + Portada) */
        #cover-spread {
            display: flex;
            flex-direction: row;
            background: white;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        
        /* Partes de la portada */
        .cover-part {
            position: relative;
            background-color: white;
            border: 1px dashed #d1d5db; /* Para visualizar límites provisionalmente */
        }
        
        #spine {
            background-color: #f9fafb;
            border-left: 1px solid #e5e7eb;
            border-right: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
    
    <script>
        // Exportar a JS
        const coverData = {
            pageWidthCm: <?php echo floatval($page_width); ?>,
            pageHeightCm: <?php echo floatval($page_height); ?>
        };
    </script>
</head>
<body class="h-screen w-screen overflow-hidden flex flex-col">

    <!-- Navbar -->
    <nav class="h-16 bg-white border-b border-gray-200 px-4 flex items-center justify-between shrink-0 z-10 relative shadow-sm">
        <div class="flex items-center gap-4">
            <a href="<?php echo esc_url( home_url( '/almaden-booklist/' ) ); ?>" class="text-gray-500 hover:text-black transition flex items-center justify-center w-8 h-8 rounded-full hover:bg-gray-100" title="Volver a Mis Libros">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="font-bold text-lg leading-none serif truncate max-w-xs" title="<?php echo esc_attr( $book_title ); ?>"><?php echo esc_html( $book_title ); ?></h1>
                <span class="text-xs text-gray-400 uppercase tracking-wider font-semibold">Editor de Portada</span>
            </div>
        </div>
        
        <div class="flex items-center gap-6">
            <!-- Paper Type Selector -->
            <div class="flex items-center gap-2">
                <label for="paper-type" class="text-xs font-medium text-gray-500 uppercase tracking-wider">Papel Interior:</label>
                <select id="paper-type" class="text-sm border border-gray-300 rounded-md px-2 py-1.5 focus:ring-black focus:border-black cursor-pointer">
                    <option value="0.06">Crema 90g (0.06mm/pág)</option>
                    <option value="0.05">Blanco 80g (0.05mm/pág)</option>
                    <option value="0.045">Fino 70g (0.045mm/pág)</option>
                </select>
            </div>
            
            <!-- Page Count -->
            <div class="flex items-center gap-2">
                <label for="page-count" class="text-xs font-medium text-gray-500 uppercase tracking-wider">Páginas:</label>
                <input type="number" id="page-count" value="<?php echo esc_attr( $total_pages > 0 ? $total_pages : 0 ); ?>" class="w-20 text-sm border border-gray-300 rounded-md px-2 py-1.5 text-center bg-gray-100 cursor-not-allowed text-gray-500" readonly title="Este valor se calcula automáticamente desde el Content Editor.">
            </div>

            <div class="h-6 w-px bg-gray-300"></div>
            
            <!-- Zoom Controls -->
            <div class="flex items-center gap-1 bg-gray-100 rounded-md p-1">
                <button id="zoom-out" class="w-8 h-8 flex items-center justify-center rounded text-gray-600 hover:bg-white hover:shadow-sm transition" title="Reducir (Ctrl/Cmd + Scroll)">
                    <i class="fa-solid fa-minus text-xs"></i>
                </button>
                <span id="zoom-level" class="text-xs font-medium w-12 text-center">100%</span>
                <button id="zoom-in" class="w-8 h-8 flex items-center justify-center rounded text-gray-600 hover:bg-white hover:shadow-sm transition" title="Aumentar (Ctrl/Cmd + Scroll)">
                    <i class="fa-solid fa-plus text-xs"></i>
                </button>
            </div>
            
            <button class="bg-black text-white px-5 py-2 rounded-md text-sm font-medium hover:bg-gray-800 transition shadow-sm">
                Guardar Portada
            </button>
        </div>
    </nav>

    <!-- Workspace -->
    <main id="workspace-container">
        <div id="cover-scaler">
            <div id="cover-spread">
                <!-- Back Cover -->
                <div id="back-cover" class="cover-part flex items-center justify-center text-gray-300 overflow-hidden">
                    <span class="text-xl font-semibold uppercase tracking-widest text-gray-200">Contraportada</span>
                </div>
                
                <!-- Spine -->
                <div id="spine" class="cover-part overflow-hidden" title="Lomo">
                    <div class="spine-text text-xs text-gray-400 font-semibold uppercase tracking-wider rotate-90 whitespace-nowrap">Lomo</div>
                </div>
                
                <!-- Front Cover -->
                <div id="front-cover" class="cover-part flex items-center justify-center text-gray-300 overflow-hidden">
                    <span class="text-xl font-semibold uppercase tracking-widest text-gray-200">Portada</span>
                </div>
            </div>
        </div>
    </main>

    <!-- App Logic -->
    <script src="<?php echo esc_url( plugins_url( 'assets/js/cover-editor.js', dirname(__FILE__) ) ); ?>"></script>
</body>
</html>
