<?php
require_once plugin_dir_path( dirname( __DIR__ ) ) . 'includes/helpers/editor-data-loader.php';
require_once dirname( __FILE__ ) . '/../../includes/helpers/cover-thumbnail.php';

$ebook_cover_html = function_exists( 'almaden_get_cover_thumbnail_html' ) ? almaden_get_cover_thumbnail_html( $book_id ) : '';
$wide_size = '1300px';
if ( function_exists( 'wp_get_global_settings' ) ) {
    $wide_size_val = wp_get_global_settings( array( 'layout', 'wideSize' ) );
    if ( ! empty( $wide_size_val ) ) {
        $wide_size = $wide_size_val;
    }
}

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
    <?php include dirname( __FILE__ ) . '/editor-app-head-extras.php'; ?>
</head>

<body class="theme-light h-full overflow-hidden flex flex-col bg-[var(--bg-app)] text-[var(--text-main)]">

    <!-- CABECERA PRINCIPAL -->
    <header class="h-16 border-b border-[var(--border-color)] px-4 flex items-center justify-between z-10 no-print transition-all" style="background-color: #f0f0f0;">
        <div class="flex items-center">
            <a href="<?php echo esc_url( almaden_bookster_get_creator_page_url() ); ?>" class="inline-flex h-10 w-10 items-center justify-center rounded-full text-[var(--text-muted)] hover:bg-[var(--bg-app)] hover:text-[var(--text-main)] transition-colors" title="Volver al Taller" aria-label="Volver al Taller">
                <svg aria-hidden="true" viewBox="0 0 24 24" class="h-7 w-7" focusable="false" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="9" />
                    <path d="M11.25 8.5 7.75 12l3.5 3.5" />
                    <path d="M8 12h8" />
                </svg>
            </a>
            <div class="w-0 shrink-0" aria-hidden="true"></div>
            <div class="flex items-center gap-3">
                <div class="bg-black text-white w-10 h-10 rounded-xl flex items-center justify-center shadow-lg shadow-black/30">
                    <i class="fa-solid fa-book-open text-lg"></i>
                </div>
                <div>
                    <input id="book-title-input" type="text" value="Mi Novela Inédita" 
                        class="bg-transparent font-bold text-lg focus:outline-none focus:ring-2 focus:ring-black rounded px-1 w-48 md:w-64 border-b border-transparent hover:border-dashed hover:border-gray-400 transition-all" 
                        title="Haz clic para renombrar el libro">
                </div>
            </div>
        </div>

        <!-- Opciones de Vista & Configuración -->
        <div class="flex items-center gap-4">
            <!-- Toggles de Visualización -->
            <div class="hidden md:flex bg-[var(--bg-app)] rounded-lg p-1 border border-[var(--border-color)] text-xs font-semibold">
                <button id="view-edit-btn" onclick="setViewMode('edit')" class="px-3 py-1.5 rounded-md text-[var(--text-muted)] hover:text-[var(--text-main)] transition">
                    Editor Raw
                </button>
                <button id="view-preview-btn" onclick="setViewMode('preview')" class="px-3 py-1.5 rounded-md text-[var(--text-muted)] hover:text-[var(--text-main)] transition">
                    Solo PDF
                </button>
                <button id="view-ebook-btn" onclick="setViewMode('ebook')" class="px-3 py-1.5 rounded-md text-[var(--text-muted)] hover:text-[var(--text-main)] transition">
                    Ebook
                </button>
                <button id="view-split-btn" onclick="setViewMode('split')" class="px-3 py-1.5 rounded-md bg-black text-white shadow-sm transition">
                    Dividido
                </button>
            </div>

            <button onclick="saveStateToLocalStorage(true)" id="toolbar-save-btn" class="hidden md:inline-flex px-3 py-2 bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-900/30 dark:border-emerald-800/50 dark:text-emerald-400 hover:bg-emerald-100 dark:hover:bg-emerald-900/50 rounded-lg transition items-center gap-2 text-sm font-semibold" title="Guardar cambios (Ctrl+S / Cmd+S)">
                <i class="fa-solid fa-floppy-disk text-sm"></i>
                <span>Guardar</span>
            </button>

            <!-- Botones de Acción -->
            <div class="flex gap-2">
                <button onclick="toggleSettingsModal(true)" class="p-2 border border-[var(--border-color)] hover:bg-[var(--bg-app)] rounded-lg text-[var(--text-muted)] hover:text-[var(--text-main)] transition" title="Configuración de Maquetación del PDF">
                    <i class="fa-solid fa-gear"></i>
                </button>
                <button id="btn-export-pdf" onclick="triggerPrint()" class="h-11 px-3 bg-black hover:bg-neutral-800 text-white text-sm font-semibold rounded-lg shadow-md hover:shadow-lg transition flex items-center gap-2 leading-none">
                    <i class="fa-solid fa-file-pdf text-sm"></i>
                    <span class="hidden sm:inline">Descargar PDF</span>
                </button>
            </div>
        </div>
    </header>

    <!-- CUERPO PRINCIPAL CONTENEDOR -->
    <div id="almaden-editor-shell" class="flex flex-1 overflow-hidden relative">
        <!-- BARRA LATERAL IZQUIERDA -->
        <aside id="sidebar" class="w-[250px] border-r border-[var(--border-color)] bg-[var(--bg-sidebar)] flex flex-col justify-between transition-all z-20 no-print h-full">
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

            <div id="sidebar-chapters-section" class="flex flex-col flex-1 overflow-y-auto">
                <!-- Listado de Capítulos -->
                <div class="flex-1">
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
                        <button id="editor-image-toolbar-btn" onclick="openMediaUploader()" class="w-7 h-7 flex items-center justify-center hover:bg-[var(--bg-app)] hover:text-[var(--text-main)] rounded transition" title="Insertar imagen">
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
                                <div class="flex items-center gap-1 shrink-0">
                                    <button onclick="openChapterSettingsModal()" class="text-[var(--text-muted)] hover:text-black dark:hover:text-white transition-colors p-2 rounded-lg hover:bg-[var(--bg-sidebar)]" title="Configuración de este Capítulo" aria-label="Configuración de este Capítulo">
                                        <i class="fa-solid fa-gear text-lg"></i>
                                    </button>
                                    <button onclick="openChapterNomenclatureGuideModal()" class="text-[var(--text-muted)] hover:text-black dark:hover:text-white transition-colors p-2 rounded-lg hover:bg-[var(--bg-sidebar)]" title="Guía de nomenclatura" aria-label="Guía de nomenclatura">
                                        <i class="fa-solid fa-circle-question text-lg"></i>
                                    </button>
                                </div>
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
                <div class="h-12 border-b border-[var(--border-color)] bg-[var(--bg-sidebar)] px-4 flex items-center justify-between gap-4 text-xs text-[var(--text-muted)] no-print">
                    <div class="flex min-w-0 items-center gap-3">
                        <button id="pdf-page-template-action" type="button" class="hidden h-7 items-center gap-1.5 rounded-md border border-amber-300 bg-amber-50 px-2.5 text-[10px] font-bold text-amber-900 hover:bg-amber-100 transition" onclick="window.almadenPageTemplateUI?.openModal()">
                            <i class="fa-solid fa-table-cells-large"></i>
                            <span>Aplicar plantilla</span>
                        </button>
                        <span id="pdf-geometry-indicator" class="hidden xl:inline text-[10px] font-medium tabular-nums"></span>
                    </div>
                    <div class="flex min-w-0 items-center gap-3">
                        <div id="pdf-preview-mode-chip" class="hidden lg:flex flex-col justify-center rounded-md border border-[var(--border-color)] bg-[var(--bg-app)] px-3 py-1.5 leading-tight shadow-sm">
                            <span class="text-[9px] font-semibold uppercase tracking-[0.18em] text-[var(--text-muted)]">Modo activo</span>
                            <span id="pdf-preview-mode-label" class="text-[11px] font-semibold text-[var(--text-main)]">Capítulo actual</span>
                            <span id="pdf-preview-mode-detail" class="text-[10px] text-[var(--text-muted)]">Vista optimizada para revisar el texto</span>
                        </div>
                        <span id="pdf-page-indicator" class="font-semibold text-[var(--text-main)]">0 Páginas</span>
                    </div>
                </div>

                <!-- Barra inferior de controles PDF -->
                <div class="min-h-12 h-auto py-1.5 border-t border-[var(--border-color)] bg-[var(--bg-sidebar)] px-4 flex items-center justify-between gap-3 text-xs text-[var(--text-muted)] no-print">
                    <div class="flex min-w-0 flex-wrap items-center gap-2 md:gap-3">
                        <button id="pdf-text-bounds-toggle" type="button" class="h-7 rounded-md border border-[var(--border-color)] bg-[var(--bg-app)] px-2 text-[11px] text-[var(--text-muted)] transition hover:text-[var(--text-main)]" aria-pressed="false" title="Mostrar límites del área de texto" aria-label="Mostrar límites del área de texto">
                            <i class="fa-solid fa-vector-square"></i>
                        </button>
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] font-semibold uppercase tracking-[0.18em] text-[var(--text-muted)]">Zoom</span>
                            <select id="pdf-preview-zoom" class="h-7 rounded-md border border-[var(--border-color)] bg-[var(--bg-app)] px-2 text-xs font-semibold text-[var(--text-main)] focus:outline-none focus:ring-2 focus:ring-black">
                                <option value="0.25">25%</option>
                                <option value="0.5">50%</option>
                                <option value="0.75" selected>75%</option>
                                <option value="1">100%</option>
                                <option value="2">200%</option>
                            </select>
                        </div>
                        <div class="flex items-center rounded-md border border-[var(--border-color)] bg-[var(--bg-app)] p-0.5 text-[10px] font-semibold">
                            <button id="btn-preview-mode-chapter" type="button" onclick="setPdfPreviewMode('chapter')" class="px-2.5 py-1 rounded-sm text-[var(--text-muted)] hover:text-[var(--text-main)] transition" aria-pressed="true" title="Vista por capítulo" aria-label="Vista por capítulo">
                                Capítulo
                            </button>
                            <button id="btn-preview-mode-full" type="button" onclick="setPdfPreviewMode('full')" class="px-2.5 py-1 rounded-sm text-[var(--text-muted)] hover:text-[var(--text-main)] transition" aria-pressed="false" title="Vista PDF completo" aria-label="Vista PDF completo">
                                Completo
                            </button>
                        </div>
                        <div class="flex items-center rounded-md border border-[var(--border-color)] bg-[var(--bg-app)] p-0.5 text-[10px] font-semibold">
                            <button id="btn-preview-layout-single" type="button" onclick="setPdfPreviewLayout('single')" class="px-2.5 py-1 rounded-sm text-[var(--text-muted)] hover:text-[var(--text-main)] transition" aria-pressed="true" title="Vista de una página" aria-label="Vista de una página">
                                <i class="fa-solid fa-file-lines"></i>
                            </button>
                            <button id="btn-toggle-spread" type="button" onclick="setPdfPreviewLayout('spread')" class="px-2.5 py-1 rounded-sm text-[var(--text-muted)] hover:text-[var(--text-main)] transition" aria-pressed="false" title="Vista de pliego, páginas pares a la izquierda e impares a la derecha" aria-label="Vista de pliego, páginas pares a la izquierda e impares a la derecha">
                                <i class="fa-solid fa-book-open"></i>
                            </button>
                        </div>
                        <button id="btn-toggle-ruler" class="hidden h-7 rounded-md border border-[var(--border-color)] bg-[var(--bg-app)] px-2 text-[11px] text-[var(--text-muted)] transition hover:text-[var(--text-main)]" title="Mostrar Regla" onclick="window.toggleRuler()">
                            <i class="fa-solid fa-ruler-horizontal"></i>
                        </button>
                    </div>
                    <span class="hidden lg:inline text-[10px] uppercase tracking-[0.18em] text-[var(--text-muted)]">Controles PDF</span>
                </div>

                <!-- Visor Scrollable de Páginas PDF -->
                <div id="pdf-ruler-wrapper" class="hidden w-full h-6 bg-white border-b border-gray-300 relative overflow-hidden pointer-events-none select-none shrink-0"><div id="pdf-ruler" class="absolute top-0 bottom-0 h-full"></div></div>
                <div id="pdf-scroller" class="flex-1 overflow-y-auto p-4 md:p-8 space-y-4 relative">
                    <!-- PDF binario compilado y verificado por Typst -->
                </div>
            </section>

            <!-- PANEL DE VISTA PREVIA EBOOK -->
            <section id="ebook-preview-pane" class="hidden flex-1 flex flex-col overflow-hidden transition-all bg-[#f6f3ed]">
                <div class="h-12 border-b border-[var(--border-color)] bg-[var(--bg-sidebar)] px-4 flex items-center justify-between gap-4 text-xs text-[var(--text-muted)] no-print">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="hidden lg:inline-flex rounded-md border border-[var(--border-color)] bg-[var(--bg-app)] px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-[var(--text-muted)]">
                            Vista Ebook
                        </span>
                        <span id="ebook-preview-chapter-count" class="truncate text-[11px] font-semibold text-[var(--text-main)]">Portada + capítulos</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" class="h-7 rounded-md border border-[var(--border-color)] bg-[var(--bg-app)] px-2 text-[11px] text-[var(--text-muted)] transition hover:text-[var(--text-main)]" onclick="window.showEbookIndexView?.()" title="Volver a la portada">
                            <i class="fa-solid fa-book-open mr-1"></i>
                            Portada
                        </button>
                    </div>
                </div>

                <div id="ebook-page-shell" class="flex-1 flex flex-col overflow-hidden w-full mx-auto" style="max-width: <?php echo esc_attr( $wide_size ); ?>;">
                    <div id="ebook-view-index" class="flex-1 flex flex-col md:flex-row overflow-hidden">
                        <div id="ebook-cover-panel" class="w-full md:w-1/2 h-1/2 md:h-full flex items-center md:items-start justify-center p-8 md:p-16 lg:p-24 border-b md:border-b-0 md:border-r border-gray-200">
                            <div id="ebook-cover-wrapper" class="w-full max-w-sm" style="box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);">
                                <?php echo ! empty( $ebook_cover_html ) ? $ebook_cover_html : '<div class="flex aspect-[3/4] items-center justify-center rounded-[1.5rem] border border-gray-200 bg-gradient-to-br from-stone-200 to-stone-100 text-neutral-500">Sin portada</div>'; ?>
                            </div>
                        </div>

                        <div id="ebook-index-panel" class="w-full md:w-1/2 h-1/2 md:h-full overflow-y-auto p-8 md:p-16 lg:p-24 relative bg-white">
                            <div class="flex justify-between items-center gap-4 mb-12">
                                <div class="min-w-0">
                                    <h2 id="ebook-book-title" class="text-2xl md:text-3xl font-bold text-gray-900 truncate"><?php echo esc_html( $book_title ); ?></h2>
                                    <p class="mt-2 text-sm text-gray-500 truncate"><?php echo esc_html( $book_author_label ?? '' ); ?></p>
                                </div>
                            </div>

                            <div class="space-y-1" id="ebook-chapters-list">
                                <!-- Chapters will be rendered here via JS -->
                            </div>
                        </div>
                    </div>

                    <div id="ebook-view-chapter" class="hidden flex-1 flex flex-col overflow-hidden">
                        <header id="ebook-chapter-navbar" class="w-full h-16 border-b border-gray-100 grid grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-3 px-6 backdrop-blur sticky top-0 z-50 transition-colors bg-white/95">
                            <button type="button" onclick="showEbookIndexView()" class="flex items-center text-gray-500 hover:text-black transition-colors font-medium shrink-0" title="Portada">
                                <i class="fa-solid fa-list-ul mr-2 text-sm"></i>
                            </button>
                            <h3 class="min-w-0 justify-self-center text-center text-sm font-semibold text-gray-800 truncate px-4 opacity-0 transition-opacity duration-300" id="ebook-chapter-nav-title">Chapter Title</h3>
                            <div class="flex items-center space-x-2 shrink-0">
                                <button type="button" onclick="showEbookPrevChapter()" id="ebook-btn-prev-chapter" class="px-3 py-2 rounded-full bg-transparent border border-gray-200 hover:bg-black/5 text-sm font-semibold text-gray-700 shadow-sm transition-colors hidden">
                                    <i class="fa-solid fa-chevron-left mr-2 text-xs"></i>Anterior
                                </button>
                                <button type="button" onclick="showEbookNextChapter()" id="ebook-btn-next-chapter" class="px-3 py-2 rounded-full bg-transparent border border-gray-200 hover:bg-black/5 text-sm font-semibold text-gray-700 shadow-sm transition-colors hidden">
                                    Siguiente<i class="fa-solid fa-chevron-right ml-2 text-xs"></i>
                                </button>
                            </div>
                        </header>

                        <main class="flex-1 overflow-y-auto p-6 md:p-12 relative" id="ebook-chapter-scroll-area">
                            <article id="ebook-chapter-content" class="prose mx-auto max-w-[700px]">
                                <!-- Markdown content will be rendered here -->
                            </article>
                        </main>
                    </div>
                </div>

                <div id="ebook-footnote-popup" class="fixed hidden z-50 bg-white border border-gray-200 shadow-xl rounded-lg p-4 text-sm text-gray-800 max-w-xs md:max-w-sm font-sans prose prose-sm transition-opacity duration-200 opacity-0 pointer-events-none" style="transform: translate(-50%, -100%); margin-top: -10px;">
                    <div id="ebook-footnote-popup-content"></div>
                    <div class="absolute w-3 h-3 bg-white border-b border-r border-gray-200 transform rotate-45 left-1/2 -ml-1.5 -bottom-1.5"></div>
                </div>
            </section>
        </main>
    </div>

    <!-- Modals -->
    <?php include plugin_dir_path( __FILE__ ) . 'editor-settings-modal.php'; ?>
    <?php include plugin_dir_path( __FILE__ ) . 'chapter-settings-modal.php'; ?>
    <?php include plugin_dir_path( __FILE__ ) . 'chapter-nomenclature-modal.php'; ?>
    <?php include plugin_dir_path( __FILE__ ) . 'document-import-modal.php'; ?>
    <?php include plugin_dir_path( __FILE__ ) . 'page-template-modal.php'; ?>
    <?php include plugin_dir_path( __FILE__ ) . 'image-viewport-modal.php'; ?>

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
            mediaPickerNonce: <?php echo json_encode( wp_create_nonce( 'almaden_bookster_media_picker_' . $book_id ) ); ?>,
            settings: <?php echo json_encode( $pdf_settings ); ?>,
            settingsNonce: <?php echo json_encode( wp_create_nonce( 'almaden_save_settings_nonce_' . $book_id ) ); ?>,
            documentImportNonce: <?php echo json_encode( wp_create_nonce( 'almaden_document_import_nonce_' . $book_id ) ); ?>,
            installedFonts: <?php echo json_encode( $installed_fonts ); ?>,
            coverSettings: <?php echo json_encode( get_post_meta( $book_id, '_almaden_cover_settings', true ) ?: get_post_meta( $source_book_id, '_almaden_cover_settings', true ) ); ?>,
            /*
             * Preview contract for the next PDF phases. This is intentionally
             * lightweight and serializable so autosave/localStorage keep it
             * alongside the rest of the book state.
             */
            pdfPreview: {
                mode: <?php echo json_encode( $pdf_settings['pdf_preview_mode'] ?? 'chapter' ); ?>,
                assetMode: <?php echo json_encode( $pdf_settings['pdf_preview_asset_mode'] ?? 'optimized' ); ?>,
                counterMode: <?php echo json_encode( $pdf_settings['pdf_preview_counter_mode'] ?? 'global' ); ?>,
                universalCounter: {
                    version: 1,
                    ready: false,
                    source: 'full-book',
                    totals: {
                        pages: null,
                        blankPages: null,
                        chapters: null
                    },
                    chapters: []
                }
            },
            commerce: <?php echo json_encode( array(
                'woocommerceActive' => ! empty( $woocommerce_status['active'] ),
                'woocommerceInstalled' => ! empty( $woocommerce_status['installed'] ),
                'relation' => $commerce_relation,
            ) ); ?>
        };
        bookState.chapters.forEach((chapter) => {
            chapter._lastSavedContent = String(chapter.content || '');
        });
        window.bookState = bookState;
        window.PagedConfig = {
            auto: false,
            settings: {
                hyphenGlyph: '-'
            }
        };
    </script>
    <?php include dirname( __FILE__ ) . '/editor-app-scripts.php'; ?>
    <?php wp_footer(); ?>
</body>
</html>
