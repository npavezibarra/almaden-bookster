<?php
$book_id = isset( $_GET['book_id'] ) ? intval( $_GET['book_id'] ) : 0;
$book = get_post( $book_id );

if ( ! $book || $book->post_type !== 'almaden-books' ) {
	wp_die( 'Libro no encontrado.' );
}

$book_title = $book->post_title;
$saved_chapters = get_post_meta( $book_id, '_almaden_chapters', true );

if ( ! is_array( $saved_chapters ) || empty( $saved_chapters ) ) {
	$saved_chapters = array(
		array(
			'id' => 'cap-1',
			'title' => 'Capítulo I: El Primer Suspiro',
			'content' => "# Capítulo I\n## El Primer Suspiro\n\nEl viento soplaba furioso contra las ventanas de la antigua cabaña. Aquella noche de invierno no parecía diferente a las anteriores, pero el destino ya había trazado su línea de no retorno. Daniel, sentado frente a su rústica mesa de madera, sostenía una pluma gastada.\n\n*\"Las palabras tienen el poder de dar vida, pero también de arrebatarla\"*, murmuró para sus adentros.\n\nFrente a él yacía un manuscrito antiguo encuadernado en cuero desgastado. Nadie debía saber lo que contenía, pero las sombras acechaban más de lo usual en los rincones de la habitación. De repente, un golpe seco resonó en la puerta principal. Tres toques rítmicos, seguidos de un profundo silencio.\n\n> Aquel que busca respuestas en las sombras debe estar preparado para ver lo que las sombras revelan.\n\n- Daniel apagó la vela rápidamente.\n- El silencio de la casa se volvió ensordecedor.\n- Con sigilo, deslizó la mano por debajo de la mesa buscando la vieja llave de latón."
		)
	);
}

// Cargar ajustes del libro desde la tabla especial
global $wpdb;
$settings_table = $wpdb->prefix . 'almaden_book_settings';
$db_settings = array();
if ( $wpdb->get_var( "SHOW TABLES LIKE '$settings_table'" ) === $settings_table ) {
	$db_settings = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $settings_table WHERE book_id = %d", $book_id ), ARRAY_A );
}

$pdf_settings = array(
	'unit'                       => 'cm',
	'page_size'                  => 'A4',
	'page_width'                 => 21.0,
	'page_height'                => 29.7,
	'margin_top'                 => 2.5,
	'margin_bottom'              => 2.5,
	'margin_left'                => 2.0,
	'margin_right'               => 2.0,
	'margin_left_odd'            => 2.0,
	'margin_right_odd'           => 2.0,
	'margin_left_even'           => 2.0,
	'margin_right_even'          => 2.0,
	'padding_top'                => 0.0,
	'padding_bottom'             => 0.0,
	'padding_left'               => 0.0,
	'padding_right'              => 0.0,
	'bleeding'                   => 0.0,
	'font_family_content'        => 'Merriweather',
	'font_size_content'          => 11.5,
	'line_height_content'        => 1.65,
	'content_text_align'         => 'justify',
	'content_hyphenation'        => 1,
	'content_language'           => 'es',
	'content_paragraph_indent'   => 0.0,
	'content_paragraph_spacing'  => 14.0,
	'font_family_headings'       => 'Playfair Display',
	'font_family_h1'             => 'Playfair Display',
	'font_family_h2'             => 'Playfair Display',
	'font_family_h3'             => 'Playfair Display',
	'font_weight_h1'             => 'bold',
	'font_weight_h2'             => 'bold',
	'font_weight_h3'             => 'bold',
	'font_size_h1'               => 24.0,
	'font_size_h2'               => 16.0,
	'font_size_h3'               => 13.0,
	'header_font_family'         => 'Merriweather',
	'header_font_size'           => 8.5,
	'header_font_weight'         => 'normal',
	'header_font_style'          => 'normal',
	'header_letter_spacing'      => 0.1,
	'header_even_type'           => 'book_title',
	'header_even_custom'         => '',
	'header_odd_type'            => 'chapter_title',
	'header_odd_custom'          => '',
	'footer_font_family'         => 'Merriweather',
	'footer_font_size'           => 9.0,
	'footer_font_weight'         => 'normal',
	'footer_font_style'          => 'normal',
	'footer_letter_spacing'      => 0.0,
	'footer_even_type'           => 'page_number',
	'footer_odd_type'            => 'page_number',
	'show_header_page_one'       => 0,
	'chapter_start_parity'       => 'any',
	'chapter_page_one_align'     => 'center',
	'chapter_page_one_vertical'  => 'top',
	'chapter_title_font_family'  => 'Playfair Display',
	'chapter_title_font_size'    => 24.0,
	'chapter_title_font_weight'  => 'bold',
	'chapter_title_font_style'   => 'normal',
	'chapter_title_align'        => 'center',
	'chapter_title_padding_top'  => 0.0,
	'chapter_title_padding_bottom'=> 1.5,
	'header_margin_top'          => 1.0,
	'header_margin_bottom'       => 0.5,
	'header_align'               => 'center',
	'footer_margin_top'          => 0.5,
	'footer_margin_bottom'       => 1.0,
	'footer_align'               => 'center'
);

if ( $db_settings ) {
	foreach ( $pdf_settings as $key => $default ) {
		if ( isset( $db_settings[$key] ) ) {
			if ( is_float( $default ) ) {
				$pdf_settings[$key] = floatval( $db_settings[$key] );
			} elseif ( is_int( $default ) ) {
				$pdf_settings[$key] = intval( $db_settings[$key] );
			} else {
				$pdf_settings[$key] = $db_settings[$key];
			}
		}
	}
}

// Fallback de retrocompatibilidad: si los márgenes par/impar no existen (BD antigua), usar los globales
if ( ! isset( $pdf_settings['margin_left_odd'] ) ) {
	$pdf_settings['margin_left_odd'] = $pdf_settings['margin_left'];
	$pdf_settings['margin_right_odd'] = $pdf_settings['margin_right'];
	$pdf_settings['margin_left_even'] = $pdf_settings['margin_left'];
	$pdf_settings['margin_right_even'] = $pdf_settings['margin_right'];
}

// Cargar fuentes instaladas desde la tabla de Google Fonts
$installed_fonts = almaden_bookster_get_installed_fonts_list();

// Construir URL dinámica de Google Fonts CDN con las fuentes instaladas y TODOS sus pesos
$font_families_for_cdn = array();
// Default built-ins (Inter, Merriweather, Playfair Display)
$font_families_for_cdn[] = 'Inter:wght@100;200;300;400;500;600;700;800;900';
$font_families_for_cdn[] = 'Merriweather:ital,wght@0,300;0,400;0,700;0,900;1,300;1,400;1,700;1,900';
$font_families_for_cdn[] = 'Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;0,800;0,900;1,400;1,500;1,600;1,700;1,800;1,900';

foreach ( $installed_fonts as $ifont ) {
	$family_slug = str_replace( ' ', '+', $ifont['family'] );
	
	$variants_str = isset($ifont['variants']) ? $ifont['variants'] : '';
	if ( empty($variants_str) ) {
		// Fallback
		$font_families_for_cdn[] = $family_slug . ':ital,wght@0,400;0,700;1,400';
		continue;
	}

	$variants_arr = explode(',', $variants_str);
	$tuples = array();
	foreach ( $variants_arr as $v ) {
		$v = trim($v);
		if ( empty($v) ) continue;
		
		$ital = 0;
		$wght = 400;
		
		if ( strpos($v, 'italic') !== false ) {
			$ital = 1;
			$w_str = str_replace('italic', '', $v);
			if ( $w_str === '' || $w_str === 'regular' ) {
				$wght = 400;
			} else {
				$wght = intval($w_str);
			}
		} else {
			if ( $v === 'regular' ) {
				$wght = 400;
			} else {
				$wght = intval($v);
			}
		}
		
		if ($wght >= 100 && $wght <= 900) {
			$tuples[] = $ital . ',' . $wght;
		}
	}
	
	if ( empty($tuples) ) {
		$font_families_for_cdn[] = $family_slug . ':ital,wght@0,400;0,700;1,400';
	} else {
		// API v2 requires them to be sorted
		sort($tuples);
		$font_families_for_cdn[] = $family_slug . ':ital,wght@' . implode(';', $tuples);
	}
}
$google_fonts_url = 'https://fonts.googleapis.com/css2?' . implode( '&', array_map( function( $f ) { return 'family=' . $f; }, $font_families_for_cdn ) ) . '&display=swap';
?>
<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookCraft - Editor de Libros Profesional</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Librería para exportar PDF directamente -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <!-- Google Fonts: Inter para la interfaz, Merriweather para el estilo de libro PDF -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="<?php echo esc_url( $google_fonts_url ); ?>" rel="stylesheet">
    <!-- Font Awesome Icons para UI -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="<?php echo esc_url( plugins_url( '../assets/css/editor-style.css?v=' . time(), __FILE__ ) ); ?>">
    <!-- Estilos dinámicos de maquetación del PDF -->
    <style id="dynamic-pdf-settings"></style>
</head>

<body class="theme-light h-full overflow-hidden flex flex-col bg-[var(--bg-app)] text-[var(--text-main)]">

    <!-- CABECERA PRINCIPAL -->
    <header class="h-16 border-b border-[var(--border-color)] bg-[var(--bg-sidebar)] px-6 flex items-center justify-between z-10 no-print transition-all">
        <div class="flex items-center gap-3">
            <a href="<?php echo esc_url( home_url( '/almaden-booklist/' ) ); ?>" class="mr-2 text-[var(--text-muted)] hover:text-[var(--text-main)] transition-colors flex items-center gap-1.5 text-sm font-semibold" title="Volver a la lista de libros">
                <i class="fa-solid fa-arrow-left"></i>
                <span class="hidden sm:inline">Volver</span>
            </a>
            <div class="bg-indigo-600 text-white w-10 h-10 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-500/30">
                <i class="fa-solid fa-book-open text-lg"></i>
            </div>
            <div>
                <input id="book-title-input" type="text" value="Mi Novela Inédita" 
                    class="bg-transparent font-bold text-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 rounded px-1 w-48 md:w-64 border-b border-transparent hover:border-dashed hover:border-gray-400 transition-all" 
                    title="Haz clic para renombrar el libro">
                <p class="text-xs text-[var(--text-muted)] -mt-1 font-medium">Editor de Manuscritos</p>
            </div>
        </div>

        <!-- Opciones de Vista & Configuración -->
        <div class="flex items-center gap-4">
            <!-- Selector de Temas Visuales -->
            <div class="flex bg-[var(--bg-app)] rounded-lg p-1 border border-[var(--border-color)] gap-1">
                <button onclick="changeTheme('light')" class="w-8 h-8 rounded-md flex items-center justify-center text-sm hover:bg-white dark:hover:bg-slate-700 transition" title="Modo Claro">
                    <i class="fa-solid fa-sun text-amber-500"></i>
                </button>
                <button onclick="changeTheme('sepia')" class="w-8 h-8 rounded-md flex items-center justify-center text-sm hover:bg-amber-100/50 transition" title="Modo Sepia">
                    <i class="fa-solid fa-feather text-amber-800"></i>
                </button>
                <button onclick="changeTheme('dark')" class="w-8 h-8 rounded-md flex items-center justify-center text-sm hover:bg-slate-800 transition" title="Modo Oscuro">
                    <i class="fa-solid fa-moon text-indigo-400"></i>
                </button>
            </div>

            <!-- Toggles de Visualización -->
            <div class="hidden md:flex bg-[var(--bg-app)] rounded-lg p-1 border border-[var(--border-color)] text-xs font-semibold">
                <button id="view-split-btn" onclick="setViewMode('split')" class="px-3 py-1.5 rounded-md bg-indigo-600 text-white shadow-sm transition">
                    Dividido
                </button>
                <button id="view-edit-btn" onclick="setViewMode('edit')" class="px-3 py-1.5 rounded-md text-[var(--text-muted)] hover:text-[var(--text-main)] transition">
                    Solo Editor
                </button>
                <button id="view-preview-btn" onclick="setViewMode('preview')" class="px-3 py-1.5 rounded-md text-[var(--text-muted)] hover:text-[var(--text-main)] transition">
                    Solo PDF
                </button>
            </div>

            <!-- Botones de Exportación -->
            <div class="flex gap-2">
                <button onclick="toggleSettingsModal(true)" class="p-2 border border-[var(--border-color)] hover:bg-[var(--bg-app)] rounded-lg text-[var(--text-muted)] hover:text-[var(--text-main)] transition" title="Configuración de Maquetación del PDF">
                    <i class="fa-solid fa-gear"></i>
                </button>
                <button onclick="triggerPrint()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg shadow-md hover:shadow-lg transition flex items-center gap-2">
                    <i class="fa-solid fa-file-pdf"></i>
                    <span class="hidden sm:inline">Imprimir PDF</span>
                </button>
            </div>
        </div>
    </header>

    <!-- CUERPO PRINCIPAL CONTENEDOR -->
    <div class="flex flex-1 overflow-hidden relative">

        <!-- BARRA LATERAL IZQUIERDA -->
        <aside id="sidebar" class="w-80 border-r border-[var(--border-color)] bg-[var(--bg-sidebar)] flex flex-col justify-between transition-all z-20 no-print">
            <div class="p-4 flex flex-col flex-1 overflow-y-auto">
                <button onclick="createNewChapter()" class="w-full py-3 px-4 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white font-bold rounded-xl shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-2 mb-6">
                    <i class="fa-solid fa-plus-circle"></i>
                    Crear Capítulo
                </button>

                <!-- Listado de Capítulos -->
                <div class="flex-1">
                    <div class="flex items-center justify-between mb-3 px-2">
                        <span class="text-xs font-semibold uppercase tracking-wider text-[var(--text-muted)]">Índice de Capítulos</span>
                        <span id="chapter-count" class="text-xs bg-indigo-100 text-indigo-800 dark:bg-slate-800 dark:text-indigo-400 font-bold px-2 py-0.5 rounded-full">0</span>
                    </div>

                    <div id="chapters-list" class="space-y-1">
                        <!-- Generado dinámicamente -->
                    </div>
                </div>
            </div>

            <!-- Footer Sidebar con Información Adicional -->
            <div class="p-4 border-t border-[var(--border-color)] bg-[var(--bg-app)]/50">
                <div class="flex items-center justify-between text-xs text-[var(--text-muted)] mb-2">
                    <span>Estado del Libro:</span>
                    <span id="save-status" class="flex items-center gap-1 font-semibold text-emerald-600">
                        <i class="fa-solid fa-cloud-arrow-up text-xs"></i> Guardado
                    </span>
                </div>
                <div class="flex justify-between text-xs text-[var(--text-muted)]">
                    <span>Palabras Totales:</span>
                    <span id="total-words" class="font-bold text-[var(--text-main)]">0</span>
                </div>
                <!-- Mini manual rápido -->
                <div class="mt-3 p-2 bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg text-[10px] text-[var(--text-muted)] leading-relaxed">
                    <p class="font-bold mb-1"><i class="fa-solid fa-info-circle mr-1"></i> Formato Rápido (Markdown):</p>
                    <p># Capítulo | ## Subtítulo | **Negrita**</p>
                    <p>*Itálica* | > Cita | - Lista</p>
                </div>
            </div>
        </aside>

        <!-- CONTENEDOR PRINCIPAL DE CONTENIDOS -->
        <main class="flex-1 flex overflow-hidden">
            
            <!-- PANEL DEL EDITOR (IZQUIERDO) -->
            <section id="editor-pane" class="flex-1 flex flex-col border-r border-[var(--border-color)] bg-[var(--bg-editor)] overflow-hidden transition-all">
                <!-- Barra de Herramientas de Edición -->
                <div class="h-12 border-b border-[var(--border-color)] bg-[var(--bg-sidebar)] px-4 flex items-center justify-between text-[var(--text-muted)]">
                    <div class="flex items-center gap-1 sm:gap-2">
                        <button onclick="wrapText('**', '**')" class="p-1.5 hover:bg-[var(--bg-app)] hover:text-[var(--text-main)] rounded transition" title="Negrita">
                            <i class="fa-solid fa-bold"></i>
                        </button>
                        <button onclick="wrapText('*', '*')" class="p-1.5 hover:bg-[var(--bg-app)] hover:text-[var(--text-main)] rounded transition" title="Itálica">
                            <i class="fa-solid fa-italic"></i>
                        </button>
                        <button onclick="wrapText('<u>', '</u>')" class="p-1.5 hover:bg-[var(--bg-app)] hover:text-[var(--text-main)] rounded transition" title="Subrayado">
                            <i class="fa-solid fa-underline"></i>
                        </button>
                        <div class="h-4 w-px bg-[var(--border-color)] mx-1"></div>
                        <button onclick="addPrefix('# ')" class="p-1.5 hover:bg-[var(--bg-app)] hover:text-[var(--text-main)] rounded transition" title="Título Principal">
                            <span class="font-bold text-xs">H1</span>
                        </button>
                        <button onclick="addPrefix('## ')" class="p-1.5 hover:bg-[var(--bg-app)] hover:text-[var(--text-main)] rounded transition" title="Subtítulo">
                            <span class="font-bold text-xs text-[10px]">H2</span>
                        </button>
                        <button onclick="addPrefix('> ')" class="p-1.5 hover:bg-[var(--bg-app)] hover:text-[var(--text-main)] rounded transition" title="Cita textual">
                            <i class="fa-solid fa-quote-left text-xs"></i>
                        </button>
                        <button onclick="addPrefix('- ')" class="p-1.5 hover:bg-[var(--bg-app)] hover:text-[var(--text-main)] rounded transition" title="Lista de viñetas">
                            <i class="fa-solid fa-list-ul"></i>
                        </button>
                    </div>

                    <!-- Contador local del Capítulo -->
                    <div class="text-xs font-semibold flex items-center gap-3">
                        <span id="current-word-count">0 palabras</span>
                    </div>
                </div>

                <!-- Campo de Entrada del Título del Capítulo -->
                <div class="p-6 pb-2">
                    <input id="chapter-title-input" type="text" placeholder="Título del Capítulo..."
                        class="w-full bg-transparent font-serif font-semibold text-2xl md:text-3xl border-b-2 border-transparent focus:border-indigo-500 focus:outline-none pb-2 text-[var(--text-main)] transition-all">
                </div>

                <!-- Área de Texto de Escritura Raw -->
                <div class="flex-1 px-6 pb-6 relative">
                    <textarea id="editor-textarea" 
                        class="w-full h-full resize-none bg-transparent text-[var(--text-main)] focus:outline-none font-mono text-sm leading-relaxed placeholder-gray-400 dark:placeholder-gray-600 focus:ring-0 overflow-y-auto"
                        placeholder="Escribe tu historia aquí utilizando formato simple o las herramientas de arriba..."></textarea>
                </div>
            </section>

            <!-- PANEL DE VISTA PREVIA PDF (DERECHO) -->
            <section id="pdf-preview-pane" class="flex-1 flex flex-col pdf-page-container overflow-hidden transition-all">
                <!-- Barra informativa superior de página -->
                <div class="h-12 border-b border-[var(--border-color)] bg-[var(--bg-sidebar)] px-4 flex items-center justify-between text-xs text-[var(--text-muted)] no-print">
                    <span class="font-semibold uppercase tracking-wider flex items-center gap-1">
                        <i class="fa-solid fa-magnifying-glass-doc text-xs text-indigo-500"></i> Vista Previa Maquetada
                    </span>
                    <span id="pdf-page-indicator">0 Páginas</span>
                </div>

                <!-- Visor Scrollable de Páginas PDF -->
                <div id="pdf-scroller" class="flex-1 overflow-y-auto p-4 md:p-8 space-y-4">
                    <!-- Contenido dinámico del PDF compilado por JS -->
                </div>
            </section>
        </main>
    </div>

    <!-- MODAL DE CONFIGURACIÓN DEL LIBRO -->
    <?php include plugin_dir_path( __FILE__ ) . 'editor-settings-modal.php'; ?>

    <!-- NOTIFICACIÓN FLOTANTE (TOAST) -->
    <div id="toast" class="fixed bottom-5 right-5 z-50 transform translate-y-10 opacity-0 pointer-events-none transition-all duration-300 bg-slate-900 text-white dark:bg-indigo-600 px-4 py-3 rounded-xl shadow-2xl flex items-center gap-3">
        <span id="toast-icon" class="text-emerald-400"><i class="fa-solid fa-circle-check"></i></span>
        <span id="toast-message" class="text-sm font-medium">Libro guardado con éxito</span>
    </div>

    <!-- SCRIPT DE COMPORTAMIENTO LÓGICO Y FUNCIONALIDADES -->
    <!-- COMPORTAMIENTO LÓGICO Y FUNCIONALIDADES MODULARES -->
    <script>
        // Estado Global
        let bookState = {
            title: <?php echo json_encode( $book_title ); ?>,
            chapters: <?php echo json_encode( $saved_chapters ); ?>,
            activeChapterId: <?php echo json_encode( !empty($saved_chapters) ? $saved_chapters[0]['id'] : '' ); ?>,
            theme: "light",
            viewMode: "split",
            bookId: <?php echo $book_id; ?>,
            ajaxUrl: <?php echo json_encode( admin_url( 'admin-ajax.php' ) ); ?>,
            nonce: <?php echo json_encode( wp_create_nonce( 'almaden_save_book_nonce_' . $book_id ) ); ?>,
            settings: <?php echo json_encode( $pdf_settings ); ?>,
            settingsNonce: <?php echo json_encode( wp_create_nonce( 'almaden_save_settings_nonce_' . $book_id ) ); ?>,
            installedFonts: <?php echo json_encode( $installed_fonts ); ?>
        };
    </script>
    <script src="<?php echo esc_url( plugins_url( '../assets/js/editor-core.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../assets/js/editor-chapters.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../assets/js/editor-settings.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../assets/js/editor-markdown.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../assets/js/editor-pdf-compiler.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../assets/js/editor-pdf-export.js?v='   . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../assets/js/editor-pdf-styles.js?v='   . time(), __FILE__ ) ); ?>"></script>
</body>
</html>
