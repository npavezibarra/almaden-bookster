<?php include plugin_dir_path( __FILE__ ) . 'settings-tabs/functions.php'; ?>
<!-- MODAL DE CONFIGURACIÓN DEL LIBRO -->
<div id="settings-modal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center hidden transition-all duration-300 opacity-0 no-print">
    <div class="bg-[var(--bg-sidebar)] border border-[var(--border-color)] text-[var(--text-main)] w-full max-w-xl rounded-2xl shadow-2xl p-6 relative transform scale-95 transition-transform duration-300">
        <!-- Header Modal -->
        <div class="flex items-center justify-between pb-3 border-b border-[var(--border-color)]">
            <div class="flex items-center gap-6">
                <h3 class="text-md font-bold flex items-center gap-2">
                    <i class="fa-solid fa-sliders text-indigo-500"></i> Ajustes del Libro
                </h3>
                <div class="flex bg-[var(--bg-app)] rounded-lg p-1 border border-[var(--border-color)]">
                    <button type="button" onclick="switchFormatTab('pdf')" id="btn-format-pdf" class="px-4 py-1 rounded-md bg-[var(--bg-sidebar)] shadow-sm border border-[var(--border-color)] text-xs font-bold text-indigo-600 transition-colors">PDF</button>
                    <button type="button" onclick="switchFormatTab('ebook')" id="btn-format-ebook" class="px-4 py-1 rounded-md text-xs font-bold text-[var(--text-muted)] hover:text-[var(--text-main)] transition-colors">EBOOK</button>
                </div>
            </div>
            <button onclick="toggleSettingsModal(false)" class="text-[var(--text-muted)] hover:text-[var(--text-main)] transition">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <div id="format-pdf-section">

        <!-- Navigation Tabs -->
        <div class="flex border-b border-[var(--border-color)] mb-4 -mx-6 px-6 overflow-x-auto gap-4 scrollbar-none">
            <button type="button" onclick="switchSettingTab('tab-page')" class="setting-tab-btn py-2 border-b-2 border-indigo-500 text-indigo-500 font-semibold text-xs transition focus:outline-none whitespace-nowrap" id="btn-tab-page">
                Página
            </button>
            <button type="button" onclick="switchSettingTab('tab-typography')" class="setting-tab-btn py-2 border-b-2 border-transparent text-[var(--text-muted)] hover:text-[var(--text-main)] font-semibold text-xs transition focus:outline-none whitespace-nowrap" id="btn-tab-typography">
                Tipografía
            </button>
            <button type="button" onclick="switchSettingTab('tab-header-footer')" class="setting-tab-btn py-2 border-b-2 border-transparent text-[var(--text-muted)] hover:text-[var(--text-main)] font-semibold text-xs transition focus:outline-none whitespace-nowrap" id="btn-tab-header-footer">
                Cabecera y Pie
            </button>
            <button type="button" onclick="switchSettingTab('tab-chapters')" class="setting-tab-btn py-2 border-b-2 border-transparent text-[var(--text-muted)] hover:text-[var(--text-main)] font-semibold text-xs transition focus:outline-none whitespace-nowrap" id="btn-tab-chapters">
                Capítulos
            </button>
        </div>

        <!-- Formulario Ajustes -->
        <div class="space-y-4 max-h-[55vh] overflow-y-auto pr-1">
            
            <!-- PESTAÑA 1: PÁGINA FÍSICA -->
            <?php include plugin_dir_path( __FILE__ ) . 'settings-tabs/tab-page.php'; ?>

            <!-- PESTAÑA 2: TIPOGRAFÍA -->
            <?php include plugin_dir_path( __FILE__ ) . 'settings-tabs/tab-typography.php'; ?>

            <!-- PESTAÑA 3: CABECERA Y PIE DE PÁGINA -->
            <div id="tab-header-footer" class="setting-tab-content space-y-5 hidden">
                <!-- SECCIÓN CABECERA -->
                <div class="border border-[var(--border-color)] rounded-xl p-4 bg-[var(--bg-app)] space-y-3 shadow-sm">
                    <h4 class="text-xs font-bold text-indigo-500 uppercase tracking-wider flex items-center gap-1.5 border-b border-[var(--border-color)] pb-2 mb-1">
                        <i class="fa-solid fa-window-maximize text-[10px]"></i> Configuración de Cabecera
                    </h4>
                    
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Familia de Fuente</label>
                            <select id="setting-header-font-family" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <?php almaden_render_font_options( $hf_default_fonts, $selector_fonts ); ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Tamaño de Fuente (pt)</label>
                            <input id="setting-header-font-size" type="number" step="0.1" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-4 gap-2">
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Peso Fuente</label>
                            <select id="setting-header-font-weight" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="normal">Normal</option>
                                <option value="bold">Negrita (Bold)</option>
                                <option value="300">Ligera (300)</option>
                                <option value="500">Mediana (500)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Estilo Fuente</label>
                            <select id="setting-header-font-style" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="normal">Normal</option>
                                <option value="italic">Itálica</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Letter Spacing (pt)</label>
                            <input id="setting-header-letter-spacing" type="number" step="0.1" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Alineación Texto</label>
                            <select id="setting-header-align" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="center">Centrado</option>
                                <option value="left">Izquierda</option>
                                <option value="right">Derecha</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 pt-1">
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Margen Superior (<span class="unit-label">cm</span>)</label>
                            <input id="setting-header-margin-top" type="number" step="0.01" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Margen Inferior (<span class="unit-label">cm</span>)</label>
                            <input id="setting-header-margin-bottom" type="number" step="0.01" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 pt-1 border-t border-[var(--border-color)]/50 mt-1">
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Contenido Página Par</label>
                            <select id="setting-header-even-type" onchange="toggleCustomHeaderFields()" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="book_title">Título del Libro</option>
                                <option value="chapter_title">Título del Capítulo</option>
                                <option value="custom">Texto personalizado</option>
                                <option value="none">Ninguno</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Contenido Página Impar</label>
                            <select id="setting-header-odd-type" onchange="toggleCustomHeaderFields()" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
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
                            <input id="setting-header-even-custom" type="text" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div id="custom-header-odd-container" class="hidden">
                            <label class="block text-[9px] text-[var(--text-muted)] mb-1">Texto Personalizado Impar</label>
                            <input id="setting-header-odd-custom" type="text" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN PIE DE PÁGINA -->
                <div class="border border-[var(--border-color)] rounded-xl p-4 bg-[var(--bg-app)] space-y-3 shadow-sm">
                    <h4 class="text-xs font-bold text-indigo-500 uppercase tracking-wider flex items-center gap-1.5 border-b border-[var(--border-color)] pb-2 mb-1">
                        <i class="fa-solid fa-window-minimize text-[10px]"></i> Configuración de Pie de Página
                    </h4>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Familia de Fuente</label>
                            <select id="setting-footer-font-family" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <?php almaden_render_font_options( $hf_default_fonts, $selector_fonts ); ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Tamaño de Fuente (pt)</label>
                            <input id="setting-footer-font-size" type="number" step="0.1" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-4 gap-2">
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Peso Fuente</label>
                            <select id="setting-footer-font-weight" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="normal">Normal</option>
                                <option value="bold">Negrita (Bold)</option>
                                <option value="300">Ligera (300)</option>
                                <option value="500">Mediana (500)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Estilo Fuente</label>
                            <select id="setting-footer-font-style" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="normal">Normal</option>
                                <option value="italic">Itálica</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Letter Spacing (pt)</label>
                            <input id="setting-footer-letter-spacing" type="number" step="0.1" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Alineación Texto</label>
                            <select id="setting-footer-align" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="center">Centrado</option>
                                <option value="left">Izquierda</option>
                                <option value="right">Derecha</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 pt-1">
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Margen Superior (<span class="unit-label">cm</span>)</label>
                            <input id="setting-footer-margin-top" type="number" step="0.01" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Margen Inferior (<span class="unit-label">cm</span>)</label>
                            <input id="setting-footer-margin-bottom" type="number" step="0.01" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 pt-1 border-t border-[var(--border-color)]/50 mt-1">
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Contenido Página Par</label>
                            <select id="setting-footer-even-type" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="page_number">Número de Página</option>
                                <option value="none">Ninguno</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Contenido Página Impar</label>
                            <select id="setting-footer-odd-type" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="page_number">Número de Página</option>
                                <option value="none">Ninguno</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- OPCIONES PRIMERA PÁGINA DEL CAPÍTULO -->
                <div class="px-2 pt-1 border-t border-[var(--border-color)] mt-4">
                    <label class="block text-[11px] font-bold text-[var(--text-main)] mb-3">CONTENIDO 1ª PÁG DEL CAPÍTULO</label>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-semibold text-[var(--text-muted)] mb-1">Contenido Cabecera</label>
                            <select id="setting-first-page-header-type" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500" onchange="toggleCustomFirstPageHeader()">
                                <option value="blank">En blanco</option>
                                <option value="book_title">Título del Libro</option>
                                <option value="chapter_title">Título del Capítulo</option>
                                <option value="author">Autor</option>
                                <option value="page_number">Número de Página</option>
                                <option value="custom">Texto Personalizado</option>
                            </select>
                            <input type="text" id="setting-first-page-header-custom" class="hidden mt-2 w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Escribe aquí...">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-[var(--text-muted)] mb-1">Contenido Pie</label>
                            <select id="setting-first-page-footer-type" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500" onchange="toggleCustomFirstPageFooter()">
                                <option value="blank">En blanco</option>
                                <option value="book_title">Título del Libro</option>
                                <option value="chapter_title">Título del Capítulo</option>
                                <option value="author">Autor</option>
                                <option value="page_number" selected>Número de Página</option>
                                <option value="custom">Texto Personalizado</option>
                            </select>
                            <input type="text" id="setting-first-page-footer-custom" class="hidden mt-2 w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Escribe aquí...">
                        </div>
                    </div>
                </div>
            </div>

            <!-- PESTAÑA 4: COMPORTAMIENTO DE CAPÍTULOS -->
            <?php include plugin_dir_path( __FILE__ ) . 'settings-tabs/tab-chapters.php'; ?>

        </div> <!-- Fin de scrollable area -->
        </div> <!-- Fin de format-pdf-section -->

        <div id="format-ebook-section" class="hidden">
            <!-- Navigation Tabs for Ebook -->
            <div class="flex border-b border-[var(--border-color)] mb-4 -mx-6 px-6 overflow-x-auto gap-4 scrollbar-none">
                <button type="button" onclick="switchEbookSettingTab('tab-ebook-theme')" class="ebook-setting-tab-btn py-2 border-b-2 border-indigo-500 text-indigo-500 font-semibold text-xs transition focus:outline-none whitespace-nowrap" id="btn-tab-ebook-theme">
                    Theme
                </button>
                <button type="button" onclick="switchEbookSettingTab('tab-ebook-fonts')" class="ebook-setting-tab-btn py-2 border-b-2 border-transparent text-[var(--text-muted)] hover:text-[var(--text-main)] font-semibold text-xs transition focus:outline-none whitespace-nowrap" id="btn-tab-ebook-fonts">
                    Fonts
                </button>
            </div>
            
            <div class="space-y-4 max-h-[48vh] overflow-y-auto pr-1">
                <!-- Ebook Theme Tab Content -->
                <div id="tab-ebook-theme" class="ebook-setting-tab-content space-y-4">
                    <div class="border border-[var(--border-color)] rounded-xl p-4 bg-[var(--bg-app)] space-y-3 shadow-sm">
                        <h4 class="text-xs font-bold text-indigo-500 uppercase tracking-wider flex items-center gap-1.5 border-b border-[var(--border-color)] pb-2 mb-1">
                            <i class="fa-solid fa-palette text-[10px]"></i> Apariencia del Lector Web (Ebook)
                        </h4>
                        
                        <div class="space-y-3">
                            <div>
                                <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Tipo de Fondo</label>
                                <select id="setting-ebook-bg-type" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500" onchange="toggleEbookBgType()">
                                    <option value="color">Color</option>
                                    <option value="image">Imagen</option>
                                </select>
                            </div>

                            <div id="ebook-bg-color-wrap">
                                <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Color de Fondo</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" id="setting-ebook-bg-color" value="#ffffff" class="w-8 h-8 rounded cursor-pointer border border-[var(--border-color)] p-0.5">
                                    <input type="text" id="setting-ebook-bg-color-text" value="#ffffff" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 uppercase" oninput="try { document.getElementById('setting-ebook-bg-color').value = this.value.toLowerCase(); } catch(e){}">
                                </div>
                            </div>

                            <div id="ebook-bg-image-wrap" class="hidden">
                                <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Imagen / Textura de Fondo</label>
                                <div class="flex items-center gap-2">
                                    <input type="text" id="setting-ebook-bg-image" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="URL de la imagen">
                                    <button type="button" class="px-3 py-1.5 bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-semibold rounded-lg transition" onclick="openMediaUploaderEbookBg()">
                                        <i class="fa-solid fa-upload"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <h4 class="text-xs font-bold text-indigo-500 uppercase tracking-wider flex items-center gap-1.5 border-b border-[var(--border-color)] pb-2 mb-1 mt-4">
                            <i class="fa-solid fa-image text-[10px]"></i> Fondo del Panel de Portada
                        </h4>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Tipo de Fondo</label>
                                <select id="setting-ebook-cover-panel-bg-type" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500" onchange="toggleCoverPanelBgType()">
                                    <option value="image">Imagen</option>
                                    <option value="color">Color</option>
                                </select>
                            </div>
                            
                            <div id="ebook-cover-panel-color-wrap" class="hidden">
                                <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Color de Fondo</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" id="setting-ebook-cover-panel-bg-color" value="#ffffff" class="w-8 h-8 rounded cursor-pointer border border-[var(--border-color)] p-0.5">
                                    <input type="text" id="setting-ebook-cover-panel-bg-color-text" value="transparent" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 uppercase" oninput="try { document.getElementById('setting-ebook-cover-panel-bg-color').value = this.value.toLowerCase(); } catch(e){}">
                                </div>
                            </div>

                            <div id="ebook-cover-panel-image-wrap">
                                <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Imagen de Fondo</label>
                                <div class="flex items-center gap-2">
                                    <input type="text" id="setting-ebook-cover-panel-bg-image" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="URL de la imagen o vacío para usar portada">
                                    <button type="button" class="px-3 py-1.5 bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-semibold rounded-lg transition" onclick="openMediaUploaderCoverPanel()">
                                        <i class="fa-solid fa-upload"></i>
                                    </button>
                                </div>
                                <p class="text-[9px] text-gray-400 mt-1">Déjalo vacío para usar la portada del libro por defecto.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ebook Fonts Tab Content -->
                <div id="tab-ebook-fonts" class="ebook-setting-tab-content space-y-4 hidden">
                    <div class="border border-[var(--border-color)] rounded-xl p-4 bg-[var(--bg-app)] space-y-3 shadow-sm">
                        <h4 class="text-xs font-bold text-indigo-500 uppercase tracking-wider flex items-center gap-1.5 border-b border-[var(--border-color)] pb-2 mb-1">
                            <i class="fa-solid fa-font text-[10px]"></i> Tipografía del Cuerpo (Ebook)
                        </h4>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Familia de Fuente</label>
                                <select id="setting-ebook-font-family-content" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <?php almaden_render_font_options( $default_fonts, $selector_fonts ); ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Tamaño de Fuente (px)</label>
                                <input id="setting-ebook-font-size-content" type="number" step="0.5" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Peso de Fuente</label>
                                <select id="setting-ebook-font-weight-content" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
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
                                <input id="setting-ebook-line-height-content" type="number" step="0.05" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3 pt-2">
                            <div class="flex items-center gap-2">
                                <input id="setting-ebook-text-align-justify" type="checkbox" class="rounded border-[var(--border-color)] text-indigo-600 focus:ring-indigo-500 bg-[var(--bg-sidebar)] h-4 w-4">
                                <label for="setting-ebook-text-align-justify" class="text-xs font-semibold text-[var(--text-muted)] cursor-pointer">Justificar texto</label>
                            </div>
                            <div class="flex items-center gap-2">
                                <input id="setting-ebook-hyphenation" type="checkbox" class="rounded border-[var(--border-color)] text-indigo-600 focus:ring-indigo-500 bg-[var(--bg-sidebar)] h-4 w-4">
                                <label for="setting-ebook-hyphenation" class="text-xs font-semibold text-[var(--text-muted)] cursor-pointer">Separación silábica (Guiones)</label>
                            </div>
                        </div>
                    </div>

                    <div class="border border-[var(--border-color)] rounded-xl p-4 bg-[var(--bg-app)] space-y-3 shadow-sm">
                        <h4 class="text-xs font-bold text-indigo-500 uppercase tracking-wider flex items-center gap-1.5 border-b border-[var(--border-color)] pb-2 mb-1">
                            <i class="fa-solid fa-heading text-[10px]"></i> Tipografía de Títulos (Ebook)
                        </h4>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Familia de Fuente</label>
                                <select id="setting-ebook-font-family-headings" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <?php almaden_render_font_options( $heading_default_fonts, $selector_fonts ); ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Tamaño de Fuente (px)</label>
                                <input id="setting-ebook-font-size-headings" type="number" step="0.5" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Peso de Fuente</label>
                                <select id="setting-ebook-font-weight-headings" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <option value="bold">Negrita (Bold)</option>
                                    <option value="normal">Normal</option>
                                    <option value="100">100 - Fino</option>
                                    <option value="300">300 - Ligero</option>
                                    <option value="500">500 - Medio</option>
                                    <option value="900">900 - Negro</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Interlineado (line-height)</label>
                                <input id="setting-ebook-line-height-headings" type="number" step="0.05" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Modal -->
        <div class="mt-4 flex justify-end gap-3 pt-3 border-t border-[var(--border-color)]">
            <button onclick="toggleSettingsModal(false)" class="px-4 py-2 border border-[var(--border-color)] text-[var(--text-muted)] hover:text-[var(--text-main)] hover:bg-[var(--bg-app)] text-xs font-semibold rounded-lg transition">
                Cancelar
            </button>
            <button onclick="savePDFSettings()" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-md hover:shadow-lg transition">
                Guardar Cambios
            </button>
        </div>
    </div>
</div>
