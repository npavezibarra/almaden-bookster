<div id="normal-chapter-settings" class="space-y-6">
    <div class="border-b border-[var(--border-color)] mb-4">
        <nav class="-mb-px flex gap-6" aria-label="Tabs">
            <button type="button" onclick="switchChapterTab('chapter-tab-structure')" id="btn-chapter-tab-structure" class="chapter-tab-btn whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-black dark:border-white text-black dark:text-white">Estructura</button>
            <button type="button" onclick="switchChapterTab('chapter-tab-opening')" id="btn-chapter-tab-opening" class="chapter-tab-btn whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-[var(--text-muted)] hover:text-[var(--text-main)] hover:border-gray-300">Apertura</button>
            <button type="button" onclick="switchChapterTab('chapter-tab-header-footer')" id="btn-chapter-tab-header-footer" class="chapter-tab-btn whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-[var(--text-muted)] hover:text-[var(--text-main)] hover:border-gray-300">Cabecera y Pie</button>
            <button type="button" onclick="switchChapterTab('chapter-tab-advanced')" id="btn-chapter-tab-advanced" class="chapter-tab-btn whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-[var(--text-muted)] hover:text-[var(--text-main)] hover:border-gray-300">Avanzado</button>
        </nav>
    </div>

    <div id="chapter-tab-structure" class="chapter-tab-content space-y-6 block">
        <div class="grid grid-cols-1 gap-4 p-4 rounded-xl border border-[var(--border-color)] bg-[var(--bg-sidebar)]">
            <div>
                <label id="chapter-settings-modal-start-label" class="block font-semibold mb-1">¿Dónde debe iniciar el contenido de este capítulo?</label>
                <p id="chapter-settings-modal-start-subtitle" class="text-xs text-[var(--text-muted)] mb-2">Define el lado donde empieza el contenido. La apertura se configura aparte en la sección "Apertura".</p>
                <select id="chapter_start_parity" name="chapter_start_parity" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-3 py-2 text-[var(--text-main)] focus:outline-none focus:border-black" onchange="toggleChapterImageSettingsForChapter()">
                    <option value="any">Continuo / Cualquiera (Por defecto)</option>
                    <option value="odd">Página Impar (Lado Derecho)</option>
                    <option value="even">Página Par (Lado Izquierdo)</option>
                </select>
            </div>
        </div>

        <!-- Keep the legacy mode available to the data layer without exposing a
             second image configuration interface. -->
        <div id="chapter_legacy_opening_settings" class="hidden" aria-hidden="true">
            <select id="chapter_opening_page_mode" name="opening_page_mode">
                <option value="auto">Automático (compatibilidad actual)</option>
                <option value="none">Sin página previa</option>
                <option value="blank">Página en blanco intencional</option>
                <option value="image">Página con imagen de capítulo</option>
            </select>
        </div>

        <div id="chapter_image_settings_wrapper" class="space-y-3 hidden">
            <div class="flex items-center justify-between gap-4 p-4 bg-[var(--bg-sidebar)] rounded-xl border border-[var(--border-color)]">
                <div>
                    <label class="font-semibold block mb-1">Iniciar con Imagen</label>
                    <span class="text-[10px] text-[var(--text-muted)]">Sobrescribe la regla del libro. Si está activo y no hay imagen, se reservará una página en blanco.</span>
                </div>
                <select id="chapter_image_override" class="w-56 bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-black" onchange="toggleChapterImageSettingsForChapter()">
                    <option value="">Usar ajuste del libro</option>
                    <option value="1">Sí</option>
                    <option value="0">No</option>
                </select>
            </div>

            <div id="chapter_image_settings_content" class="space-y-3 hidden">
                <div id="chapter_image_upload_wrapper" class="space-y-2 hidden">
                    <div class="flex flex-wrap items-end gap-2">
                        <div id="chapter_image_mode_wrapper" class="min-w-[240px] flex-1">
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Modo de imagen de capítulo</label>
                            <select id="chapter_image_mode" onchange="toggleChapterImageSettingsForChapter()" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                                <option value="page_blank">Full dentro del content box</option>
                                <option value="image_full_page">Full bleed</option>
                                <option value="image_inner">Ajustable por el usuario</option>
                            </select>
                        </div>
                        <button type="button" onclick="openChapterImageUploaderForChapter()" class="px-4 py-2 bg-neutral-100 text-black dark:text-white hover:bg-neutral-200 dark:bg-neutral-800 dark:text-neutral-300 dark:hover:bg-neutral-700 border border-neutral-300 dark:border-neutral-700 rounded-lg text-sm font-semibold transition flex items-center gap-2">
                            <i class="fa-solid fa-upload"></i> Subir / Seleccionar Imagen
                        </button>
                        <button type="button" onclick="clearChapterImageSelectionForChapter()" class="px-4 py-2 bg-transparent text-[var(--text-muted)] hover:text-black dark:hover:text-white border border-[var(--border-color)] rounded-lg text-sm font-semibold transition">
                            Limpiar
                        </button>
                    </div>
                    <input id="chapter_image_url" type="text" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black" placeholder="URL de la imagen">
                </div>

                <div id="chapter_image_fullpage_note" class="hidden rounded-lg border border-dashed border-[var(--border-color)] bg-[var(--bg-sidebar)] p-3">
                    <p class="text-[10px] text-[var(--text-muted)]">
                        Image Full Page usa el ancho completo de la imagen hasta tocar el bleed; si la proporción no coincide, el ajuste ocurre solo arriba y abajo.
                    </p>
                </div>

                <div id="chapter_image_inner_controls" class="grid grid-cols-1 gap-3 hidden">
                    <div>
                        <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Ancho de imagen (%)</label>
                        <p class="text-[10px] text-[var(--text-muted)] mb-2">100% equivale al ancho total de la página, incluyendo bleed.</p>
                        <input id="chapter_image_inner_width" type="range" min="10" max="100" step="1" value="100" oninput="syncChapterImageWidthLabelForChapter()" class="w-full">
                        <div class="flex items-center justify-between text-[10px] text-[var(--text-muted)]">
                            <span>10%</span>
                            <span id="chapter_image_inner_width_label" class="font-semibold text-[var(--text-main)]">100%</span>
                            <span>100%</span>
                        </div>
                    </div>
                </div>

			</div>
		</div>
	</div>

    <div id="chapter-tab-opening" class="chapter-tab-content space-y-6 hidden">
		<div class="flex items-center justify-between p-4 bg-[var(--bg-sidebar)] rounded-xl border border-[var(--border-color)]">
			<div>
				<label class="font-semibold block mb-1">Ocultar apertura del capítulo</label>
				<span class="text-xs text-[var(--text-muted)]">Oculta el bloque de apertura del capítulo: título, prefijo, subtítulo y metadata asociada. El contenido comienza directamente y no afecta Créditos ni Índice.</span>
			</div>
			<label class="relative inline-flex items-center cursor-pointer">
				<input type="checkbox" id="chapter_hide_opening" name="hide_chapter_opening" class="sr-only peer" onchange="toggleOpeningPageControls()">
				<div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-black"></div>
			</label>
		</div>

		<div id="chapter_opening_separate_content_wrapper" class="flex items-start justify-between gap-4 p-4 bg-[var(--bg-sidebar)] rounded-xl border border-[var(--border-color)]">
			<div class="flex-1">
				<label class="font-semibold block mb-1">Separar apertura de contenido</label>
				<span class="text-xs text-[var(--text-muted)]">Sobrescribe el ajuste global solo para este capítulo. Deja en "Usar Global" para mantener el comportamiento del libro.</span>
			</div>
			<div class="w-56">
				<select id="chapter_opening_separate_content" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-black">
					<option value="">Usar Global</option>
					<option value="1">Separar apertura</option>
					<option value="0">No separar apertura</option>
				</select>
			</div>
		</div>

        <div id="chapter_opening_layout_controls" class="space-y-6 hidden">
            <div class="flex items-center justify-between p-4 bg-[var(--bg-sidebar)] rounded-xl border border-[var(--border-color)]">
                <div>
                    <label class="font-semibold block mb-1">Marcar blanco intencional</label>
                    <span class="text-xs text-[var(--text-muted)]">Reserva este flag para indicar que la página previa vacía es una decisión editorial deliberada antes de la apertura.</span>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" id="chapter_opening_blank_intentional" name="opening_blank_intentional" class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-black"></div>
                </label>
            </div>

            <div class="flex items-center justify-between p-4 bg-[var(--bg-sidebar)] rounded-xl border border-[var(--border-color)]">
                <div>
                    <label class="font-semibold block mb-1">Mostrar bloque de apertura</label>
                    <span class="text-xs text-[var(--text-muted)]">Controla el bloque estructural que contiene título, prefijo, subtítulo y metadata en la página previa en blanco.</span>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" id="chapter_opening_block_enabled" name="opening_block_enabled" class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-black"></div>
                </label>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="p-4 bg-[var(--bg-sidebar)] rounded-xl border border-[var(--border-color)]">
                    <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1">Alineación horizontal</label>
                    <select id="chapter_opening_block_horizontal_align" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                        <option value="left">Izquierda</option>
                        <option value="center">Centro</option>
                        <option value="right">Derecha</option>
                    </select>
                </div>
                <div class="p-4 bg-[var(--bg-sidebar)] rounded-xl border border-[var(--border-color)]">
                    <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1">Alineación vertical</label>
                    <select id="chapter_opening_block_vertical_align" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                        <option value="top">Arriba</option>
                        <option value="center">Centro</option>
                        <option value="bottom">Abajo</option>
                    </select>
                </div>
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

            </div>

            <div class="flex items-center justify-between p-4 bg-[var(--bg-sidebar)] rounded-xl border border-[var(--border-color)]">
                <div>
                    <label class="font-semibold block mb-1">Subtítulo</label>
                    <span class="text-xs text-[var(--text-muted)]">Activa el subtítulo de este capítulo y permite editar su contenido.</span>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" id="chapter_subtitle_show" class="sr-only peer" onchange="toggleChapterSubtitleControls()">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-black"></div>
                </label>
            </div>

            <div id="chapter_subtitle_controls" class="space-y-4 hidden">
            <div class="grid grid-cols-1 gap-4">
                <div>
                    <label class="block font-semibold mb-1">Texto del subtítulo</label>
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
                    <label class="block text-[10px] font-semibold text-[var(--text-muted)] mb-1">Padding Arriba (cm)</label>
                    <input type="number" step="0.1" id="chapter_subtitle_padding_top" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-2 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-black" placeholder="Ej: 0.5">
                </div>
                <div>
                    <label class="block text-[10px] font-semibold text-[var(--text-muted)] mb-1">Padding Abajo (cm)</label>
                    <input type="number" step="0.1" id="chapter_subtitle_padding_bottom" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-2 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-black" placeholder="Ej: 0.5">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-semibold text-[var(--text-muted)] mb-1">Padding Izquierda (cm)</label>
                    <input type="number" step="0.1" id="chapter_subtitle_padding_left" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-2 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-black" placeholder="Ej: 0">
                </div>
                <div>
                    <label class="block text-[10px] font-semibold text-[var(--text-muted)] mb-1">Padding Derecha (cm)</label>
                    <input type="number" step="0.1" id="chapter_subtitle_padding_right" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-2 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-black" placeholder="Ej: 0">
                </div>
            </div>
        </div>

    </div>

    <div id="chapter-tab-header-footer" class="chapter-tab-content space-y-6 hidden">
        <div class="grid grid-cols-1 gap-4">
            <div>
                <label class="block font-semibold mb-1">Cabecera corrida personalizada</label>
                <p class="text-xs text-[var(--text-muted)] mb-2">Sobrescribe el texto de la cabecera superior corrida, útil si el título completo es demasiado largo.</p>
                <input type="text" id="chapter_custom_running_header" name="custom_running_header" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-3 py-2 text-[var(--text-main)] focus:outline-none focus:border-black dark:border-white" placeholder="Ej: El misterio de la montaña...">
            </div>
        </div>

        <div class="p-4 bg-[var(--bg-sidebar)] rounded-xl border border-[var(--border-color)]">
            <div class="mb-3">
                <label class="font-semibold block mb-1">Contenido en la primera página</label>
                <span class="text-xs text-[var(--text-muted)]">Configura qué se muestra en la cabecera y pie de la primera página de texto de este capítulo. Si el capítulo arranca con imagen, esa página queda fuera de este ajuste y nunca mostrará cabecera ni pie.</span>
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

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="flex items-center justify-between p-4 bg-[var(--bg-sidebar)] rounded-xl border border-[var(--border-color)]">
                <div>
                    <label class="font-semibold block mb-1">Ocultar cabecera</label>
                    <span class="text-xs text-[var(--text-muted)]">Oculta la cabecera corrida solo en la primera página de texto de este capítulo. La primera página con imagen nunca lleva cabecera.</span>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" id="chapter_hide_header" name="hide_header" class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-black"></div>
                </label>
            </div>
            <div class="flex items-center justify-between p-4 bg-[var(--bg-sidebar)] rounded-xl border border-[var(--border-color)]">
                <div>
                    <label class="font-semibold block mb-1">Ocultar pie</label>
                    <span class="text-xs text-[var(--text-muted)]">Oculta el pie de página solo en la primera página de texto de este capítulo. La primera página con imagen nunca lleva pie.</span>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" id="chapter_hide_footer" name="hide_footer" class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-black"></div>
                </label>
            </div>
        </div>
    </div>

    <div id="chapter-tab-advanced" class="chapter-tab-content space-y-6 hidden">
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

        <div class="grid grid-cols-1 gap-4">
            <h4 class="font-bold border-b border-[var(--border-color)] pb-2">Páginas en blanco</h4>
            <p class="text-xs text-[var(--text-muted)] -mt-2">Agrega páginas completamente vacías inmediatamente antes o después de este capítulo.</p>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1">Antes del capítulo</label>
                <input type="number" min="0" max="999" step="1" id="chapter_blank_before" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-black" value="0">
            </div>
            <div>
                <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1">Después del capítulo</label>
                <input type="number" min="0" max="999" step="1" id="chapter_blank_after" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-black" value="0">
            </div>
        </div>

        <div class="p-4 rounded-xl border border-[var(--border-color)] bg-[var(--bg-sidebar)]">
            <label class="block font-semibold mb-1">Alineación vertical de la página 1</label>
            <p class="text-xs text-[var(--text-muted)]">Esta opción se define globalmente en Ajustes del Libro &gt; Capítulos y se aplica a todos los capítulos por igual.</p>
        </div>
    </div>
</div>
