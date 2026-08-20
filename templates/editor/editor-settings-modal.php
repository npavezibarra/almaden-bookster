<?php include plugin_dir_path( __FILE__ ) . 'settings-tabs/functions.php'; ?>
<!-- MODAL DE CONFIGURACIÓN DEL LIBRO -->
<div id="settings-modal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center hidden transition-all duration-300 opacity-0 no-print">
    <div class="settings-dialog bg-[var(--bg-sidebar)] border border-[var(--border-color)] text-[var(--text-main)] w-full max-w-xl rounded-2xl shadow-2xl p-6 relative transform scale-95 transition-transform duration-300" role="dialog" aria-modal="true" aria-labelledby="settings-modal-title">
        <!-- Header Modal -->
        <div class="settings-header flex items-center justify-between pb-3 border-b border-[var(--border-color)]">
            <div class="settings-header-main flex items-center gap-6">
                <h3 id="settings-modal-title" class="settings-title text-md font-bold flex items-center gap-2">
                    <i class="fa-solid fa-sliders text-black dark:text-white" aria-hidden="true"></i> Ajustes del libro
                </h3>
                <div class="settings-format-tabs flex bg-[var(--bg-app)] rounded-lg p-1 border border-[var(--border-color)]" role="tablist" aria-label="Formato de ajustes">
                    <button type="button" role="tab" aria-selected="true" onclick="switchFormatTab('pdf')" id="btn-format-pdf" class="px-4 py-1 rounded-md bg-[var(--bg-sidebar)] shadow-sm border border-[var(--border-color)] text-xs font-bold text-black dark:text-white transition-colors">PDF</button>
                    <button type="button" role="tab" aria-selected="false" onclick="switchFormatTab('ebook')" id="btn-format-ebook" class="px-4 py-1 rounded-md text-xs font-bold text-[var(--text-muted)] hover:text-[var(--text-main)] transition-colors">eBook</button>
                    <button type="button" role="tab" aria-selected="false" onclick="switchFormatTab('global')" id="btn-format-global" class="px-4 py-1 rounded-md text-xs font-bold text-[var(--text-muted)] hover:text-[var(--text-main)] transition-colors">General</button>
                </div>
            </div>
            <button type="button" onclick="toggleSettingsModal(false)" class="settings-close text-[var(--text-muted)] hover:text-[var(--text-main)] transition" aria-label="Cerrar ajustes">
                <i class="fa-solid fa-xmark text-lg" aria-hidden="true"></i>
            </button>
        </div>

        <div id="format-pdf-section" class="settings-format-section">

        <!-- Navigation Tabs -->
        <div class="settings-primary-tabs flex border-b border-[var(--border-color)] mb-4 -mx-6 px-6 overflow-x-auto gap-4 scrollbar-none" role="tablist" aria-label="Secciones de PDF">
            <button type="button" onclick="switchSettingTab('tab-templates')" class="setting-tab-btn py-2 border-b-2 border-transparent text-[var(--text-muted)] hover:text-[var(--text-main)] font-semibold text-xs transition focus:outline-none whitespace-nowrap" id="btn-tab-templates">
                Plantillas
            </button>
            <button type="button" onclick="switchSettingTab('tab-page')" class="setting-tab-btn py-2 border-b-2 border-black dark:border-white text-black dark:text-white font-semibold text-xs transition focus:outline-none whitespace-nowrap" id="btn-tab-page">
                Página
            </button>
            <button type="button" onclick="switchSettingTab('tab-typography')" class="setting-tab-btn py-2 border-b-2 border-transparent text-[var(--text-muted)] hover:text-[var(--text-main)] font-semibold text-xs transition focus:outline-none whitespace-nowrap" id="btn-tab-typography">
                Tipografía
            </button>
            <button type="button" onclick="switchSettingTab('tab-header-footer')" class="setting-tab-btn py-2 border-b-2 border-transparent text-[var(--text-muted)] hover:text-[var(--text-main)] font-semibold text-xs transition focus:outline-none whitespace-nowrap" id="btn-tab-header-footer">
                Cabecera y pie
            </button>
            <button type="button" onclick="switchSettingTab('tab-footnotes')" class="setting-tab-btn py-2 border-b-2 border-transparent text-[var(--text-muted)] hover:text-[var(--text-main)] font-semibold text-xs transition focus:outline-none whitespace-nowrap" id="btn-tab-footnotes">
                Notas al pie
            </button>
            <button type="button" onclick="switchSettingTab('tab-chapters')" class="setting-tab-btn py-2 border-b-2 border-transparent text-[var(--text-muted)] hover:text-[var(--text-main)] font-semibold text-xs transition focus:outline-none whitespace-nowrap" id="btn-tab-chapters">
                Capítulos
            </button>
        </div>

        <!-- Formulario Ajustes -->
        <div class="settings-scroll-region space-y-4 max-h-[55vh] overflow-y-auto pr-1">
            
            <!-- PESTAÑA: PLANTILLAS -->
            <div id="tab-templates" class="setting-tab-content space-y-5 hidden">
                <nav class="settings-inner-tabs" role="tablist" aria-label="Tipos de plantillas">
                    <button type="button" id="book-template-tab-system" data-book-template-group="system" role="tab" aria-selected="true" class="book-template-subtab header-footer-tab-btn is-active">
                        Estándar <span id="book-template-count-system" class="ml-1 opacity-70">0</span>
                    </button>
                    <button type="button" id="book-template-tab-personal" data-book-template-group="personal" role="tab" aria-selected="false" class="book-template-subtab header-footer-tab-btn">
                        Mis plantillas <span id="book-template-count-personal" class="ml-1 opacity-70">0</span>
                    </button>
                </nav>

                <section class="settings-section-card">
                    <h4><i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i> Plantillas de libro</h4>
                    <div class="settings-section-card-body space-y-3">
                    <div id="templates-container" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Renderizado por JS -->
                        <div class="text-[10px] text-[var(--text-muted)] italic"><i class="fa-solid fa-spinner fa-spin mr-1"></i> Cargando plantillas de libro...</div>
                    </div>
                    
                    <div class="mt-4 pt-4 border-t border-[var(--border-color)]">
                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button" id="save-book-template-btn" onclick="promptSaveCurrentAsBookTemplate()" class="text-[11px] font-semibold text-black dark:text-white bg-transparent border border-black dark:border-white hover:bg-neutral-100 dark:hover:bg-neutral-800 rounded px-3 py-1.5 transition inline-flex items-center gap-2">
                                <i class="fa-solid fa-plus"></i>
                                Crear plantilla con estos ajustes
                            </button>
                            <button type="button" onclick="promptUploadBookTemplate()" class="text-[11px] font-semibold text-black dark:text-white bg-transparent border border-black dark:border-white hover:bg-neutral-100 dark:hover:bg-neutral-800 rounded px-3 py-1.5 transition inline-flex items-center gap-2">
                                <i class="fa-solid fa-file-arrow-up"></i>
                                Importar JSON
                            </button>
                            <input id="book-template-upload-input" type="file" accept=".json,application/json" class="hidden" onchange="handleBookTemplateUpload(event)">
                        </div>
                        <div id="book-template-save-status" class="mt-2 text-[10px] text-[var(--text-muted)] hidden"></div>
                    </div>
                    </div>
                </section>
            </div>

            <!-- PESTAÑA 1: PÁGINA FÍSICA -->
            <?php include plugin_dir_path( __FILE__ ) . 'settings-tabs/tab-page.php'; ?>

            <!-- PESTAÑA 2: TIPOGRAFÍA -->
            <?php include plugin_dir_path( __FILE__ ) . 'settings-tabs/tab-typography.php'; ?>

            <!-- PESTAÑA 3: CABECERA Y PIE DE PÁGINA -->
            <div id="tab-header-footer" class="setting-tab-content space-y-5 hidden">
                <nav class="settings-inner-tabs" role="tablist" aria-label="Ajustes de cabecera y pie">
                    <button type="button" role="tab" aria-controls="header-footer-header-panel" id="btn-header-footer-header" class="header-footer-tab-btn is-active" onclick="switchHeaderFooterTab('header')" aria-selected="true">
                        Cabecera
                    </button>
                    <button type="button" role="tab" aria-controls="header-footer-footer-panel" id="btn-header-footer-footer" class="header-footer-tab-btn" onclick="switchHeaderFooterTab('footer')" aria-selected="false">
                        Pie de página
                    </button>
                </nav>

                <div id="header-footer-header-panel" class="header-footer-tab-panel space-y-5" role="tabpanel" aria-labelledby="btn-header-footer-header">
                <!-- SECCIÓN CABECERA -->
                <div class="border border-[var(--border-color)] rounded-xl p-4 bg-[var(--bg-app)] space-y-3 shadow-sm">
                    <h4 class="text-xs font-bold text-black dark:text-white uppercase tracking-wider flex items-center gap-1.5 border-b border-[var(--border-color)] pb-2 mb-1">
                        <i class="fa-solid fa-window-maximize text-[10px]"></i> Configuración de cabecera
                    </h4>
                    
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Familia de Fuente</label>
                            <select id="setting-header-font-family" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                                <?php almaden_render_font_options( $hf_default_fonts, $selector_fonts ); ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Tamaño de Fuente (pt)</label>
                            <input id="setting-header-font-size" type="number" step="0.1" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                    </div>

                    <div class="grid grid-cols-5 gap-1.5">
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Peso Fuente</label>
                            <select id="setting-header-font-weight" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                                <option value="normal">Normal</option>
                                <option value="bold">Negrita (Bold)</option>
                                <option value="300">Ligera (300)</option>
                                <option value="500">Mediana (500)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Estilo Fuente</label>
                            <select id="setting-header-font-style" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                                <option value="normal">Normal</option>
                                <option value="italic">Itálica</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Transformación</label>
                            <select id="setting-header-text-transform" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                                <option value="none">Normal</option>
                                <option value="uppercase">MAYÚSCULAS</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Espaciado entre letras (pt)</label>
                            <input id="setting-header-letter-spacing" type="number" step="0.1" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Alineación Texto</label>
                            <select id="setting-header-align" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                                <option value="center">Centrado</option>
                                <option value="outer">Exterior</option>
                            </select>
                        </div>
                    </div>

                    <label class="flex items-center gap-2 rounded-lg border border-[var(--border-color)] bg-[var(--bg-app)] px-3 py-2 text-xs font-semibold text-[var(--text-main)] mt-1">
                        <input type="checkbox" id="setting-header-hyphenate" class="h-3.5 w-3.5 rounded border-[var(--border-color)] text-black focus:ring-black">
                        <span>Separación silábica</span>
                    </label>

                    <div class="grid grid-cols-2 gap-3 pt-1">
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Desde borde superior (<span class="unit-label">cm</span>)</label>
                            <input id="setting-header-margin-top" type="number" step="0.1" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Separación bajo cabecera (<span class="unit-label">cm</span>)</label>
                            <input id="setting-header-margin-bottom" type="number" step="0.1" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 pt-1 border-t border-[var(--border-color)]/50 mt-1">
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Contenido Página Par</label>
                            <select id="setting-header-even-type" onchange="toggleCustomHeaderFields()" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                                <option value="book_title">Título del Libro</option>
                                <option value="chapter_title">Título del Capítulo</option>
                                <option value="custom">Texto personalizado</option>
                                <option value="none">Ninguno</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Contenido Página Impar</label>
                            <select id="setting-header-odd-type" onchange="toggleCustomHeaderFields()" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                                <option value="chapter_title">Título del Capítulo</option>
                                <option value="book_title">Título del Libro</option>
                                <option value="custom">Texto personalizado</option>
                                <option value="none">Ninguno</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 mt-1">
                        <div id="custom-header-even-container" class="hidden">
                            <label class="block text-[9px] text-[var(--text-muted)] mb-1">Texto Personalizado Par</label>
                            <input id="setting-header-even-custom" type="text" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                        <div id="custom-header-odd-container" class="hidden">
                            <label class="block text-[9px] text-[var(--text-muted)] mb-1">Texto Personalizado Impar</label>
                            <input id="setting-header-odd-custom" type="text" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                    </div>
                </div>

                <div class="settings-subsection border border-[var(--border-color)] rounded-xl p-4 bg-[var(--bg-app)] space-y-3">
                    <h4>Cabecera en la primera página del capítulo</h4>
                    <label class="flex items-center gap-2 rounded-xl border border-[var(--border-color)] bg-[var(--bg-sidebar)] px-3 py-2 font-semibold text-[var(--text-main)]">
                        <input type="checkbox" id="setting-first-page-header-show" class="h-3.5 w-3.5 rounded border-[var(--border-color)] text-black focus:ring-black" checked>
                        <span>Incluir cabecera</span>
                    </label>
                    <div>
                        <label class="block font-semibold text-[var(--text-muted)] mb-1">Contenido de la cabecera</label>
                        <select id="setting-first-page-header-type" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black" onchange="toggleCustomFirstPageHeader()">
                            <option value="blank">En blanco</option>
                            <option value="book_title">Título del libro</option>
                            <option value="chapter_title">Título del capítulo</option>
                            <option value="author">Autor</option>
                            <option value="page_number">Número de página</option>
                            <option value="custom">Texto personalizado</option>
                        </select>
                        <input type="text" id="setting-first-page-header-custom" class="hidden mt-2 w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black" placeholder="Escribe aquí...">
                    </div>
                </div>
                </div>

                <div id="header-footer-footer-panel" class="header-footer-tab-panel space-y-5 hidden" role="tabpanel" aria-labelledby="btn-header-footer-footer">
                <!-- SECCIÓN PIE DE PÁGINA -->
                <div class="border border-[var(--border-color)] rounded-xl p-4 bg-[var(--bg-app)] space-y-3 shadow-sm">
                    <h4 class="text-xs font-bold text-black dark:text-white uppercase tracking-wider flex items-center gap-1.5 border-b border-[var(--border-color)] pb-2 mb-1">
                        <i class="fa-solid fa-window-minimize text-[10px]"></i> Configuración de pie de página
                    </h4>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Familia de Fuente</label>
                            <select id="setting-footer-font-family" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                                <?php almaden_render_font_options( $hf_default_fonts, $selector_fonts ); ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Tamaño de Fuente (pt)</label>
                            <input id="setting-footer-font-size" type="number" step="0.1" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                    </div>

                    <div class="grid grid-cols-5 gap-1.5">
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Peso Fuente</label>
                            <select id="setting-footer-font-weight" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                                <option value="normal">Normal</option>
                                <option value="bold">Negrita (Bold)</option>
                                <option value="300">Ligera (300)</option>
                                <option value="500">Mediana (500)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Estilo Fuente</label>
                            <select id="setting-footer-font-style" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                                <option value="normal">Normal</option>
                                <option value="italic">Itálica</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Transformación</label>
                            <select id="setting-footer-text-transform" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                                <option value="none">Normal</option>
                                <option value="uppercase">MAYÚSCULAS</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Espaciado entre letras (pt)</label>
                            <input id="setting-footer-letter-spacing" type="number" step="0.1" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Alineación Texto</label>
                            <select id="setting-footer-align" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                                <option value="center">Centrado</option>
                                <option value="outer">Exterior</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 pt-1">
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Separación sobre pie (<span class="unit-label">cm</span>)</label>
                            <input id="setting-footer-margin-top" type="number" step="0.1" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Hasta borde inferior (<span class="unit-label">cm</span>)</label>
                            <input id="setting-footer-margin-bottom" type="number" step="0.1" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 pt-1 border-t border-[var(--border-color)]/50 mt-1">
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Contenido Página Par</label>
                            <select id="setting-footer-even-type" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                                <option value="page_number">Número de Página</option>
                                <option value="none">Ninguno</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Contenido Página Impar</label>
                            <select id="setting-footer-odd-type" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                                <option value="page_number">Número de Página</option>
                                <option value="none">Ninguno</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="settings-subsection border border-[var(--border-color)] rounded-xl p-4 bg-[var(--bg-app)] space-y-3">
                    <h4>Pie en la primera página del capítulo</h4>
                    <label class="flex items-center gap-2 rounded-xl border border-[var(--border-color)] bg-[var(--bg-sidebar)] px-3 py-2 font-semibold text-[var(--text-main)]">
                        <input type="checkbox" id="setting-first-page-footer-show" class="h-3.5 w-3.5 rounded border border-[var(--border-color)] text-black focus:ring-black" checked>
                        <span>Incluir pie de página</span>
                    </label>
                    <div>
                        <label class="block font-semibold text-[var(--text-muted)] mb-1">Contenido del pie</label>
                        <select id="setting-first-page-footer-type" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black" onchange="toggleCustomFirstPageFooter()">
                            <option value="blank">En blanco</option>
                            <option value="book_title">Título del libro</option>
                            <option value="chapter_title">Título del capítulo</option>
                            <option value="author">Autor</option>
                            <option value="page_number" selected>Número de página</option>
                            <option value="custom">Texto personalizado</option>
                        </select>
                        <input type="text" id="setting-first-page-footer-custom" class="hidden mt-2 w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black" placeholder="Escribe aquí...">
                    </div>
                </div>
                </div>
            </div>

            <!-- PESTAÑA 4: FOOTNOTES -->
            <?php include plugin_dir_path( __FILE__ ) . 'settings-tabs/tab-footnotes.php'; ?>

            <!-- PESTAÑA 5: COMPORTAMIENTO DE CAPÍTULOS -->
            <?php include plugin_dir_path( __FILE__ ) . 'settings-tabs/tab-chapters.php'; ?>

        </div> <!-- Fin de scrollable area -->
        </div> <!-- Fin de format-pdf-section -->

        <div id="format-ebook-section" class="settings-format-section hidden">
            <!-- Navigation Tabs for Ebook -->
            <div class="settings-primary-tabs flex border-b border-[var(--border-color)] mb-4 -mx-6 px-6 overflow-x-auto gap-4 scrollbar-none" role="tablist" aria-label="Secciones de eBook">
                <button type="button" onclick="switchEbookSettingTab('tab-ebook-theme')" class="ebook-setting-tab-btn py-2 border-b-2 border-black dark:border-white text-black dark:text-white font-semibold text-xs transition focus:outline-none whitespace-nowrap" id="btn-tab-ebook-theme">
                    Apariencia
                </button>
                <button type="button" onclick="switchEbookSettingTab('tab-ebook-typography')" class="ebook-setting-tab-btn py-2 border-b-2 border-transparent text-[var(--text-muted)] hover:text-[var(--text-main)] font-semibold text-xs transition focus:outline-none whitespace-nowrap" id="btn-tab-ebook-typography">
                    Tipografía
                </button>
                <button type="button" onclick="switchEbookSettingTab('tab-ebook-chapters')" class="ebook-setting-tab-btn py-2 border-b-2 border-transparent text-[var(--text-muted)] hover:text-[var(--text-main)] font-semibold text-xs transition focus:outline-none whitespace-nowrap" id="btn-tab-ebook-chapters">
                    Capítulos
                </button>
            </div>
            
            <div class="settings-scroll-region space-y-4 max-h-[48vh] overflow-y-auto pr-1">
                <!-- Ebook Theme Tab Content -->
                <div id="tab-ebook-theme" class="ebook-setting-tab-content space-y-4">
                    <div class="border border-[var(--border-color)] rounded-xl p-4 bg-[var(--bg-app)] space-y-3 shadow-sm">
                        <h4 class="text-xs font-bold text-black dark:text-white uppercase tracking-wider flex items-center gap-1.5 border-b border-[var(--border-color)] pb-2 mb-1">
                            <i class="fa-solid fa-palette text-[10px]"></i> Apariencia del Lector Web (Ebook)
                        </h4>
                        
                        <div class="space-y-3">
                            <div>
                                <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Tipo de Fondo</label>
                                <select id="setting-ebook-bg-type" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black" onchange="toggleEbookBgType()">
                                    <option value="color">Color</option>
                                    <option value="image">Imagen</option>
                                </select>
                            </div>

                            <div id="ebook-bg-color-wrap">
                                <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Color de Fondo</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" id="setting-ebook-bg-color" value="#ffffff" class="w-8 h-8 rounded cursor-pointer border border-[var(--border-color)] p-0.5">
                                    <input type="text" id="setting-ebook-bg-color-text" value="#ffffff" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black uppercase" oninput="try { document.getElementById('setting-ebook-bg-color').value = this.value.toLowerCase(); } catch(e){}">
                                </div>
                            </div>

                            <div id="ebook-bg-image-wrap" class="hidden">
                                <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Imagen / Textura de Fondo</label>
                                <div class="flex items-center gap-2">
                                    <input type="text" id="setting-ebook-bg-image" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black" placeholder="URL de la imagen">
                                    <button type="button" class="px-3 py-1.5 bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-semibold rounded-lg transition" onclick="openMediaUploaderEbookBg()">
                                        <i class="fa-solid fa-upload"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="mt-2">
                                <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Opacidad del Fondo</label>
                                <div class="flex items-center gap-2">
                                    <input type="range" id="setting-ebook-bg-opacity" min="0" max="1" step="0.05" value="1" class="w-full h-1.5 bg-gray-200 rounded-lg appearance-none cursor-pointer" oninput="document.getElementById('ebook-bg-opacity-val').innerText = Math.round(this.value * 100) + '%'">
                                    <span id="ebook-bg-opacity-val" class="text-[10px] text-gray-500 w-8 text-right font-medium">100%</span>
                                </div>
                            </div>
                        </div>
                        <h4 class="text-xs font-bold text-black dark:text-white uppercase tracking-wider flex items-center gap-1.5 border-b border-[var(--border-color)] pb-2 mb-1 mt-4">
                            <i class="fa-solid fa-image text-[10px]"></i> Fondo del Panel de Portada
                        </h4>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Tipo de Fondo</label>
                                <select id="setting-ebook-cover-panel-bg-type" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black" onchange="toggleCoverPanelBgType()">
                                    <option value="image">Imagen</option>
                                    <option value="color">Color</option>
                                </select>
                            </div>
                            
                            <div id="ebook-cover-panel-color-wrap" class="hidden">
                                <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Color de Fondo</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" id="setting-ebook-cover-panel-bg-color" value="#ffffff" class="w-8 h-8 rounded cursor-pointer border border-[var(--border-color)] p-0.5">
                                    <input type="text" id="setting-ebook-cover-panel-bg-color-text" value="transparent" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black uppercase" oninput="try { document.getElementById('setting-ebook-cover-panel-bg-color').value = this.value.toLowerCase(); } catch(e){}">
                                </div>
                            </div>

                            <div id="ebook-cover-panel-image-wrap">
                                <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Imagen de Fondo</label>
                                <div class="flex items-center gap-2">
                                    <input type="text" id="setting-ebook-cover-panel-bg-image" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black" placeholder="URL de la imagen o vacío para usar portada">
                                    <button type="button" class="px-3 py-1.5 bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-semibold rounded-lg transition" onclick="openMediaUploaderCoverPanel()">
                                        <i class="fa-solid fa-upload"></i>
                                    </button>
                                </div>
                                <p class="text-[9px] text-gray-400 mt-1">Déjalo vacío para usar la portada del libro por defecto.</p>
                            </div>
                            <div class="mt-2">
                                <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Opacidad del Fondo</label>
                                <div class="flex items-center gap-2">
                                    <input type="range" id="setting-ebook-cover-panel-bg-opacity" min="0" max="1" step="0.05" value="1" class="w-full h-1.5 bg-gray-200 rounded-lg appearance-none cursor-pointer" oninput="document.getElementById('ebook-cover-panel-bg-opacity-val').innerText = Math.round(this.value * 100) + '%'">
                                    <span id="ebook-cover-panel-bg-opacity-val" class="text-[10px] text-gray-500 w-8 text-right font-medium">100%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ebook Typography Tab Content -->
                <div id="tab-ebook-typography" class="ebook-setting-tab-content space-y-4 hidden">
                    <div class="border border-[var(--border-color)] rounded-xl p-4 bg-[var(--bg-app)] space-y-3 shadow-sm">
                        <h4 class="text-xs font-bold text-black dark:text-white uppercase tracking-wider flex items-center gap-1.5 border-b border-[var(--border-color)] pb-2 mb-1">
                            <i class="fa-solid fa-font text-[10px]"></i> Tipografía del Cuerpo (Ebook)
                        </h4>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Familia de Fuente</label>
                                <select id="setting-ebook-font-family-content" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                                    <?php almaden_render_font_options( $default_fonts, $selector_fonts ); ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Tamaño de Fuente (px)</label>
                                <input id="setting-ebook-font-size-content" type="number" step="0.5" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Peso de Fuente</label>
                                <select id="setting-ebook-font-weight-content" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                                    <option value="normal">Normal</option>
                                    <option value="bold">Negrita (Bold)</option>
                                    <option value="100">100 - Fino</option>
                                    <option value="300">300 - Ligero</option>
                                    <option value="500">500 - Medio</option>
                                    <option value="900">900 - Negro</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Interlineado (line-height)</label>
                                <input id="setting-ebook-line-height-content" type="number" step="0.05" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3 pt-2">
                            <div class="flex items-center gap-2">
                                <input id="setting-ebook-text-align-justify" type="checkbox" class="rounded border-[var(--border-color)] text-black dark:text-white focus:ring-black bg-[var(--bg-sidebar)] h-4 w-4">
                                <label for="setting-ebook-text-align-justify" class="text-xs font-semibold text-[var(--text-muted)] cursor-pointer">Justificar texto</label>
                            </div>
                            <div class="flex items-center gap-2">
                                <input id="setting-ebook-hyphenation" type="checkbox" class="rounded border-[var(--border-color)] text-black dark:text-white focus:ring-black bg-[var(--bg-sidebar)] h-4 w-4">
                                <label for="setting-ebook-hyphenation" class="text-xs font-semibold text-[var(--text-muted)] cursor-pointer">Separación silábica (Guiones)</label>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Ebook Chapters Tab Content -->
                <?php include plugin_dir_path( __FILE__ ) . 'settings-tabs/tab-ebook-chapters.php'; ?>

            </div>
        </div>

        <div id="format-global-section" class="settings-format-section hidden">
            <div class="settings-primary-tabs flex items-center gap-2 border-b border-[var(--border-color)]" role="tablist" aria-label="Secciones generales">
                <button type="button" id="btn-global-info" onclick="switchGlobalSettingsInnerTab('format-global-info-section')" class="global-setting-tab-btn px-3 py-2 text-[10px] font-semibold border-b-2 border-black text-black transition">
                    Información del libro
                </button>
                <button type="button" id="btn-global-product" onclick="switchGlobalSettingsInnerTab('format-global-product-section')" class="global-setting-tab-btn px-3 py-2 text-[10px] font-semibold border-b-2 border-transparent text-[var(--text-muted)] hover:text-[var(--text-main)] transition">
                    Producto
                </button>
            </div>

            <div class="settings-scroll-region settings-global-body">
            <div id="format-global-info-section" class="py-4 border-b border-[var(--border-color)]">
                <label class="block text-[10px] font-bold uppercase tracking-wider text-[var(--text-muted)] mb-2">Idioma base del libro</label>
                <select id="setting-book-language" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-2 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                    <option value="es">Español</option>
                    <option value="en">English</option>
                    <option value="fr">Français</option>
                    <option value="de">Deutsch</option>
                    <option value="pt">Português</option>
                    <option value="it">Italiano</option>
                    <option value="la">Latín</option>
                </select>
                <p class="mt-2 text-[10px] leading-5 text-[var(--text-muted)]">Este valor define el idioma base para EPUB, PDF impreso y cualquier texto sin marca de idioma explícita. Los fragmentos con <code>&lt;foreign lang=&quot;...&quot;&gt;</code> siguen su propio idioma.</p>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-[var(--text-muted)] mb-2">Autores del libro</label>
                <?php $book_authors_input_value = isset( $book_authors_input_value ) ? $book_authors_input_value : ''; ?>
                <textarea id="setting-book-authors" rows="2" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-2 text-sm focus:outline-none focus:ring-2 focus:ring-black resize-none" placeholder="Escribe emails o usuarios separados por coma"><?php echo esc_textarea( $book_authors_input_value ); ?></textarea>
                <p class="mt-2 text-[10px] leading-5 text-[var(--text-muted)]">Usa correos o nombres de usuario para vincular autores reales. La lista visible del libro se sigue mostrando con nombres legibles.</p>
            </div>

            <div id="format-global-product-section" class="hidden py-4">
                <?php if ( ! empty( $woocommerce_status['active'] ) ) : ?>
                    <?php include plugin_dir_path( __FILE__ ) . 'settings-tabs/tab-commerce.php'; ?>
                <?php else : ?>
                    <div class="border border-[var(--border-color)] rounded-xl p-4 bg-[var(--bg-app)] shadow-sm text-sm text-[var(--text-muted)]">
                        WooCommerce no está activo en este sitio. La configuración de producto se habilita cuando WooCommerce está instalado y activo.
                    </div>
                <?php endif; ?>
            </div>
            </div>
        </div>

        <!-- Footer Modal -->
        <div class="settings-footer mt-4 flex justify-end gap-3 pt-3 border-t border-[var(--border-color)]">
            <button onclick="toggleSettingsModal(false)" class="px-4 py-2 border border-[var(--border-color)] text-[var(--text-muted)] hover:text-[var(--text-main)] hover:bg-[var(--bg-app)] text-xs font-semibold rounded-lg transition">
                Cancelar
            </button>
            <button id="btn-save-settings" onclick="savePDFSettings()" class="px-5 py-2 bg-black hover:bg-neutral-800 text-white text-xs font-semibold rounded-lg shadow-md hover:shadow-lg transition">
                Guardar cambios
            </button>
        </div>
    </div>
</div>
<script>
    window.almadenBookTemplatesNonce = <?php echo json_encode( wp_create_nonce( 'almaden_book_templates_library' ) ); ?>;
</script>
