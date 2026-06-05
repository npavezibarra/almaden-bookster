<!-- Modal de Configuración Local (Capítulo) -->
<div id="chapter-settings-modal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden z-50 flex items-center justify-center transition-opacity opacity-0">
    <div class="bg-[var(--bg-app)] border border-[var(--border-color)] w-full max-w-2xl rounded-2xl shadow-2xl overflow-hidden flex flex-col transform scale-95 transition-transform" style="max-height: 90vh;">
        
        <!-- Header -->
        <div class="px-6 py-4 border-b border-[var(--border-color)] flex justify-between items-center bg-[var(--bg-sidebar)]">
            <div>
                <h3 class="font-bold text-lg text-[var(--text-main)] flex items-center gap-2">
                    <i class="fa-solid fa-gear text-indigo-500"></i> Ajustes de este Capítulo
                </h3>
                <p class="text-xs text-[var(--text-muted)] mt-1">
                    Estas configuraciones sobrescriben las reglas globales solo para el capítulo actual.
                </p>
            </div>
            <button onclick="closeChapterSettingsModal()" class="text-[var(--text-muted)] hover:text-rose-500 transition-colors p-2">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>

        <!-- Formulario -->
        <div class="flex-1 overflow-y-auto p-6">
            <form id="chapter-settings-form" class="space-y-6 text-sm text-[var(--text-main)]">
                
                <!-- Inicio de Capítulo (Paridad) -->
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="block font-semibold mb-1">¿Dónde debe iniciar este capítulo?</label>
                        <p class="text-xs text-[var(--text-muted)] mb-2">Fuerza a que este capítulo comience en una página específica del libro impreso.</p>
                        <select id="chapter_start_parity" name="chapter_start_parity" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-3 py-2 text-[var(--text-main)] focus:outline-none focus:border-indigo-500">
                            <option value="any">Continuo / Cualquiera (Por defecto)</option>
                            <option value="odd">Página Impar (Lado Derecho)</option>
                            <option value="even">Página Par (Lado Izquierdo)</option>
                        </select>
                    </div>
                </div>

                <!-- Ajustes normales de capítulo -->
                <div id="normal-chapter-settings" class="space-y-6">
                    <!-- TABS NAVIGATION -->
                    <div class="border-b border-[var(--border-color)] mb-4">
                        <nav class="-mb-px flex gap-6" aria-label="Tabs">
                            <button type="button" onclick="switchChapterTab('tab-general')" id="btn-tab-general" class="chapter-tab-btn whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-indigo-500 text-indigo-600">General</button>
                            <button type="button" onclick="switchChapterTab('tab-subtitle')" id="btn-tab-subtitle" class="chapter-tab-btn whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-[var(--text-muted)] hover:text-[var(--text-main)] hover:border-gray-300">Subtítulo / Meta</button>
                            <button type="button" onclick="switchChapterTab('tab-design')" id="btn-tab-design" class="chapter-tab-btn whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-[var(--text-muted)] hover:text-[var(--text-main)] hover:border-gray-300">Imágenes y Diseño</button>
                        </nav>
                    </div>

                    <!-- TAB CONTENT: General -->
                    <div id="tab-general" class="chapter-tab-content space-y-6 block">
                <!-- Ocultar Título -->
                <div class="flex items-center justify-between p-4 bg-[var(--bg-sidebar)] rounded-xl border border-[var(--border-color)]">
                    <div>
                        <label class="font-semibold block mb-1">Ocultar Título del Capítulo</label>
                        <span class="text-xs text-[var(--text-muted)]">No muestra el título en el PDF. Ideal para prólogos o continuaciones.</span>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <label class="flex items-center gap-2 cursor-pointer group">
                            <div class="relative flex items-center">
                                <input type="checkbox" id="chapter_hide_title" name="hide_chapter_title" class="sr-only peer">
                                <div class="w-8 h-4 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                            </div>
                            <span class="text-[11px] font-medium text-[var(--text-main)] group-hover:text-indigo-600 transition-colors">Ocultar título en PDF</span>
                        </label>
                        
                        <label class="flex items-center gap-2 cursor-pointer group">
                            <div class="relative flex items-center">
                                <input type="checkbox" id="chapter_exclude_from_numbering" name="exclude_from_numbering" class="sr-only peer">
                                <div class="w-8 h-4 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                            </div>
                            <span class="text-[11px] font-medium text-[var(--text-main)] group-hover:text-indigo-600 transition-colors">Excluir de numeración</span>
                        </label>

                        <label class="flex items-center gap-2 cursor-pointer group col-span-2">
                            <div class="relative flex items-center">
                                <input type="checkbox" id="chapter_hide_all_headers_footers" name="hide_all_headers_footers" class="sr-only peer">
                                <div class="w-8 h-4 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                            </div>
                            <span class="text-[11px] font-medium text-[var(--text-main)] group-hover:text-indigo-600 transition-colors">Sin cabecera ni pie en todo el capítulo</span>
                        </label>
                    </div>
                </div>

                <!-- Cabecera Personalizada -->
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="block font-semibold mb-1">Cabecera Superior Personalizada</label>
                        <p class="text-xs text-[var(--text-muted)] mb-2">Sobrescribe el texto de la cabecera (útil si el título original es demasiado largo para caber).</p>
                        <input type="text" id="chapter_custom_running_header" name="custom_running_header" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-3 py-2 text-[var(--text-main)] focus:outline-none focus:border-indigo-500" placeholder="Ej: El misterio de la montaña...">
                    </div>
                </div>

                <!-- Letra Capitular (Drop Cap) -->
                <div class="flex items-center justify-between p-4 bg-[var(--bg-sidebar)] rounded-xl border border-[var(--border-color)]">
                    <div>
                        <label class="font-semibold block mb-1">Letra Capitular (Drop Cap)</label>
                        <span class="text-xs text-[var(--text-muted)]">Aumenta drásticamente el tamaño de la primera letra del primer párrafo.</span>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="chapter_drop_cap_enabled" name="drop_cap_enabled" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                    </label>
                </div>

                <!-- Desactivar Separación Silábica -->
                <div class="flex items-center justify-between p-4 bg-[var(--bg-sidebar)] rounded-xl border border-[var(--border-color)]">
                    <div>
                        <label class="font-semibold block mb-1">Desactivar Separación Silábica</label>
                        <span class="text-xs text-[var(--text-muted)]">Apaga los guiones (hyphens) al final de la línea solo para este capítulo.</span>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="chapter_disable_hyphenation" name="disable_hyphenation" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                    </label>
                </div>

                <!-- Alineación Vertical Página 1 -->
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="block font-semibold mb-1">Alineación Vertical de la Página 1</label>
                        <p class="text-xs text-[var(--text-muted)] mb-2">Ideal para crear separadores de "Partes" que van al centro de la hoja.</p>
                        <select id="chapter_page_one_vertical" name="chapter_page_one_vertical" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-3 py-2 text-[var(--text-main)] focus:outline-none focus:border-indigo-500">
                            <option value="top">Arriba (Por defecto)</option>
                            <option value="center">Centrado (Medio)</option>
                            <option value="bottom">Abajo</option>
                        </select>
                    </div>
                </div>


                    </div><!-- End tab-general -->

                    <!-- TAB CONTENT: Subtitle -->
                    <div id="tab-subtitle" class="chapter-tab-content space-y-6 hidden">
                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label class="block font-semibold mb-1">Subtítulo / Metadata</label>
                                <p class="text-xs text-[var(--text-muted)] mb-2">Se mostrará debajo del título del capítulo.</p>
                                <textarea id="chapter_subtitle_text" rows="2" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-3 py-2 text-[var(--text-main)] focus:outline-none focus:border-indigo-500" placeholder="Ej: Las memorias perdidas..."></textarea>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1">Tipografía</label>
                                <select id="chapter_subtitle_font_family" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <option value="">Usar Global</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1">Alineación</label>
                                <select id="chapter_subtitle_align" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <option value="center">Centro</option>
                                    <option value="left">Izquierda</option>
                                    <option value="right">Derecha</option>
                                    <option value="justify">Justificado</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1">Tamaño (pt)</label>
                                <input type="number" step="0.5" id="chapter_subtitle_font_size" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Ej: 12">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1">Espaciado de Letras (px)</label>
                                <input type="number" step="0.1" id="chapter_subtitle_letter_spacing" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Ej: 1.5">
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-[10px] font-semibold text-[var(--text-muted)] mb-1">Estilo</label>
                                <select id="chapter_subtitle_font_style" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-2 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <option value="normal">Normal</option>
                                    <option value="italic">Cursiva</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-semibold text-[var(--text-muted)] mb-1">Transformación</label>
                                <select id="chapter_subtitle_text_transform" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-2 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <option value="none">Normal</option>
                                    <option value="uppercase">MAYÚSCULAS</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-semibold text-[var(--text-muted)] mb-1">Peso</label>
                                <select id="chapter_subtitle_font_weight" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-2 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <option value="normal">Normal (400)</option>
                                    <option value="bold">Bold (700)</option>
                                    <option value="300">Light (300)</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-semibold text-[var(--text-muted)] mb-1">Margen Arriba (cm)</label>
                                <input type="number" step="0.1" id="chapter_subtitle_margin_top" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-2 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Ej: 0.5">
                            </div>
                            <div>
                                <label class="block text-[10px] font-semibold text-[var(--text-muted)] mb-1">Margen Abajo (cm)</label>
                                <input type="number" step="0.1" id="chapter_subtitle_margin_bottom" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-2 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Ej: 1.0">
                            </div>
                        </div>
                    </div><!-- End tab-subtitle -->

                    <!-- TAB CONTENT: Design -->
                    <div id="tab-design" class="chapter-tab-content space-y-6 hidden">
                
                <!-- Mostrar cabecera y pie en página 1 -->
                <div class="p-4 bg-[var(--bg-sidebar)] rounded-xl border border-[var(--border-color)]">
                    <div class="mb-3">
                        <label class="font-semibold block mb-1">Contenido en la Primera Página</label>
                        <span class="text-xs text-[var(--text-muted)]">Configura qué se muestra en la cabecera y pie de la primera página de este capítulo.</span>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-semibold text-[var(--text-muted)] mb-1">Cabecera</label>
                            <select id="chapter_first_page_header_type" name="first_page_header_type" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-2 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500" onchange="toggleChapterCustomFirstPageHeader()">
                                <option value="global" selected>Usar Global</option>
                                <option value="blank">En blanco</option>
                                <option value="book_title">Título del Libro</option>
                                <option value="chapter_title">Título del Capítulo</option>
                                <option value="author">Autor</option>
                                <option value="page_number">Número de Página</option>
                                <option value="custom">Texto Personalizado</option>
                            </select>
                            <input type="text" id="chapter_first_page_header_custom" class="hidden mt-2 w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-2 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Escribe aquí...">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-[var(--text-muted)] mb-1">Pie de página</label>
                            <select id="chapter_first_page_footer_type" name="first_page_footer_type" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-2 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500" onchange="toggleChapterCustomFirstPageFooter()">
                                <option value="global" selected>Usar Global</option>
                                <option value="blank">En blanco</option>
                                <option value="book_title">Título del Libro</option>
                                <option value="chapter_title">Título del Capítulo</option>
                                <option value="author">Autor</option>
                                <option value="page_number">Número de Página</option>
                                <option value="custom">Texto Personalizado</option>
                            </select>
                            <input type="text" id="chapter_first_page_footer_custom" class="hidden mt-2 w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-2 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Escribe aquí...">
                        </div>
                    </div>
                </div>

                <!-- Modo de Imagen de Paridad -->
                <div class="grid grid-cols-1 gap-4 pb-4">
                    <div>
                        <label class="block font-semibold mb-1">Imagen de Paridad (Página en Blanco)</label>
                        <p class="text-xs text-[var(--text-muted)] mb-2">Selecciona la imagen que se mostrará en la página opuesta al inicio del capítulo.</p>
                        <div class="flex items-center gap-2 mb-4">
                            <button type="button" onclick="openParityImageUploader()" class="px-4 py-2 bg-indigo-50 text-indigo-600 hover:bg-indigo-100 dark:bg-indigo-900/30 dark:text-indigo-400 dark:hover:bg-indigo-900/50 border border-indigo-200 dark:border-indigo-800 rounded-lg text-sm font-semibold transition flex items-center gap-2">
                                <i class="fa-solid fa-upload"></i> Subir / Seleccionar Imagen
                            </button>
                        </div>

                        <label class="block font-semibold mb-1">Modo de Imagen de Paridad</label>
                        <p class="text-xs text-[var(--text-muted)] mb-2">Define si la imagen que llena la página en blanco se extiende hasta el borde (Sangría) o respeta los márgenes.</p>
                        <select id="chapter_parity_image_mode" name="parity_image_mode" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-3 py-2 text-[var(--text-main)] focus:outline-none focus:border-indigo-500" onchange="toggleParityImageSizeInputs()">
                            <option value="content">Full dentro del content box</option>
                            <option value="bleed">Full page con 5mm bleed (3 lados)</option>
                            <option value="custom">Ajustable por el usuario</option>
                        </select>
                        <div id="parity_image_custom_size" class="hidden grid-cols-2 gap-4 mt-3">
                            <div>
                                <label class="block text-[10px] font-semibold text-[var(--text-muted)] mb-1">Ancho (%)</label>
                                <input type="number" id="chapter_parity_image_width" name="parity_image_width" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-2 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Ej: 100">
                            </div>
                            <div>
                                <label class="block text-[10px] font-semibold text-[var(--text-muted)] mb-1">Alto (%)</label>
                                <input type="number" id="chapter_parity_image_height" name="parity_image_height" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-2 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Ej: 100">
                            </div>
                        </div>
                    </div>
                </div>
                    </div><!-- End tab-design -->
                </div><!-- End normal-chapter-settings -->

                <!-- Ajustes exclusivos para la Tabla de Contenidos -->
                <div id="toc-chapter-settings" class="hidden space-y-6">
                    <div class="p-4 bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-200 dark:border-indigo-800 rounded-xl mb-4 text-indigo-800 dark:text-indigo-300">
                        <p class="text-sm font-semibold mb-1"><i class="fa-solid fa-circle-info mr-1"></i> Configuración de la Tabla de Contenidos</p>
                        <p class="text-xs">Estás editando el Índice. Configura aquí la tipografía y el estilo de la lista de capítulos.</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1">Tamaño de Fuente (px)</label>
                            <input type="number" step="0.5" id="chapter_toc_font_size" name="toc_font_size" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Ej: 11.5">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1">Enumerar Capítulos</label>
                            <select id="chapter_toc_enumerate" name="toc_enumerate" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="none">No enumerar</option>
                                <option value="decimal">Números (1., 2., 3.)</option>
                                <option value="roman">Números Romanos (I., II., III.)</option>
                                <option value="bullet">Bullet points (•)</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1">Tipografía</label>
                            <select id="chapter_toc_font_family" name="toc_font_family" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <!-- Se llenará dinámicamente con JS desde bookState.installedFonts -->
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1">Estilo de Fuente</label>
                            <select id="chapter_toc_font_style" name="toc_font_style" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="normal">Normal</option>
                                <option value="italic">Itálica (Cursiva)</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1">Grosor (Weight)</label>
                            <select id="chapter_toc_font_weight" name="toc_font_weight" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="normal">Normal (400)</option>
                                <option value="bold">Negrita (700)</option>
                                <option value="300">Light (300)</option>
                                <option value="500">Medium (500)</option>
                                <option value="600">Semi-Bold (600)</option>
                                <option value="800">Extra-Bold (800)</option>
                                <option value="900">Black (900)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1">Transformación</label>
                            <select id="chapter_toc_text_transform" name="toc_text_transform" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="none">Ninguna</option>
                                <option value="uppercase">MAYÚSCULAS (All Caps)</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1">Letter Spacing (px)</label>
                            <input type="number" step="0.1" id="chapter_toc_letter_spacing" name="toc_letter_spacing" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Ej: 0.5">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1">Interlineado (Line Height)</label>
                            <input type="number" step="0.1" id="chapter_toc_line_height" name="toc_line_height" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Ej: 1.8">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1">Estilo de la Línea (Leader)</label>
                            <select id="chapter_toc_leader_style" name="toc_leader_style" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="dotted">Punteada (Dotted)</option>
                                <option value="solid">Continua (Solid)</option>
                                <option value="dashed">A rayas (Dashed)</option>
                                <option value="none">Sin Línea (Espacio vacío)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1">Posición de la Línea</label>
                            <select id="chapter_toc_leader_position" name="toc_leader_position" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="middle">Centro (Middle)</option>
                                <option value="bottom">Base (Bottom)</option>
                            </select>
                        </div>
                    </div>

                    <!-- NUEVA SECCIÓN: Formato del Título del Índice -->
                    <div class="p-4 bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-200 dark:border-indigo-800 rounded-xl mb-4 text-indigo-800 dark:text-indigo-300 mt-8">
                        <p class="text-sm font-semibold mb-1"><i class="fa-solid fa-heading mr-1"></i> Formato del Título del Índice</p>
                        <p class="text-xs">Sobrescribe el formato global del título de capítulo exclusivamente para esta página.</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1">Alineación del Título</label>
                            <select id="chapter_toc_title_align" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">Usar Global</option>
                                <option value="left">Izquierda</option>
                                <option value="center">Centro</option>
                                <option value="right">Derecha</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1">Diseño Vertical</label>
                            <select id="chapter_toc_page_one_vertical" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">Usar Global</option>
                                <option value="top">Arriba (Margen estándar)</option>
                                <option value="half">Media Página (Centrado vertical)</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1">Familia de Fuente</label>
                            <select id="chapter_toc_title_font_family" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">Usar Global</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1">Tamaño (pt)</label>
                            <input type="number" step="0.5" id="chapter_toc_title_font_size" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Usar Global">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1">Estilo</label>
                            <select id="chapter_toc_title_font_style" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">Usar Global</option>
                                <option value="normal">Normal</option>
                                <option value="italic">Cursiva</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1">Transformación</label>
                            <select id="chapter_toc_title_text_transform" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">Usar Global</option>
                                <option value="none">Normal</option>
                                <option value="uppercase">ALL CAPS</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1">Peso</label>
                            <select id="chapter_toc_title_font_weight" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">Usar Global</option>
                                <option value="100">Thin (100)</option>
                                <option value="200">Extra Light (200)</option>
                                <option value="300">Light (300)</option>
                                <option value="normal">Normal (400)</option>
                                <option value="500">Medium (500)</option>
                                <option value="600">Semi Bold (600)</option>
                                <option value="bold">Bold (700)</option>
                                <option value="800">Extra Bold (800)</option>
                                <option value="900">Black (900)</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-[10px] font-semibold text-[var(--text-muted)] mb-1">Pad. Arriba (cm)</label>
                            <input type="number" step="0.1" id="chapter_toc_title_padding_top" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-2 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Global">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-[var(--text-muted)] mb-1">Pad. Abajo (cm)</label>
                            <input type="number" step="0.1" id="chapter_toc_title_padding_bottom" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-2 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Global">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-[var(--text-muted)] mb-1">Alt. Línea</label>
                            <input type="number" step="0.1" id="chapter_toc_title_line_height" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-2 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Global">
                        </div>
                    </div>

                </div><!-- End toc-chapter-settings -->

            </form>
        </div>

        <!-- Footer / Controles -->
        <div class="px-6 py-4 border-t border-[var(--border-color)] flex justify-end gap-3 bg-[var(--bg-sidebar)]">
            <button type="button" onclick="closeChapterSettingsModal()" class="px-4 py-2 rounded-lg text-sm font-semibold border border-[var(--border-color)] hover:bg-[var(--bg-app)] transition">
                Cancelar
            </button>
            <button type="button" onclick="saveChapterSettings()" class="px-4 py-2 rounded-lg text-sm font-semibold bg-indigo-600 text-white hover:bg-indigo-700 shadow-lg shadow-indigo-500/30 transition flex items-center gap-2">
                <i class="fa-solid fa-check"></i> Aplicar al Capítulo
            </button>
        </div>
        
    </div>
</div>
