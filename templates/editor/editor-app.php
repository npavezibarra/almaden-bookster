<?php
require_once plugin_dir_path( dirname( __DIR__ ) ) . 'includes/helpers/editor-data-loader.php';

// Prevent caching of the editor page
if (!headers_sent()) {
    header("Cache-Control: no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");
    header("Expires: Wed, 11 Jan 1984 05:00:00 GMT");
}
?>
<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookCraft - Editor de Libros Profesional</title>
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
</head>

<body class="theme-light h-full overflow-hidden flex flex-col bg-[var(--bg-app)] text-[var(--text-main)]">

    <!-- CABECERA PRINCIPAL -->
    <header class="h-16 border-b border-[var(--border-color)] bg-[var(--bg-sidebar)] px-6 flex items-center justify-between z-10 no-print transition-all">
        <div class="flex items-center gap-3">
            <a href="<?php echo esc_url( almaden_bookster_get_creator_page_url() ); ?>" class="mr-2 text-[var(--text-muted)] hover:text-[var(--text-main)] transition-colors flex items-center gap-1.5 text-sm font-semibold" title="Volver al Taller">
                <i class="fa-solid fa-arrow-left"></i>
                <span class="hidden sm:inline">Volver</span>
            </a>
            <div class="bg-black text-white w-10 h-10 rounded-xl flex items-center justify-center shadow-lg shadow-black/30">
                <i class="fa-solid fa-book-open text-lg"></i>
            </div>
            <div>
                <input id="book-title-input" type="text" value="Mi Novela Inédita" 
                    class="bg-transparent font-bold text-lg focus:outline-none focus:ring-2 focus:ring-black rounded px-1 w-48 md:w-64 border-b border-transparent hover:border-dashed hover:border-gray-400 transition-all" 
                    title="Haz clic para renombrar el libro">
                <p class="text-xs text-[var(--text-muted)] -mt-1 font-medium">Editor de Manuscritos</p>
            </div>
        </div>

        <!-- Opciones de Vista & Configuración -->
        <div class="flex items-center gap-4">
            <!-- Toggles de Visualización -->
            <div class="hidden md:flex bg-[var(--bg-app)] rounded-lg p-1 border border-[var(--border-color)] text-xs font-semibold">
                <button id="view-split-btn" onclick="setViewMode('split')" class="px-3 py-1.5 rounded-md bg-black text-white shadow-sm transition">
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
                <button id="btn-export-pdf" onclick="triggerPrint()" class="px-4 py-2 bg-black hover:bg-neutral-800 text-white text-sm font-semibold rounded-[6px] shadow-md hover:shadow-lg transition flex items-center gap-2">
                    <i class="fa-solid fa-file-pdf"></i>
                    <span class="hidden sm:inline">Imprimir PDF</span>
                </button>
            </div>
        </div>
    </header>

    <!-- CUERPO PRINCIPAL CONTENEDOR -->
    <div id="almaden-editor-shell" class="flex flex-1 overflow-hidden relative">
        <!-- BARRA LATERAL IZQUIERDA -->
        <aside id="sidebar" class="w-80 border-r border-[var(--border-color)] bg-[var(--bg-sidebar)] flex flex-col justify-between transition-all z-20 no-print h-full">
            <div class="pl-4 pr-0 pt-0 pb-0 shrink-0 relative" id="add-chapter-dropdown-wrapper">
                <div class="flex items-center gap-2">
                    <div id="sidebar-toggle-sidebar-slot"></div>
                    <button id="add-chapter-main-btn" onclick="toggleAddChapterDropdown()" class="flex-1 h-12 px-2 bg-black hover:bg-neutral-800 text-white font-bold rounded-none shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-2 text-sm leading-none">
                        <i class="fa-solid fa-plus"></i>
                        Añadir
                        <i class="fa-solid fa-chevron-down text-[10px] ml-1 opacity-80"></i>
                    </button>
                </div>
                
                <!-- Dropdown Menú -->
                <div id="add-chapter-dropdown" class="hidden absolute top-full left-4 right-0 mt-1 bg-[var(--bg-app)] border border-[var(--border-color)] rounded-xl shadow-xl z-50 overflow-hidden text-sm">
                    <button onclick="createNewChapter(false); toggleAddChapterDropdown()" class="w-full text-left px-4 py-3 hover:bg-[var(--bg-sidebar)] transition flex items-center gap-3 text-[var(--text-main)] font-medium border-b border-[var(--border-color)]">
                        <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400 flex items-center justify-center">
                            <i class="fa-solid fa-file-lines"></i>
                        </div>
                        Nuevo Capítulo
                    </button>
                    <button onclick="createNewChapter(true, false); toggleAddChapterDropdown()" class="w-full text-left px-4 py-3 hover:bg-[var(--bg-sidebar)] transition flex items-center gap-3 text-[var(--text-main)] font-medium border-b border-[var(--border-color)]">
                        <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 flex items-center justify-center">
                            <i class="fa-solid fa-list-ol"></i>
                        </div>
                        Página de Índice
                    </button>
                    <button onclick="createNewChapter(false, true); toggleAddChapterDropdown()" class="w-full text-left px-4 py-3 hover:bg-[var(--bg-sidebar)] transition flex items-center gap-3 text-[var(--text-main)] font-medium">
                        <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400 flex items-center justify-center">
                            <i class="fa-solid fa-copyright"></i>
                        </div>
                        Página de Créditos
                    </button>
                    <button onclick="openDocumentImportModal(); toggleAddChapterDropdown()" class="w-full text-left px-4 py-3 hover:bg-[var(--bg-sidebar)] transition flex items-center gap-3 text-[var(--text-main)] font-medium border-t border-[var(--border-color)]">
                        <div class="w-8 h-8 rounded-lg bg-sky-100 text-sky-600 dark:bg-sky-900/30 dark:text-sky-400 flex items-center justify-center">
                            <i class="fa-solid fa-file-import"></i>
                        </div>
                        Upload Document
                    </button>
                </div>
            </div>

            <div id="sidebar-chapters-section" class="px-4 pb-4 flex flex-col flex-1 overflow-y-auto">
                <!-- Listado de Capítulos -->
                <div class="flex-1 mt-2">
                    <div class="flex items-center justify-between mb-3 px-2">
                        <span class="text-xs font-semibold uppercase tracking-wider text-[var(--text-muted)]">Índice de Capítulos</span>
                        <span id="chapter-count" class="text-xs bg-neutral-200 text-neutral-850 dark:bg-slate-800 dark:text-neutral-400 font-bold px-2 py-0.5 rounded-full">0</span>
                    </div>

                    <div id="chapters-list" class="divide-y divide-[var(--border-color)] space-y-0">
                        <!-- Generado dinámicamente -->
                    </div>
                </div>
            </div>

            <!-- Footer Sidebar con Información Adicional -->
            <div id="sidebar-footer" class="p-4 border-t border-[var(--border-color)] bg-[var(--bg-app)]/50">
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
            </div>
        </aside>

        <!-- CONTENEDOR PRINCIPAL DE CONTENIDOS -->
        <main id="almaden-editor-main" class="flex-1 flex overflow-hidden">
            
            <!-- PANEL DEL EDITOR (IZQUIERDO) -->
            <section id="editor-pane" class="flex-1 flex flex-col border-r border-[var(--border-color)] bg-[var(--bg-editor)] overflow-hidden transition-all relative min-h-0">
                <!-- Barra de Herramientas de Edición -->
                <div class="h-12 border-b border-[var(--border-color)] bg-[var(--bg-sidebar)] px-4 flex items-center justify-between text-[var(--text-muted)]">
                    <div class="flex items-center gap-0.5 sm:gap-1">
                        <div id="sidebar-toggle-toolbar-slot">
                            <button id="sidebar-toggle-btn" onclick="toggleSidebar()" class="w-7 h-7 flex items-center justify-center hover:bg-[var(--bg-app)] hover:text-[var(--text-main)] rounded transition border border-transparent" title="Ocultar capítulos" aria-expanded="true">
                                <i id="sidebar-toggle-icon" class="fa-solid fa-bars text-[13px]"></i>
                            </button>
                        </div>
                        <button onclick="wrapText('**', '**')" class="w-7 h-7 flex items-center justify-center hover:bg-[var(--bg-app)] hover:text-[var(--text-main)] rounded transition" title="Negrita">
                            <i class="fa-solid fa-bold text-[13px]"></i>
                        </button>
                        <button onclick="wrapText('*', '*')" class="w-7 h-7 flex items-center justify-center hover:bg-[var(--bg-app)] hover:text-[var(--text-main)] rounded transition" title="Itálica">
                            <i class="fa-solid fa-italic text-[13px]"></i>
                        </button>
                        <button onclick="wrapText('<u>', '</u>')" class="w-7 h-7 flex items-center justify-center hover:bg-[var(--bg-app)] hover:text-[var(--text-main)] rounded transition" title="Subrayado">
                            <i class="fa-solid fa-underline text-[13px]"></i>
                        </button>
                        <button onclick="openMediaUploader()" class="w-7 h-7 flex items-center justify-center hover:bg-[var(--bg-app)] hover:text-[var(--text-main)] rounded transition" title="Insertar Imagen">
                            <i class="fa-regular fa-image text-[13px]"></i>
                        </button>
                        
                        <div class="h-4 w-px bg-[var(--border-color)] mx-1"></div>
                        
                        <button onclick="addPrefix('# ')" class="w-7 h-7 flex items-center justify-center hover:bg-[var(--bg-app)] hover:text-[var(--text-main)] rounded transition" title="Título Principal">
                            <span class="font-bold text-[11px]">H1</span>
                        </button>
                        <button onclick="addPrefix('## ')" class="w-7 h-7 flex items-center justify-center hover:bg-[var(--bg-app)] hover:text-[var(--text-main)] rounded transition" title="Subtítulo">
                            <span class="font-bold text-[10px]">H2</span>
                        </button>
                        <button onclick="addPrefix('> ')" class="w-7 h-7 flex items-center justify-center hover:bg-[var(--bg-app)] hover:text-[var(--text-main)] rounded transition" title="Cita textual">
                            <i class="fa-solid fa-quote-left text-[11px]"></i>
                        </button>
                        <button onclick="addPrefix('- ')" class="w-7 h-7 flex items-center justify-center hover:bg-[var(--bg-app)] hover:text-[var(--text-main)] rounded transition" title="Lista de viñetas">
                            <i class="fa-solid fa-list-ul text-[12px]"></i>
                        </button>

                        <div class="h-4 w-px bg-[var(--border-color)] mx-1"></div>

                        <!-- Alineación -->
                        <div class="flex items-center gap-0.5">
                            <button onclick="wrapText('\n[align=left]\n', '\n[/align]\n')" class="w-7 h-7 flex items-center justify-center hover:bg-[var(--bg-app)] hover:text-[var(--text-main)] rounded transition" title="Alinear a la Izquierda">
                                <i class="fa-solid fa-align-left text-[12px]"></i>
                            </button>
                            <button onclick="wrapText('\n[align=center]\n', '\n[/align]\n')" class="w-7 h-7 flex items-center justify-center hover:bg-[var(--bg-app)] hover:text-[var(--text-main)] rounded transition" title="Centrar">
                                <i class="fa-solid fa-align-center text-[12px]"></i>
                            </button>
                            <button onclick="wrapText('\n[align=right]\n', '\n[/align]\n')" class="w-7 h-7 flex items-center justify-center hover:bg-[var(--bg-app)] hover:text-[var(--text-main)] rounded transition" title="Alinear a la Derecha">
                                <i class="fa-solid fa-align-right text-[12px]"></i>
                            </button>
                            <button onclick="wrapText('\n[align=justify]\n', '\n[/align]\n')" class="w-7 h-7 flex items-center justify-center hover:bg-[var(--bg-app)] hover:text-[var(--text-main)] rounded transition" title="Justificar">
                                <i class="fa-solid fa-align-justify text-[12px]"></i>
                            </button>
                        </div>

                        <div class="h-4 w-px bg-[var(--border-color)] mx-1"></div>

                        <!-- Tamaño de Fuente -->
                        <div class="flex items-center rounded border border-transparent hover:border-[var(--border-color)] transition focus-within:border-[var(--border-color)] focus-within:bg-[var(--bg-app)] h-7 px-1">
                            <input type="number" id="toolbar-font-size" value="16" min="8" max="72" class="w-8 bg-transparent text-[11px] text-center focus:outline-none" title="Tamaño de fuente">
                            <span class="text-[10px] text-slate-400 font-mono mr-1">px</span>
                            <button onclick="applyFontSize()" class="w-5 h-5 flex items-center justify-center text-black hover:bg-neutral-100 hover:text-black rounded transition" title="Aplicar tamaño al texto seleccionado">
                                <i class="fa-solid fa-check text-[9px]"></i>
                            </button>
                        </div>

                        <div class="h-4 w-px bg-[var(--border-color)] mx-1"></div>

                        <!-- Tipografía -->
                        <div class="flex items-center rounded border border-transparent hover:border-[var(--border-color)] transition focus-within:border-[var(--border-color)] focus-within:bg-[var(--bg-app)] h-7 px-1.5 max-w-[100px]">
                            <select id="toolbar-font-family" onchange="applyFontFamily(this.value)" class="w-full bg-transparent text-[11px] focus:outline-none appearance-none cursor-pointer text-ellipsis font-medium" title="Cambiar tipografía al texto seleccionado">
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
                                class="h-7 px-2 flex items-center justify-center gap-1.5 rounded hover:bg-[var(--bg-app)] hover:text-[var(--text-main)] transition text-[11px] font-semibold border border-transparent"
                                title="Aplicar idioma a texto seleccionado (para hyphenation)">
                                <i class="fa-solid fa-language text-[13px]"></i>
                                <span class="hidden sm:inline">Lang</span>
                                <i class="fa-solid fa-chevron-down text-[8px] opacity-60"></i>
                            </button>
                            <!-- Dropdown de idiomas -->
                            <div id="lang-dropdown" class="hidden absolute top-full left-0 mt-1 bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg shadow-xl z-50 py-1 min-w-[160px]">
                                <p class="text-[10px] text-[var(--text-muted)] px-3 pt-1 pb-1 uppercase tracking-wider font-semibold">Idioma del fragmento</p>
                                <button onclick="applyLanguage('es')" class="w-full text-left px-3 py-1.5 text-xs hover:bg-[var(--bg-app)] transition flex items-center gap-2">
                                    <span class="font-mono font-bold text-black dark:text-neutral-400">es</span> Español
                                </button>
                                <button onclick="applyLanguage('en')" class="w-full text-left px-3 py-1.5 text-xs hover:bg-[var(--bg-app)] transition flex items-center gap-2">
                                    <span class="font-mono font-bold text-black dark:text-neutral-400">en</span> English
                                </button>
                                <button onclick="applyLanguage('fr')" class="w-full text-left px-3 py-1.5 text-xs hover:bg-[var(--bg-app)] transition flex items-center gap-2">
                                    <span class="font-mono font-bold text-black dark:text-neutral-400">fr</span> Français
                                </button>
                                <button onclick="applyLanguage('de')" class="w-full text-left px-3 py-1.5 text-xs hover:bg-[var(--bg-app)] transition flex items-center gap-2">
                                    <span class="font-mono font-bold text-black dark:text-neutral-400">de</span> Deutsch
                                </button>
                                <button onclick="applyLanguage('pt')" class="w-full text-left px-3 py-1.5 text-xs hover:bg-[var(--bg-app)] transition flex items-center gap-2">
                                    <span class="font-mono font-bold text-black dark:text-neutral-400">pt</span> Português
                                </button>
                                <button onclick="applyLanguage('it')" class="w-full text-left px-3 py-1.5 text-xs hover:bg-[var(--bg-app)] transition flex items-center gap-2">
                                    <span class="font-mono font-bold text-black dark:text-neutral-400">it</span> Italiano
                                </button>
                                <div class="border-t border-[var(--border-color)] my-1"></div>
                                <button onclick="removeLanguage()" class="w-full text-left px-3 py-1.5 text-xs hover:bg-[var(--bg-app)] transition flex items-center gap-2 text-rose-500">
                                    <i class="fa-solid fa-xmark text-xs"></i> Quitar idioma
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Botón de guardado -->
                    <div class="text-xs font-semibold flex items-center gap-3">
                        <button onclick="saveStateToLocalStorage(true)" id="toolbar-save-btn" class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-900/30 dark:border-emerald-800/50 dark:text-emerald-400 hover:bg-emerald-100 dark:hover:bg-emerald-900/50 rounded transition flex items-center gap-1.5" title="Guardar cambios (Ctrl+S / Cmd+S)">
                            <i class="fa-solid fa-floppy-disk text-[10px]"></i>
                            <span>Guardar</span>
                        </button>
                    </div>
                </div>

                <!-- Campo de Entrada del Título del Capítulo -->
                <!-- Contenido centrado: Título + Textarea con max-width 800px -->
                <div class="flex-1 flex flex-col overflow-hidden relative min-h-0">
                    <div class="flex-1 overflow-y-auto">
                        <div class="max-w-[800px] mx-auto px-6 pt-6 pb-6 flex flex-col h-full min-h-0">
                            <!-- Título del capítulo y Configuración Local -->
                            <div class="flex items-center gap-3 mb-4 border-b-2 border-transparent focus-within:border-black transition-all pb-2">
                                <input id="chapter-title-input" type="text" placeholder="Título del Capítulo..."
                                    class="w-full bg-transparent font-serif font-semibold text-2xl md:text-3xl focus:outline-none text-[var(--text-main)]">
                                <button onclick="openChapterSettingsModal()" class="text-[var(--text-muted)] hover:text-black dark:hover:text-white transition-colors p-2 rounded-lg hover:bg-[var(--bg-sidebar)]" title="Configuración de este Capítulo">
                                    <i class="fa-solid fa-gear text-lg"></i>
                                </button>
                            </div>
                            <!-- Área de escritura -->
                            <div id="raw-editor-container" class="flex-1 w-full flex min-h-0">
                                <textarea data-editor-surface="raw"
                                    class="flex-1 w-full h-full min-h-0 resize-none bg-transparent text-[var(--text-main)] focus:outline-none font-mono text-sm leading-relaxed placeholder-gray-400 dark:placeholder-gray-600 focus:ring-0"
                                    style="min-height: 0;"
                                    placeholder="Escribe tu historia aquí utilizando formato simple o las herramientas de arriba..."></textarea>
                            </div>

                            <!-- Formulario de Créditos (Oculto por defecto) -->
                            <div id="credits-editor-container" class="hidden flex-1 w-full bg-transparent overflow-y-auto">
                                <div id="credits-editor-root" class="space-y-5 pb-6"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Barra inferior compacta del editor -->
                <div class="absolute bottom-0 left-0 right-0 h-6 z-10 border-t border-[var(--border-color)] bg-[var(--bg-sidebar)] px-4 flex items-center justify-between text-[10px] text-[var(--text-muted)] no-print">
                    <span class="uppercase tracking-[0.18em] font-semibold">Estado del capítulo</span>
                    <span class="font-semibold text-[var(--text-main)]">
                        <span id="current-word-count">0 palabras</span>
                    </span>
                </div>
            </section>

            <!-- PANEL DE VISTA PREVIA PDF (DERECHO) -->
            <section id="pdf-preview-pane" class="flex-1 flex flex-col pdf-page-container overflow-hidden transition-all">
                <!-- Barra informativa superior de página -->
                <div class="h-12 border-b border-[var(--border-color)] bg-[var(--bg-sidebar)] px-4 flex items-center justify-between text-xs text-[var(--text-muted)] no-print">
                    <span id="pdf-pane-mode-label" class="font-semibold uppercase tracking-wider flex items-center gap-1">
                        <i class="fa-solid fa-magnifying-glass-doc text-xs text-black dark:text-white"></i> Vista Previa
                    </span>
                    <div class="flex items-center gap-3">

                        <button id="btn-toggle-ruler" class="text-[var(--text-muted)] hover:text-black dark:hover:text-white transition-colors" title="Mostrar Regla" onclick="window.toggleRuler()">
                            <i class="fa-solid fa-ruler-horizontal"></i>
                        </button>
                        <button id="btn-toggle-spread" class="text-[var(--text-muted)] hover:text-black dark:hover:text-white transition-colors" title="Alternar Vista a Doble Página">
                            <i class="fa-solid fa-file-lines"></i>
                        </button>
                        <span id="pdf-page-indicator">0 Páginas</span>
                    </div>
                </div>

                <!-- Visor Scrollable de Páginas PDF -->
                <div id="pdf-ruler-wrapper" class="hidden w-full h-6 bg-white border-b border-gray-300 relative overflow-hidden pointer-events-none select-none shrink-0"><div id="pdf-ruler" class="absolute top-0 bottom-0 h-full"></div></div>
                <div id="pdf-scroller" class="flex-1 overflow-y-auto p-4 md:p-8 space-y-4 relative">
                    <!-- Contenido dinámico del PDF compilado por JS -->
                </div>
            </section>
        </main>
    </div>

    <!-- Modals -->
    <?php include plugin_dir_path( __FILE__ ) . 'editor-settings-modal.php'; ?>
    <?php include plugin_dir_path( __FILE__ ) . 'chapter-settings-modal.php'; ?>
    <?php include plugin_dir_path( __FILE__ ) . 'document-import-modal.php'; ?>

    <!-- VIEWPORT EDITOR DE IMAGEN -->
    <div id="image-viewport-modal" class="fixed inset-0 z-50 hidden opacity-0 bg-slate-900/60 backdrop-blur-sm transition-all duration-200 no-print" onclick="if (event.target === this) closeImageViewportModal();">
        <div data-image-viewport-panel class="absolute left-1/2 top-6 w-[min(920px,calc(100vw-1.5rem))] -translate-x-1/2 rounded-[28px] bg-white shadow-2xl border border-slate-200 scale-95 transition-transform duration-200 overflow-hidden flex flex-col max-h-[calc(100vh-1.5rem)]">
            <div class="px-6 py-5 border-b border-slate-200 flex items-center justify-between gap-4">
                <div>
                    <p class="text-[11px] uppercase tracking-[0.2em] text-slate-400 font-semibold">Viewport Editor</p>
                    <h3 id="image-viewport-title" class="text-2xl md:text-3xl font-extrabold tracking-tight text-slate-900">Agregar Imagen</h3>
                </div>
                <button type="button" onclick="closeImageViewportModal()" class="w-9 h-9 rounded-full border border-slate-200 text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition flex items-center justify-center" title="Cerrar">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="p-6 md:p-7 flex-1 overflow-y-auto">
                <div id="image-viewport-preview-frame" onclick="if (!window.imageViewportEditorState || !window.imageViewportEditorState.src) openImageMediaPicker()" class="h-[clamp(360px,52vh,620px)] rounded-[26px] border-2 border-dashed border-slate-300 bg-slate-50 overflow-hidden relative px-4 py-6 cursor-pointer">
                    <div id="image-viewport-preview-viewport" class="absolute left-1/2 top-1/2 overflow-hidden rounded-[18px] shadow-sm bg-white/30" style="transform: translate(-50%, -50%); width: 100%; height: auto;">
                        <img id="image-viewport-preview-image" class="hidden w-full h-full object-contain" alt="" />
                    </div>
                    <div id="image-viewport-empty-state" class="absolute inset-0 flex items-center justify-center text-center px-4 py-6">
                        <div class="max-w-sm">
                        <div class="mx-auto w-16 h-16 rounded-2xl bg-white border border-slate-200 shadow-sm flex items-center justify-center text-slate-400 mb-4">
                            <i class="fa-regular fa-image text-2xl"></i>
                        </div>
                        <p class="text-2xl font-semibold text-slate-900">Upload or select Image</p>
                        <p class="mt-2 text-sm text-slate-500">Agrega una imagen para convertirla en un bloque editable con viewport.</p>
                        </div>
                    </div>
                </div>

                <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    El ancho del bloque se ajusta automáticamente al content area. Solo se controla la altura y el encuadre interno de la imagen.
                </div>

                <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <div class="flex items-center justify-between gap-3 mb-3">
                        <label for="image-viewport-caption" class="text-sm font-bold text-slate-900">Descripción de la imagen</label>
                        <span id="image-viewport-caption-count" class="text-xs font-semibold text-slate-500">0/50 palabras</span>
                    </div>
                    <textarea id="image-viewport-caption" rows="3" maxlength="600" oninput="updateImageViewportControl('caption', this.value)" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 resize-none" placeholder="Describe brevemente lo que muestra la imagen."></textarea>
                    <p id="image-viewport-caption-warning" class="hidden mt-2 text-xs font-semibold text-rose-600">Máximo 50 palabras.</p>
                </div>

                <div class="mt-6 flex flex-wrap gap-3">
                    <button type="button" onclick="openImageMediaPicker()" class="px-4 py-2.5 rounded-xl bg-black text-white text-sm font-semibold hover:bg-neutral-800 transition flex items-center gap-2">
                        <i class="fa-solid fa-upload"></i>
                        <span>Upload / Select</span>
                    </button>
                    <button id="image-viewport-change-btn" type="button" onclick="openImageMediaPicker()" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-700 text-sm font-semibold hover:bg-slate-100 transition flex items-center gap-2">
                        <i class="fa-solid fa-rotate"></i>
                        <span>Change Image</span>
                    </button>
                    <button id="image-viewport-remove-btn" type="button" onclick="removeCurrentImageBlock()" class="px-4 py-2.5 rounded-xl border border-rose-200 text-rose-700 text-sm font-semibold hover:bg-rose-50 transition flex items-center gap-2">
                        <i class="fa-solid fa-trash"></i>
                        <span>Remove Image</span>
                    </button>
                    <button id="image-viewport-transform-btn" type="button" onclick="toggleImageViewportAdvancedControls()" class="px-4 py-2.5 rounded-xl border border-sky-200 text-sky-700 text-sm font-semibold hover:bg-sky-50 transition flex items-center gap-2">
                        <i class="fa-solid fa-sliders"></i>
                        <span id="image-viewport-transform-label">Transform</span>
                    </button>
                    <button id="image-viewport-save-btn" type="button" onclick="saveImageViewportState()" class="px-4 py-2.5 rounded-xl bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 transition flex items-center gap-2">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span id="image-viewport-save-label">Guardar</span>
                    </button>
                </div>

                <div id="image-viewport-controls" class="hidden mt-6 grid md:grid-cols-2 gap-5">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-sm font-bold text-slate-900">Zoom</h4>
                            <span id="image-viewport-zoom-value" class="text-xs font-semibold text-slate-500">1.00x</span>
                        </div>
                        <input id="image-viewport-zoom" type="range" min="0.5" max="2.5" step="0.01" value="1" oninput="updateImageViewportControl('zoom', this.value)" class="w-full accent-black">
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-sm font-bold text-slate-900">Viewport Height</h4>
                            <span id="image-viewport-height-value" class="text-xs font-semibold text-slate-500">100%</span>
                        </div>
                        <input id="image-viewport-height" type="range" min="30" max="100" step="1" value="100" oninput="updateImageViewportControl('viewportHeight', this.value)" class="w-full accent-black">
                        <p id="image-viewport-height-limit" class="mt-2 text-xs font-semibold text-slate-500">Límite calculado: 100%</p>
                        <p id="image-viewport-height-warning" class="hidden mt-2 text-xs font-semibold text-amber-700">El valor fue ajustado para evitar que el bloque cruce a otra página sin intención explícita.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- NOTIFICACIÓN FLOTANTE (TOAST) -->
    <div id="toast" class="fixed bottom-5 right-5 z-50 transform translate-y-10 opacity-0 pointer-events-none transition-all duration-300 bg-slate-900 text-white dark:bg-neutral-800 px-4 py-3 rounded-xl shadow-2xl flex items-center gap-3">
        <span id="toast-icon" class="text-emerald-400"><i class="fa-solid fa-circle-check"></i></span>
        <span id="toast-message" class="text-sm font-medium">Libro guardado con éxito</span>
    </div>

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
            bookAuthorLabel: <?php echo json_encode( $book_author_label ); ?>,
            bookAuthorsInputValue: <?php echo json_encode( $book_authors_input_value ); ?>,
            ajaxUrl: <?php echo json_encode( admin_url( 'admin-ajax.php' ) ); ?>,
            nonce: <?php echo json_encode( wp_create_nonce( 'almaden_save_book_nonce_' . $book_id ) ); ?>,
            settings: <?php echo json_encode( $pdf_settings ); ?>,
            settingsNonce: <?php echo json_encode( wp_create_nonce( 'almaden_save_settings_nonce_' . $book_id ) ); ?>,
            documentImportNonce: <?php echo json_encode( wp_create_nonce( 'almaden_document_import_nonce_' . $book_id ) ); ?>,
            installedFonts: <?php echo json_encode( $installed_fonts ); ?>,
            coverSettings: <?php echo json_encode( get_post_meta( $book_id, '_almaden_cover_settings', true ) ?: get_post_meta( $source_book_id, '_almaden_cover_settings', true ) ); ?>
        };
        window.bookState = bookState;
        window.PagedConfig = {
            auto: false,
            settings: {
                hyphenGlyph: '-'
            }
        };
    </script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/editor/editor-core.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/editor/editor-ui.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/editor/toolbar/toolbar-core.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/editor/toolbar/toolbar-text-formats.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/editor/toolbar/toolbar-image-block.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/editor/toolbar/toolbar-image-viewport-state.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/editor/toolbar/toolbar-image-viewport-ui.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/editor/toolbar/toolbar-parity.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/editor/editor-virtualization.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/editor/editor-chapters.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/editor/editor-settings-tabs.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/editor/editor-settings-fields.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/editor/editor-settings-credits-debug.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/editor/editor-settings-credits-constants.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/editor/editor-settings-credits-utils.js?v=' . time(), __FILE__ ) ); ?>"></script>

    <script src="<?php echo esc_url( plugins_url( '../../assets/js/editor/editor-settings-credits-state.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/editor/editor-settings-credits-ui.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/editor/editor-settings-credits-events.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/editor/editor-settings-credits.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/editor/editor-settings-templates.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/editor/editor-settings-api.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/editor/editor-chapter-settings.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/editor/document-import/import-state.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/editor/document-import/import-utils.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/editor/document-import/import-logic.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/editor/document-import/import-ui.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/editor/document-import/import-api.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/editor/document-import/import-app.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/almaden-shortcodes.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/editor/editor-markdown.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/editor/editor-visual-session.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/editor/editor-visual-selection.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/editor/editor-visual-editor.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/vendor/paged.polyfill.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/pdf/core/editor-pdf-safe-breaks.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/pdf/core/editor-pdf-chapter-flow.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/pdf/core/editor-pdf-single-chapter-rule.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/pdf/core/editor-pdf-compiler-parity.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/pdf/core/editor-pdf-dom.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/pdf/core/editor-pdf-semantic-blocks.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/pdf/core/editor-pdf-html-hyphenation.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/pdf/core/editor-pdf-html-images.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/pdf/core/editor-pdf-html-opening.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/pdf/core/editor-pdf-html.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/pdf/core/editor-pdf-compiler-dimensions.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/pdf/core/editor-pdf-compiler-spread.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/pdf/core/editor-pdf-compiler-map.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/pdf/core/editor-pdf-compiler-builder.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/pdf/core/editor-pdf-compiler.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/pdf/export/editor-pdf-export.js?v='   . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/pdf/styles/editor-pdf-styles-base.js?v='   . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/pdf/styles/editor-pdf-styles-chapters.js?v='   . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/pdf/styles/editor-pdf-styles-flow.js?v='   . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/pdf/styles/editor-pdf-styles-typography.js?v='   . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/pdf/styles/editor-pdf-styles-semantic.js?v='   . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/pdf/styles/editor-pdf-styles.js?v='   . time(), __FILE__ ) ); ?>"></script>
    <?php wp_footer(); ?>
</body>
</html>
