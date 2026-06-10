<?php
require_once plugin_dir_path( __DIR__ ) . 'includes/editor-data-loader.php';
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
    <style>
        .is-dragging-chapter .group * { pointer-events: none; }
    </style>
    <script>
        var ajaxurl = "<?php echo admin_url( 'admin-ajax.php' ); ?>";
    </script>
    <?php wp_head(); ?>
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

            <!-- Botones de Acción -->
            <div class="flex gap-2">
                <button onclick="toggleSettingsModal(true)" class="p-2 border border-[var(--border-color)] hover:bg-[var(--bg-app)] rounded-lg text-[var(--text-muted)] hover:text-[var(--text-main)] transition" title="Configuración de Maquetación del PDF">
                    <i class="fa-solid fa-gear"></i>
                </button>
                <button id="btn-export-pdf" onclick="triggerPrint()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg shadow-md hover:shadow-lg transition flex items-center gap-2">
                    <i class="fa-solid fa-file-pdf"></i>
                    <span class="hidden sm:inline">Imprimir PDF</span>
                </button>
            </div>
        </div>
    </header>

    <!-- CUERPO PRINCIPAL CONTENEDOR -->
    <div class="flex flex-1 overflow-hidden relative">

        <!-- BARRA LATERAL IZQUIERDA -->
        <aside id="sidebar" class="w-80 border-r border-[var(--border-color)] bg-[var(--bg-sidebar)] flex flex-col justify-between transition-all z-20 no-print h-full">
            <div class="p-4 shrink-0 pb-2 flex gap-2">
                <button onclick="createNewChapter(false)" class="flex-1 py-3 px-2 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white font-bold rounded-xl shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-1 text-sm">
                    <i class="fa-solid fa-plus-circle"></i>
                    Capítulo
                </button>
                <button onclick="createNewChapter(true)" class="flex-1 py-3 px-2 bg-gradient-to-r from-slate-500 to-slate-600 hover:from-slate-600 hover:to-slate-700 text-white font-bold rounded-xl shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-1 text-sm" title="Crear Índice">
                    <i class="fa-solid fa-list-ol"></i>
                    Índice
                </button>
            </div>

            <div class="px-4 pb-4 flex flex-col flex-1 overflow-y-auto">
                <!-- Listado de Capítulos -->
                <div class="flex-1 mt-2">
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
                <div class="flex justify-between text-xs text-[var(--text-muted)] mb-1">
                    <span>Palabras Totales:</span>
                    <span id="total-words" class="font-bold text-[var(--text-main)]">0</span>
                </div>
                <div class="flex justify-between text-xs text-[var(--text-muted)]">
                    <span>Páginas Totales:</span>
                    <span id="total-pages-sidebar" class="font-bold text-[var(--text-main)]"><?php echo esc_html( get_post_meta( $book_id, '_almaden_total_pages', true ) ?: '-' ); ?></span>
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
                        <button onclick="openMediaUploader()" class="p-1.5 hover:bg-[var(--bg-app)] hover:text-[var(--text-main)] rounded transition" title="Insertar Imagen">
                            <i class="fa-regular fa-image"></i>
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

                        <div class="h-4 w-px bg-[var(--border-color)] mx-1"></div>

                        <!-- Alineación -->
                        <div class="flex items-center">
                            <button onclick="wrapText('\n[align=left]\n', '\n[/align]\n')" class="p-1.5 hover:bg-[var(--bg-app)] hover:text-[var(--text-main)] rounded transition" title="Alinear a la Izquierda">
                                <i class="fa-solid fa-align-left text-xs"></i>
                            </button>
                            <button onclick="wrapText('\n[align=center]\n', '\n[/align]\n')" class="p-1.5 hover:bg-[var(--bg-app)] hover:text-[var(--text-main)] rounded transition" title="Centrar">
                                <i class="fa-solid fa-align-center text-xs"></i>
                            </button>
                            <button onclick="wrapText('\n[align=right]\n', '\n[/align]\n')" class="p-1.5 hover:bg-[var(--bg-app)] hover:text-[var(--text-main)] rounded transition" title="Alinear a la Derecha">
                                <i class="fa-solid fa-align-right text-xs"></i>
                            </button>
                            <button onclick="wrapText('\n[align=justify]\n', '\n[/align]\n')" class="p-1.5 hover:bg-[var(--bg-app)] hover:text-[var(--text-main)] rounded transition" title="Justificar">
                                <i class="fa-solid fa-align-justify text-xs"></i>
                            </button>
                        </div>

                        <div class="h-4 w-px bg-[var(--border-color)] mx-1"></div>

                        <!-- Tamaño de Fuente -->
                        <div class="flex items-center gap-1 bg-[var(--bg-app)] rounded px-1.5 border border-transparent hover:border-[var(--border-color)] transition focus-within:border-indigo-400">
                            <input type="number" id="toolbar-font-size" value="16" min="8" max="72" class="w-10 bg-transparent text-xs text-center focus:outline-none" title="Tamaño de fuente">
                            <span class="text-[10px] text-slate-400 font-mono">px</span>
                            <button onclick="applyFontSize()" class="ml-1 p-0.5 text-indigo-500 hover:text-indigo-600 transition" title="Aplicar tamaño al texto seleccionado">
                                <i class="fa-solid fa-check text-[10px]"></i>
                            </button>
                        </div>

                        <div class="h-4 w-px bg-[var(--border-color)] mx-1"></div>

                        <!-- Tipografía -->
                        <div class="flex items-center bg-[var(--bg-app)] rounded px-1.5 border border-transparent hover:border-[var(--border-color)] transition focus-within:border-indigo-400 max-w-[120px]">
                            <select id="toolbar-font-family" onchange="applyFontFamily(this.value)" class="w-full bg-transparent text-xs focus:outline-none appearance-none cursor-pointer text-ellipsis" title="Cambiar tipografía al texto seleccionado">
                                <option value="" disabled selected>Fuente...</option>
                                <?php
                                foreach ( $installed_fonts as $ifont ) {
                                    echo '<option value="' . esc_attr( $ifont['family'] ) . '">' . esc_html( $ifont['family'] ) . '</option>';
                                }
                                ?>
                            </select>
                            <i class="fa-solid fa-chevron-down text-[8px] opacity-50 ml-1 pointer-events-none"></i>
                        </div>

                        <div class="h-4 w-px bg-[var(--border-color)] mx-1"></div>
                        <!-- Selector de idioma para hyphenation específico por frase -->
                        <div class="relative" id="lang-selector-wrapper">
                            <button onclick="toggleLangDropdown()" 
                                class="flex items-center gap-1 px-2 py-1 text-xs font-mono font-semibold hover:bg-[var(--bg-app)] hover:text-[var(--text-main)] rounded transition border border-transparent hover:border-[var(--border-color)]"
                                title="Aplicar idioma a texto seleccionado (para hyphenation)">
                                <i class="fa-solid fa-language text-sm"></i>
                                <span class="hidden sm:inline">Lang</span>
                                <i class="fa-solid fa-chevron-down text-[9px] opacity-60"></i>
                            </button>
                            <!-- Dropdown de idiomas -->
                            <div id="lang-dropdown" class="hidden absolute top-full left-0 mt-1 bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg shadow-xl z-50 py-1 min-w-[160px]">
                                <p class="text-[10px] text-[var(--text-muted)] px-3 pt-1 pb-1 uppercase tracking-wider font-semibold">Idioma del fragmento</p>
                                <button onclick="applyLanguage('es')" class="w-full text-left px-3 py-1.5 text-xs hover:bg-[var(--bg-app)] transition flex items-center gap-2">
                                    <span class="font-mono font-bold text-indigo-500">es</span> Español
                                </button>
                                <button onclick="applyLanguage('en')" class="w-full text-left px-3 py-1.5 text-xs hover:bg-[var(--bg-app)] transition flex items-center gap-2">
                                    <span class="font-mono font-bold text-indigo-500">en</span> English
                                </button>
                                <button onclick="applyLanguage('fr')" class="w-full text-left px-3 py-1.5 text-xs hover:bg-[var(--bg-app)] transition flex items-center gap-2">
                                    <span class="font-mono font-bold text-indigo-500">fr</span> Français
                                </button>
                                <button onclick="applyLanguage('de')" class="w-full text-left px-3 py-1.5 text-xs hover:bg-[var(--bg-app)] transition flex items-center gap-2">
                                    <span class="font-mono font-bold text-indigo-500">de</span> Deutsch
                                </button>
                                <button onclick="applyLanguage('pt')" class="w-full text-left px-3 py-1.5 text-xs hover:bg-[var(--bg-app)] transition flex items-center gap-2">
                                    <span class="font-mono font-bold text-indigo-500">pt</span> Português
                                </button>
                                <button onclick="applyLanguage('it')" class="w-full text-left px-3 py-1.5 text-xs hover:bg-[var(--bg-app)] transition flex items-center gap-2">
                                    <span class="font-mono font-bold text-indigo-500">it</span> Italiano
                                </button>
                                <div class="border-t border-[var(--border-color)] my-1"></div>
                                <button onclick="removeLanguage()" class="w-full text-left px-3 py-1.5 text-xs hover:bg-[var(--bg-app)] transition flex items-center gap-2 text-rose-500">
                                    <i class="fa-solid fa-xmark text-xs"></i> Quitar idioma
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Contador local del Capítulo -->
                    <div class="text-xs font-semibold flex items-center gap-3">
                        <span id="current-word-count">0 palabras</span>
                        <div class="h-4 w-px bg-[var(--border-color)] mx-1"></div>
                        <button onclick="saveStateToLocalStorage(true)" id="toolbar-save-btn" class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-900/30 dark:border-emerald-800/50 dark:text-emerald-400 hover:bg-emerald-100 dark:hover:bg-emerald-900/50 rounded transition flex items-center gap-1.5" title="Guardar cambios (Ctrl+S / Cmd+S)">
                            <i class="fa-solid fa-floppy-disk text-[10px]"></i>
                            <span>Guardar</span>
                        </button>
                    </div>
                </div>

                <!-- Campo de Entrada del Título del Capítulo -->
                <!-- Contenido centrado: Título + Textarea con max-width 800px -->
                <div class="flex-1 flex flex-col overflow-hidden">
                    <div class="flex-1 overflow-y-auto">
                        <div class="max-w-[800px] mx-auto px-6 pt-6 pb-6 flex flex-col h-full min-h-full">
                            <!-- Título del capítulo y Configuración Local -->
                            <div class="flex items-center gap-3 mb-4 border-b-2 border-transparent focus-within:border-indigo-500 transition-all pb-2">
                                <input id="chapter-title-input" type="text" placeholder="Título del Capítulo..."
                                    class="w-full bg-transparent font-serif font-semibold text-2xl md:text-3xl focus:outline-none text-[var(--text-main)]">
                                <button onclick="openChapterSettingsModal()" class="text-[var(--text-muted)] hover:text-indigo-500 transition-colors p-2 rounded-lg hover:bg-[var(--bg-sidebar)]" title="Configuración de este Capítulo">
                                    <i class="fa-solid fa-gear text-lg"></i>
                                </button>
                            </div>
                            <!-- Área de escritura -->
                            <textarea id="editor-textarea"
                                class="flex-1 w-full resize-none bg-transparent text-[var(--text-main)] focus:outline-none font-mono text-sm leading-relaxed placeholder-gray-400 dark:placeholder-gray-600 focus:ring-0"
                                style="min-height: 400px;"
                                placeholder="Escribe tu historia aquí utilizando formato simple o las herramientas de arriba..."></textarea>
                        </div>
                    </div>
                </div>
            </section>

            <!-- PANEL DE VISTA PREVIA PDF (DERECHO) -->
            <section id="pdf-preview-pane" class="flex-1 flex flex-col pdf-page-container overflow-hidden transition-all">
                <!-- Barra informativa superior de página -->
                <div class="h-12 border-b border-[var(--border-color)] bg-[var(--bg-sidebar)] px-4 flex items-center justify-between text-xs text-[var(--text-muted)] no-print">
                    <span class="font-semibold uppercase tracking-wider flex items-center gap-1">
                        <i class="fa-solid fa-magnifying-glass-doc text-xs text-indigo-500"></i> Vista Previa
                    </span>
                    <div class="flex items-center gap-3">

                        <button id="btn-toggle-spread" class="text-[var(--text-muted)] hover:text-indigo-500 transition-colors" title="Alternar Vista a Doble Página">
                            <i class="fa-solid fa-file-lines"></i>
                        </button>
                        <span id="pdf-page-indicator">0 Páginas</span>
                    </div>
                </div>

                <!-- Visor Scrollable de Páginas PDF -->
                <div id="pdf-scroller" class="flex-1 overflow-y-auto p-4 md:p-8 space-y-4">
                    <!-- Contenido dinámico del PDF compilado por JS -->
                </div>
            </section>
        </main>
    </div>

    <!-- Modals -->
    <?php include plugin_dir_path( __FILE__ ) . 'editor-settings-modal.php'; ?>
    <?php include plugin_dir_path( __FILE__ ) . 'chapter-settings-modal.php'; ?>

    <!-- NOTIFICACIÓN FLOTANTE (TOAST) -->
    <div id="toast" class="fixed bottom-5 right-5 z-50 transform translate-y-10 opacity-0 pointer-events-none transition-all duration-300 bg-slate-900 text-white dark:bg-indigo-600 px-4 py-3 rounded-xl shadow-2xl flex items-center gap-3">
        <span id="toast-icon" class="text-emerald-400"><i class="fa-solid fa-circle-check"></i></span>
        <span id="toast-message" class="text-sm font-medium">Libro guardado con éxito</span>
    </div>

    <!-- SCRIPT DE COMPORTAMIENTO LÓGICO Y FUNCIONALIDADES -->
    <!-- COMPORTAMIENTO LÓGICO Y FUNCIONALIDADES MODULARES -->
    <script>
        window.onerror = function(msg, url, line, col, error) {
            alert("JS Error: " + msg + "\nLine: " + line + "\nFile: " + url.split('/').pop());
            return false;
        };
        // Estado Global
        let bookState = {
            title: <?php echo json_encode( $book_title ); ?>,
            chapters: <?php echo json_encode( $saved_chapters ); ?>,
            activeChapterId: localStorage.getItem('almaden_active_chapter_<?php echo $book_id; ?>') || <?php echo json_encode( !empty($saved_chapters) ? $saved_chapters[0]['id'] : '' ); ?>,
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
    <script src="<?php echo esc_url( plugins_url( '../assets/js/editor-chapter-settings.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../assets/js/editor-markdown.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../assets/js/editor-pdf-dom.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../assets/js/editor-pdf-pagination.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../assets/js/editor-pdf-html.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../assets/js/editor-pdf-compiler.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../assets/js/editor-pdf-export.js?v='   . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../assets/js/editor-pdf-styles.js?v='   . time(), __FILE__ ) ); ?>"></script>
    <?php wp_footer(); ?>
</body>
</html>
