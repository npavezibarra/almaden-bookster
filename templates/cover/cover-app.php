<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once dirname( __FILE__ ) . '/../../includes/editor-data-loader.php';

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
    <link href="<?php echo esc_url($fonts_url); ?>" rel="stylesheet">
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
        
        /* Contenedor del workspace con scroll y centrado */
        #workspace-container {
            overflow: auto;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #e5e7eb;
            position: relative;
        }

        /* Envoltura escalable para el zoom */
        #cover-scaler {
            transform-origin: center center;
            transition: transform 0.2s ease;
            padding: 40px; /* Margen de seguridad visual */
        }
        
        /* El "spread" de la portada (Contraportada + Lomo + Portada) */
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
        
        /* Partes de la portada */
        .cover-part {
            position: relative;
            background-color: white;
            border: 1px dashed #d1d5db;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            flex-shrink: 0;
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
            bookId: <?php echo intval($book_id); ?>,
            nonce: "<?php echo esc_js($cover_nonce); ?>",
            ajaxUrl: "<?php echo esc_url(admin_url('admin-ajax.php')); ?>",
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
    <nav class="h-16 bg-white border-b border-gray-200 px-4 flex items-center justify-between shrink-0 z-10 relative shadow-sm">
        <div class="flex items-center gap-4">
            <a href="<?php echo esc_url( home_url( '/almaden-booklist/' ) ); ?>" class="text-gray-500 hover:text-black transition flex items-center justify-center w-8 h-8 rounded-full hover:bg-gray-100" title="Volver a Taller">
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
            <button id="export-pdf-btn" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-50 transition shadow-sm flex items-center gap-2">
                <i class="fa-solid fa-file-pdf text-red-500"></i> Descargar PDF
            </button>
            <button id="save-cover-btn" class="bg-black text-white px-5 py-2 rounded-md text-sm font-medium hover:bg-gray-800 transition shadow-sm flex items-center gap-2">
                <i class="fa-solid fa-floppy-disk"></i> Guardar Portada
            </button>
        </div>
    </nav>

    <!-- Main Content Area -->
    <div class="flex flex-1 overflow-hidden">
        
        <!-- Sidebar -->
        <aside class="w-72 bg-white border-r border-gray-200 flex flex-col shrink-0 shadow-sm z-10 overflow-y-auto">
            <div class="p-4 border-b border-gray-100 flex justify-between items-center cursor-pointer hover:bg-gray-50 transition select-none" id="toggle-images-section">
                <h2 class="font-bold text-sm uppercase tracking-wider text-gray-800">Imágenes</h2>
                <i class="fa-solid fa-chevron-down text-gray-400 transition-transform duration-200" id="images-section-icon"></i>
            </div>
            
            <div class="hidden flex-col gap-6 bg-white pb-4" id="images-section-content">
                <div class="px-4 pt-4">
                    <p class="text-xs text-gray-500">Asigna las imágenes para la cubierta del libro.</p>
                </div>
                
                <div class="px-4 flex flex-col gap-6">
                    <!-- Portada -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Portada (Front Cover)</label>
                    <button type="button" id="btn-front-cover" class="block w-full text-sm font-semibold bg-gray-100 text-gray-700 py-2 px-4 rounded-md border border-gray-300 hover:bg-gray-200 transition mb-2 text-center">
                        <i class="fa-solid fa-image mr-1"></i> Seleccionar Imagen
                    </button>
                    <input type="hidden" id="upload-front-cover" />
                    <button id="clear-front-cover" class="hidden text-xs text-red-600 hover:text-red-800 font-medium"><i class="fa-solid fa-trash mr-1"></i> Eliminar Portada</button>
                </div>
                
                <div class="h-px bg-gray-200"></div>

                <!-- Contraportada -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Contraportada (Back Cover)</label>
                    <button type="button" id="btn-back-cover" class="block w-full text-sm font-semibold bg-gray-100 text-gray-700 py-2 px-4 rounded-md border border-gray-300 hover:bg-gray-200 transition mb-2 text-center">
                        <i class="fa-solid fa-image mr-1"></i> Seleccionar Imagen
                    </button>
                    <input type="hidden" id="upload-back-cover" />
                    <button id="clear-back-cover" class="hidden text-xs text-red-600 hover:text-red-800 font-medium"><i class="fa-solid fa-trash mr-1"></i> Eliminar Contraportada</button>
                </div>

                <div class="h-px bg-gray-200"></div>

                <!-- Lomo -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Lomo (Spine)</label>
                    <div class="flex gap-2 mb-2">
                        <div class="flex-1">
                            <button type="button" id="btn-spine-image" class="block w-full text-xs font-semibold bg-gray-100 text-gray-700 py-2 px-2 rounded-md border border-gray-300 hover:bg-gray-200 transition text-center">
                                <i class="fa-solid fa-image mr-1"></i> Imagen
                            </button>
                            <input type="hidden" id="upload-spine-image" />
                        </div>
                        <div class="flex items-center gap-1 border border-gray-300 rounded-md px-2 bg-gray-50 hover:bg-gray-100 transition">
                            <i class="fa-solid fa-fill-drip text-gray-400 text-xs"></i>
                            <input type="color" id="spine-color-picker" value="#f9fafb" class="block w-6 h-6 p-0 border-0 rounded cursor-pointer bg-transparent" title="Color de Fondo" />
                        </div>
                    </div>
                    <button id="clear-spine" class="hidden text-xs text-red-600 hover:text-red-800 font-medium"><i class="fa-solid fa-trash mr-1"></i> Limpiar Lomo</button>
                </div>

                <div class="h-px bg-gray-200"></div>

                <!-- Spread Completo -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Spread Completo</label>
                    <p class="text-xs text-gray-500 mb-3">Reemplaza portada, contraportada y lomo con una sola imagen.</p>
                    <button type="button" id="btn-full-spread" class="block w-full text-sm font-semibold bg-gray-100 text-gray-700 py-2 px-4 rounded-md border border-gray-300 hover:bg-gray-200 transition mb-2 text-center">
                        <i class="fa-solid fa-image mr-1"></i> Seleccionar Imagen
                    </button>
                    <input type="hidden" id="upload-full-spread" />
                    <button id="clear-full-spread" class="hidden text-xs text-red-600 hover:text-red-800 font-medium"><i class="fa-solid fa-trash mr-1"></i> Eliminar Spread</button>
                </div>
            </div>
            </div>

            <!-- Section: Solapas -->
            <div class="p-4 border-b border-gray-100 flex justify-between items-center cursor-pointer hover:bg-gray-50 transition select-none border-t" id="toggle-flaps-section">
                <h2 class="font-bold text-sm uppercase tracking-wider text-gray-800">Solapas</h2>
                <i class="fa-solid fa-chevron-down text-gray-400 transition-transform duration-200 -rotate-90" id="flaps-section-icon"></i>
            </div>
            
            <div class="hidden flex-col gap-6 bg-white pb-4" id="flaps-section-content">
                <div class="px-4 pt-4">
                    <p class="text-xs text-gray-500">Agrega solapas a tu cubierta (ancho en mm).</p>
                </div>
                
                <div class="px-4 flex flex-col gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Solapa Portada (mm)</label>
                        <input type="number" id="front-flap-width" value="0" min="0" class="block w-full text-sm border border-gray-300 rounded-md px-3 py-2 focus:ring-black focus:border-black mb-2" />
                        <div class="flex gap-2 mb-2">
                            <div class="flex-1">
                                <button type="button" id="btn-front-flap-image" class="block w-full text-xs font-semibold bg-gray-100 text-gray-700 py-2 px-2 rounded-md border border-gray-300 hover:bg-gray-200 transition text-center">
                                    <i class="fa-solid fa-image mr-1"></i> Imagen
                                </button>
                                <input type="hidden" id="upload-front-flap-image" />
                            </div>
                            <div class="flex items-center gap-1 border border-gray-300 rounded-md px-2 bg-gray-50 hover:bg-gray-100 transition">
                                <i class="fa-solid fa-fill-drip text-gray-400 text-xs"></i>
                                <input type="color" id="front-flap-color-picker" value="#ffffff" class="block w-6 h-6 p-0 border-0 rounded cursor-pointer bg-transparent" title="Color de Fondo" />
                            </div>
                        </div>
                        <button id="clear-front-flap" class="hidden text-xs text-red-600 hover:text-red-800 font-medium"><i class="fa-solid fa-trash mr-1"></i> Limpiar Fondo</button>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Solapa Contraportada (mm)</label>
                        <input type="number" id="back-flap-width" value="0" min="0" class="block w-full text-sm border border-gray-300 rounded-md px-3 py-2 focus:ring-black focus:border-black mb-2" />
                        <div class="flex gap-2 mb-2">
                            <div class="flex-1">
                                <button type="button" id="btn-back-flap-image" class="block w-full text-xs font-semibold bg-gray-100 text-gray-700 py-2 px-2 rounded-md border border-gray-300 hover:bg-gray-200 transition text-center">
                                    <i class="fa-solid fa-image mr-1"></i> Imagen
                                </button>
                                <input type="hidden" id="upload-back-flap-image" />
                            </div>
                            <div class="flex items-center gap-1 border border-gray-300 rounded-md px-2 bg-gray-50 hover:bg-gray-100 transition">
                                <i class="fa-solid fa-fill-drip text-gray-400 text-xs"></i>
                                <input type="color" id="back-flap-color-picker" value="#ffffff" class="block w-6 h-6 p-0 border-0 rounded cursor-pointer bg-transparent" title="Color de Fondo" />
                            </div>
                        </div>
                        <button id="clear-back-flap" class="hidden text-xs text-red-600 hover:text-red-800 font-medium"><i class="fa-solid fa-trash mr-1"></i> Limpiar Fondo</button>
                    </div>
                </div>
            </div>

            <!-- Section: Textos y Capas -->
            <div class="p-4 border-b border-gray-100 flex justify-between items-center cursor-pointer hover:bg-gray-50 transition select-none border-t" id="toggle-texts-section">
                <h2 class="font-bold text-sm uppercase tracking-wider text-gray-800">Textos y Capas</h2>
                <i class="fa-solid fa-chevron-down text-gray-400 transition-transform duration-200 -rotate-90" id="texts-section-icon"></i>
            </div>

            <div class="hidden flex-col gap-4 bg-white pb-4" id="texts-section-content">
                <div class="px-4 pt-4">
                    <p class="text-[10px] text-gray-500 leading-tight">Haz clic en un texto de la portada o en el panel de capas (derecha) para editar sus propiedades.</p>
                </div>
                
                <!-- Text Properties Panel (Hidden by default) -->
                <div id="text-properties-panel" class="hidden px-4 flex-col gap-3 pt-2 border-t border-gray-100">
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-black dark:text-white">Propiedades</span>
                        <button id="delete-text-btn" class="text-red-500 hover:text-red-700" title="Eliminar Texto">
                            <i class="fa-solid fa-trash text-sm"></i>
                        </button>
                    </div>

                    <div class="text-only-prop">
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Contenido</label>
                        <textarea id="prop-text-content" rows="2" class="block w-full text-sm border border-gray-300 rounded-md px-2 py-1.5 focus:ring-black focus:border-black dark:border-white"></textarea>
                    </div>

                    <div class="text-only-prop">
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Tipografía</label>
                        <select id="prop-font-family" class="block w-full text-sm border border-gray-300 rounded-md px-2 py-1.5 focus:ring-black focus:border-black dark:border-white">
                            <!-- Populated via JS -->
                        </select>
                    </div>

                    <div class="flex gap-2">
                        <div class="flex-1">
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Rotación (°)</label>
                            <input type="number" id="prop-rotation" class="block w-full text-sm border border-gray-300 rounded-md px-2 py-1.5 focus:ring-black focus:border-black dark:border-white" />
                        </div>
                        <div class="flex-1 text-only-prop">
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Tamaño (px)</label>
                            <input type="number" id="prop-font-size" class="block w-full text-sm border border-gray-300 rounded-md px-2 py-1.5 focus:ring-black focus:border-black dark:border-white" />
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <div class="flex-1">
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Ancho (px)</label>
                            <input type="number" id="prop-width" placeholder="Auto" class="block w-full text-sm border border-gray-300 rounded-md px-2 py-1.5 focus:ring-black focus:border-black dark:border-white" />
                        </div>
                        <div class="flex-1">
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Alto (px)</label>
                            <input type="number" id="prop-height" placeholder="Auto" class="block w-full text-sm border border-gray-300 rounded-md px-2 py-1.5 focus:ring-black focus:border-black dark:border-white" />
                        </div>
                    </div>

                    <div class="text-only-prop">
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Color</label>
                        <div class="flex items-center gap-1">
                            <input type="color" id="prop-text-color" class="block w-8 h-8 p-0 border-0 rounded cursor-pointer" />
                            <input type="text" id="prop-text-color-hex" class="flex-1 text-xs border border-gray-300 rounded-md px-2 py-1.5 focus:ring-black focus:border-black dark:border-white" />
                        </div>
                    </div>

                    <div class="text-only-prop">
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Alineación</label>
                        <div class="flex bg-gray-100 rounded-md p-1 gap-1">
                            <button class="prop-align-btn flex-1 py-1 rounded hover:bg-white text-gray-600 transition" data-align="left" title="Izquierda">
                                <i class="fa-solid fa-align-left text-xs"></i>
                            </button>
                            <button class="prop-align-btn flex-1 py-1 rounded hover:bg-white text-gray-600 transition" data-align="center" title="Centro">
                                <i class="fa-solid fa-align-center text-xs"></i>
                            </button>
                            <button class="prop-align-btn flex-1 py-1 rounded hover:bg-white text-gray-600 transition" data-align="right" title="Derecha">
                                <i class="fa-solid fa-align-right text-xs"></i>
                            </button>
                            <button class="prop-align-btn flex-1 py-1 rounded hover:bg-white text-gray-600 transition" data-align="justify" title="Justificar">
                                <i class="fa-solid fa-align-justify text-xs"></i>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 mt-1 text-only-prop">
                        <input type="checkbox" id="prop-hyphens" class="rounded border-gray-300 text-black dark:text-white focus:ring-black cursor-pointer" />
                        <label for="prop-hyphens" class="text-xs font-semibold text-gray-700 cursor-pointer">Separación por sílabas (guiones)</label>
                    </div>

                    <!-- SHAPE PROPERTIES -->
                    <div class="shape-only-prop hidden">
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Tipo de Forma</label>
                        <select id="prop-shape-type" class="block w-full text-sm border border-gray-300 rounded-md px-2 py-1.5 focus:ring-black focus:border-black dark:border-white">
                            <option value="rectangle">Rectángulo</option>
                            <option value="circle">Círculo</option>
                        </select>
                    </div>

                    <div class="shape-only-prop hidden">
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Opacidad (%)</label>
                        <input type="range" id="prop-shape-opacity" min="0" max="100" value="100" class="w-full accent-indigo-600" />
                        <div class="text-xs text-center text-gray-500 mt-1" id="prop-shape-opacity-val">100%</div>
                    </div>

                    <div class="shape-only-prop hidden">
                        <label class="block text-xs font-semibold text-gray-700 mb-2">Fondo (Background)</label>
                        
                        <div class="flex items-center gap-2 mb-2">
                            <input type="checkbox" id="prop-shape-is-gradient" class="rounded border-gray-300 text-black dark:text-white focus:ring-black cursor-pointer" />
                            <label for="prop-shape-is-gradient" class="text-xs font-semibold text-gray-700 cursor-pointer">Usar Degradado Lineal</label>
                        </div>
                        
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <div class="flex flex-col items-center flex-1">
                                <span class="text-[10px] text-gray-500 uppercase font-bold mb-1" id="label-color-1">Color 1</span>
                                <input type="color" id="prop-shape-color1" value="#000000" class="block w-full h-8 p-0 border-0 rounded cursor-pointer mb-1" />
                                <input type="range" id="prop-shape-color1-opacity" min="0" max="100" value="100" class="w-full accent-indigo-600 h-1" />
                            </div>
                            <div class="flex flex-col items-center flex-1" id="prop-shape-color2-container" style="display: none;">
                                <span class="text-[10px] text-gray-500 uppercase font-bold mb-1">Color 2</span>
                                <input type="color" id="prop-shape-color2" value="#ffffff" class="block w-full h-8 p-0 border-0 rounded cursor-pointer mb-1" />
                                <input type="range" id="prop-shape-color2-opacity" min="0" max="100" value="100" class="w-full accent-indigo-600 h-1" />
                            </div>
                        </div>

                        <div id="prop-shape-angle-container" style="display: none;">
                            <label class="block text-[10px] font-semibold text-gray-500 uppercase mb-1 mt-2">Ángulo (°)</label>
                            <input type="number" id="prop-shape-angle" value="90" min="0" max="360" class="block w-full text-sm border border-gray-300 rounded-md px-2 py-1 focus:ring-black focus:border-black dark:border-white" />
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Workspace -->
        <main id="workspace-container" class="flex-1">
            <div id="cover-scaler">
            <div id="cover-spread">
                <!-- Bleed Guide -->
                <div id="bleed-guide" class="absolute border-2 border-dashed border-red-500 pointer-events-none z-20"></div>

                <!-- Back Flap -->
                <div id="back-flap" class="cover-part flex items-center justify-center text-gray-300 overflow-hidden" style="width: 0px; border: none;">
                    <span class="text-sm font-semibold uppercase tracking-widest text-gray-200 transform -rotate-90 whitespace-nowrap">Solapa</span>
                </div>

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

                <!-- Front Flap -->
                <div id="front-flap" class="cover-part flex items-center justify-center text-gray-300 overflow-hidden" style="width: 0px; border: none;">
                    <span class="text-sm font-semibold uppercase tracking-widest text-gray-200 transform rotate-90 whitespace-nowrap">Solapa</span>
                </div>
            </div>
            </div>
        </main>

        <!-- Right Sidebar (Layers Panel) -->
        <aside class="w-64 bg-white border-l border-gray-200 flex flex-col shrink-0 shadow-sm z-10">
            <div class="p-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h2 class="font-bold text-sm uppercase tracking-wider text-gray-800">Capas (Layers)</h2>
            </div>
            
            <div class="p-2 border-b border-gray-100 flex flex-col justify-center gap-2 bg-white">
                <button id="add-text-layer-btn" class="w-full bg-white border border-gray-300 text-gray-700 py-1.5 rounded text-xs font-semibold hover:bg-gray-50 transition flex items-center justify-center gap-1 shadow-sm">
                    <i class="fa-solid fa-t"></i> Texto
                </button>
                <button id="add-image-layer-btn" class="w-full bg-white border border-gray-300 text-gray-700 py-1.5 rounded text-xs font-semibold hover:bg-gray-50 transition flex items-center justify-center gap-1 shadow-sm">
                    <i class="fa-regular fa-image"></i> Imagen
                </button>
                <button id="add-shape-layer-btn" class="w-full bg-white border border-gray-300 text-gray-700 py-1.5 rounded text-xs font-semibold hover:bg-gray-50 transition flex items-center justify-center gap-1 shadow-sm">
                    <i class="fa-solid fa-shapes"></i> Forma
                </button>
            </div>

            <div id="layers-list" class="flex-1 overflow-y-auto bg-gray-50 flex flex-col p-2 gap-1">
                <!-- Layers will be rendered here via JS -->
            </div>
        </aside>
    </div>

    <script src="<?php echo esc_url( plugin_dir_url( dirname( dirname(__FILE__) ) ) . 'assets/js/cover/cover-state.js?v=' . time() ); ?>"></script>
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
