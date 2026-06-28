<div id="normal-chapter-settings" class="space-y-6">
    <div class="border-b border-[var(--border-color)] mb-4">
        <nav class="-mb-px flex gap-6" aria-label="Tabs">
            <button type="button" onclick="switchChapterTab('tab-structure')" id="btn-tab-structure" class="chapter-tab-btn whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-black dark:border-white text-black dark:text-white">Estructura</button>
            <button type="button" onclick="switchChapterTab('tab-opening')" id="btn-tab-opening" class="chapter-tab-btn whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-[var(--text-muted)] hover:text-[var(--text-main)] hover:border-gray-300">Apertura</button>
            <button type="button" onclick="switchChapterTab('tab-header-footer')" id="btn-tab-header-footer" class="chapter-tab-btn whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-[var(--text-muted)] hover:text-[var(--text-main)] hover:border-gray-300">Cabecera y Pie</button>
            <button type="button" onclick="switchChapterTab('tab-advanced')" id="btn-tab-advanced" class="chapter-tab-btn whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-[var(--text-muted)] hover:text-[var(--text-main)] hover:border-gray-300">Avanzado</button>
        </nav>
    </div>

    <div id="tab-structure" class="chapter-tab-content space-y-6 block">
        <div class="p-4 rounded-xl border border-[var(--border-color)] bg-[var(--bg-sidebar)]">
            <label class="block font-semibold mb-1">Arquitectura de apertura</label>
            <p class="text-xs text-[var(--text-muted)]">
                Define la estructura editorial previa al contenido: página intencional en blanco, imagen de capítulo o apertura continua.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-4">
            <div>
                <label class="block font-semibold mb-1">Página previa a la apertura</label>
                <p class="text-xs text-[var(--text-muted)] mb-2">Controla si este capítulo debe abrir con una página previa especial antes de la página de título/contenido.</p>
                <select id="chapter_opening_page_mode" name="opening_page_mode" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-3 py-2 text-[var(--text-main)] focus:outline-none focus:border-black dark:border-white" onchange="toggleOpeningPageControls()">
                    <option value="auto">Automático (compatibilidad actual)</option>
                    <option value="none">Sin página previa</option>
                    <option value="blank">Página en blanco intencional</option>
                    <option value="image">Página con imagen de capítulo</option>
                </select>
            </div>
        </div>

        <div id="chapter_opening_blank_intentional_wrapper" class="flex items-center justify-between p-4 bg-[var(--bg-sidebar)] rounded-xl border border-[var(--border-color)] hidden">
            <div>
                <label class="font-semibold block mb-1">Marcar blanco intencional</label>
                <span class="text-xs text-[var(--text-muted)]">Reserva este flag para marcar que la página previa vacía es una decisión editorial deliberada.</span>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" id="chapter_opening_blank_intentional" name="opening_blank_intentional" class="sr-only peer">
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-black"></div>
            </label>
        </div>

        <div id="chapter_opening_image_controls" class="grid grid-cols-1 gap-4 hidden">
            <div>
                <label class="block font-semibold mb-1">Imagen de apertura</label>
                <p class="text-xs text-[var(--text-muted)] mb-2">Selecciona la imagen que se mostrará en la página previa al capítulo.</p>
                <div class="flex items-center gap-2 mb-4">
                    <button type="button" onclick="openParityImageUploader()" class="px-4 py-2 bg-neutral-100 text-black dark:text-white hover:bg-neutral-200 dark:bg-neutral-800 dark:text-neutral-300 dark:hover:bg-neutral-700 border border-neutral-300 dark:border-neutral-700 rounded-lg text-sm font-semibold transition flex items-center gap-2">
                        <i class="fa-solid fa-upload"></i> Subir / Seleccionar Imagen
                    </button>
                </div>

                <label class="block font-semibold mb-1">Modo de imagen</label>
                <p class="text-xs text-[var(--text-muted)] mb-2">Define si la imagen se extiende hasta el borde, respeta el content box o usa tamaño personalizado.</p>
                <select id="chapter_parity_image_mode" name="parity_image_mode" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-3 py-2 text-[var(--text-main)] focus:outline-none focus:border-black dark:border-white" onchange="toggleParityImageSizeInputs()">
                    <option value="content">Full dentro del content box</option>
                    <option value="bleed">Full page con bleed</option>
                    <option value="custom">Ajustable por el usuario</option>
                </select>

                <div id="parity_image_custom_size" class="hidden grid-cols-2 gap-4 mt-3">
                    <div>
                        <label class="block text-[10px] font-semibold text-[var(--text-muted)] mb-1">Ancho (%)</label>
                        <input type="number" id="chapter_parity_image_width" name="parity_image_width" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-2 text-xs focus:outline-none focus:ring-2 focus:ring-black" placeholder="Ej: 100">
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold text-[var(--text-muted)] mb-1">Alto (%)</label>
                        <input type="number" id="chapter_parity_image_height" name="parity_image_height" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-2 text-xs focus:outline-none focus:ring-2 focus:ring-black" placeholder="Ej: 100">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="tab-opening" class="chapter-tab-content space-y-6 hidden">
        <div class="flex items-center justify-between p-4 bg-[var(--bg-sidebar)] rounded-xl border border-[var(--border-color)]">
            <div>
                <label class="font-semibold block mb-1">Mostrar bloque de apertura</label>
                <span class="text-xs text-[var(--text-muted)]">Controla el bloque estructural que contiene título, prefijo y subtítulo en la primera página del capítulo.</span>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" id="chapter_opening_block_enabled" name="opening_block_enabled" class="sr-only peer">
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-black"></div>
            </label>
        </div>

        <div class="flex items-center justify-between p-4 bg-[var(--bg-sidebar)] rounded-xl border border-[var(--border-color)]">
            <div>
                <label class="font-semibold block mb-1">Ocultar título del capítulo</label>
                <span class="text-xs text-[var(--text-muted)]">No muestra el título en el PDF. Ideal para prólogos, continuaciones o páginas especiales.</span>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" id="chapter_hide_title" name="hide_chapter_title" class="sr-only peer">
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-black"></div>
            </label>
        </div>

        <div class="grid grid-cols-1 gap-4">
            <div>
                <label class="block font-semibold mb-1">Subtítulo / Metadata</label>
                <p class="text-xs text-[var(--text-muted)] mb-2">Se mostrará debajo del título del capítulo.</p>
                <textarea id="chapter_subtitle_text" rows="2" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-3 py-2 text-[var(--text-main)] focus:outline-none focus:border-black dark:border-white" placeholder="Ej: Las memorias perdidas..."></textarea>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1">Tipografía</label>
                <select id="chapter_subtitle_font_family" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                    <option value="">Usar Global</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1">Alineación</label>
                <select id="chapter_subtitle_align" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                    <option value="left">Izquierda</option>
                    <option value="center">Centro</option>
                    <option value="right">Derecha</option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1">Tamaño (pt)</label>
                <input type="number" step="0.5" id="chapter_subtitle_font_size" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-black" placeholder="Ej: 12">
            </div>
            <div>
                <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1">Espaciado de Letras (px)</label>
                <input type="number" step="0.1" id="chapter_subtitle_letter_spacing" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-black" placeholder="Ej: 1.5">
            </div>
        </div>

        <div class="grid grid-cols-3 gap-4">
            <div>
                <label class="block text-[10px] font-semibold text-[var(--text-muted)] mb-1">Estilo</label>
                <select id="chapter_subtitle_font_style" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-2 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                    <option value="normal">Normal</option>
                    <option value="italic">Cursiva</option>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-semibold text-[var(--text-muted)] mb-1">Transformación</label>
                <select id="chapter_subtitle_text_transform" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-2 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                    <option value="none">Normal</option>
                    <option value="uppercase">MAYÚSCULAS</option>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-semibold text-[var(--text-muted)] mb-1">Peso</label>
                <select id="chapter_subtitle_font_weight" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-2 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                    <option value="normal">Normal (400)</option>
                    <option value="bold">Bold (700)</option>
                    <option value="300">Light (300)</option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-[10px] font-semibold text-[var(--text-muted)] mb-1">Margen Arriba (cm)</label>
                <input type="number" step="0.1" id="chapter_subtitle_margin_top" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-2 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-black" placeholder="Ej: 0.5">
            </div>
            <div>
                <label class="block text-[10px] font-semibold text-[var(--text-muted)] mb-1">Margen Abajo (cm)</label>
                <input type="number" step="0.1" id="chapter_subtitle_margin_bottom" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-2 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-black" placeholder="Ej: 1.0">
            </div>
        </div>
    </div>

    <div id="tab-header-footer" class="chapter-tab-content space-y-6 hidden">
        <div class="grid grid-cols-1 gap-4">
            <div>
                <label class="block font-semibold mb-1">Cabecera superior personalizada</label>
                <p class="text-xs text-[var(--text-muted)] mb-2">Sobrescribe el texto de la cabecera corrida, útil si el título completo es demasiado largo.</p>
                <input type="text" id="chapter_custom_running_header" name="custom_running_header" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-3 py-2 text-[var(--text-main)] focus:outline-none focus:border-black dark:border-white" placeholder="Ej: El misterio de la montaña...">
            </div>
        </div>

        <div class="p-4 bg-[var(--bg-sidebar)] rounded-xl border border-[var(--border-color)]">
            <div class="mb-3">
                <label class="font-semibold block mb-1">Contenido en la primera página</label>
                <span class="text-xs text-[var(--text-muted)]">Configura qué se muestra en la cabecera y pie de la primera página de este capítulo.</span>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-semibold text-[var(--text-muted)] mb-1">Cabecera</label>
                    <select id="chapter_first_page_header_type" name="first_page_header_type" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-2 text-xs focus:outline-none focus:ring-2 focus:ring-black" onchange="toggleChapterCustomFirstPageHeader()">
                        <option value="global">Usar Global</option>
                        <option value="blank">En blanco</option>
                        <option value="book_title">Título del Libro</option>
                        <option value="chapter_title">Título del Capítulo</option>
                        <option value="author">Autor</option>
                        <option value="page_number">Número de Página</option>
                        <option value="custom">Texto Personalizado</option>
                    </select>
                    <input type="text" id="chapter_first_page_header_custom" class="hidden mt-2 w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-2 text-xs focus:outline-none focus:ring-2 focus:ring-black" placeholder="Escribe aquí...">
                </div>
                <div>
                    <label class="block text-[10px] font-semibold text-[var(--text-muted)] mb-1">Pie de página</label>
                    <select id="chapter_first_page_footer_type" name="first_page_footer_type" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-2 text-xs focus:outline-none focus:ring-2 focus:ring-black" onchange="toggleChapterCustomFirstPageFooter()">
                        <option value="global">Usar Global</option>
                        <option value="blank">En blanco</option>
                        <option value="book_title">Título del Libro</option>
                        <option value="chapter_title">Título del Capítulo</option>
                        <option value="author">Autor</option>
                        <option value="page_number">Número de Página</option>
                        <option value="custom">Texto Personalizado</option>
                    </select>
                    <input type="text" id="chapter_first_page_footer_custom" class="hidden mt-2 w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-2 text-xs focus:outline-none focus:ring-2 focus:ring-black" placeholder="Escribe aquí...">
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between p-4 bg-[var(--bg-sidebar)] rounded-xl border border-[var(--border-color)]">
            <div>
                <label class="font-semibold block mb-1">Ocultar cabeceras y pies</label>
                <span class="text-xs text-[var(--text-muted)]">Desactiva cabecera y pie en todas las páginas del capítulo.</span>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" id="chapter_hide_all_headers_footers" name="hide_all_headers_footers" class="sr-only peer">
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-black"></div>
            </label>
        </div>
    </div>

    <div id="tab-advanced" class="chapter-tab-content space-y-6 hidden">
        <div class="flex items-center justify-between p-4 bg-[var(--bg-sidebar)] rounded-xl border border-[var(--border-color)]">
            <div>
                <label class="font-semibold block mb-1">Excluir de numeración de capítulos</label>
                <span class="text-xs text-[var(--text-muted)]">Quita este capítulo de la secuencia de prefijos tipo “Capítulo I, II, III...”.</span>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" id="chapter_exclude_from_numbering" name="exclude_from_numbering" class="sr-only peer">
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-black"></div>
            </label>
        </div>

        <div class="flex items-center justify-between p-4 bg-[var(--bg-sidebar)] rounded-xl border border-[var(--border-color)]">
            <div>
                <label class="font-semibold block mb-1">Letra capitular (Drop Cap)</label>
                <span class="text-xs text-[var(--text-muted)]">Aumenta drásticamente el tamaño de la primera letra del primer párrafo.</span>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" id="chapter_drop_cap_enabled" name="drop_cap_enabled" class="sr-only peer">
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-black"></div>
            </label>
        </div>

        <div class="flex items-center justify-between p-4 bg-[var(--bg-sidebar)] rounded-xl border border-[var(--border-color)]">
            <div>
                <label class="font-semibold block mb-1">Desactivar separación silábica</label>
                <span class="text-xs text-[var(--text-muted)]">Apaga la partición con hyphenation solo para este capítulo.</span>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" id="chapter_disable_hyphenation" name="disable_hyphenation" class="sr-only peer">
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-black"></div>
            </label>
        </div>

        <div class="p-4 rounded-xl border border-[var(--border-color)] bg-[var(--bg-sidebar)]">
            <label class="block font-semibold mb-1">Alineación vertical de la página 1</label>
            <p class="text-xs text-[var(--text-muted)]">Esta opción se define globalmente en Ajustes del Libro &gt; Capítulos y se aplica a todos los capítulos por igual.</p>
        </div>
    </div>
</div>
