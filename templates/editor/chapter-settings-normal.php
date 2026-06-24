                <div id="normal-chapter-settings" class="space-y-6">
                    <!-- TABS NAVIGATION -->
                    <div class="border-b border-[var(--border-color)] mb-4">
                        <nav class="-mb-px flex gap-6" aria-label="Tabs">
                            <button type="button" onclick="switchChapterTab('tab-general')" id="btn-tab-general" class="chapter-tab-btn whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-black dark:border-white text-black dark:text-white">General</button>
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
                                <div class="w-8 h-4 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all dark:border-gray-600 peer-checked:bg-black"></div>
                            </div>
                            <span class="text-[11px] font-medium text-[var(--text-main)] group-hover:text-black dark:text-white transition-colors">Ocultar título en PDF</span>
                        </label>
                        
                        <label class="flex items-center gap-2 cursor-pointer group">
                            <div class="relative flex items-center">
                                <input type="checkbox" id="chapter_exclude_from_numbering" name="exclude_from_numbering" class="sr-only peer">
                                <div class="w-8 h-4 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all dark:border-gray-600 peer-checked:bg-black"></div>
                            </div>
                            <span class="text-[11px] font-medium text-[var(--text-main)] group-hover:text-black dark:text-white transition-colors">Excluir de numeración</span>
                        </label>

                        <label class="flex items-center gap-2 cursor-pointer group col-span-2">
                            <div class="relative flex items-center">
                                <input type="checkbox" id="chapter_hide_all_headers_footers" name="hide_all_headers_footers" class="sr-only peer">
                                <div class="w-8 h-4 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all dark:border-gray-600 peer-checked:bg-black"></div>
                            </div>
                            <span class="text-[11px] font-medium text-[var(--text-main)] group-hover:text-black dark:text-white transition-colors">Sin cabecera ni pie en todo el capítulo</span>
                        </label>
                    </div>
                </div>

                <!-- Cabecera Personalizada -->
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="block font-semibold mb-1">Cabecera Superior Personalizada</label>
                        <p class="text-xs text-[var(--text-muted)] mb-2">Sobrescribe el texto de la cabecera (útil si el título original es demasiado largo para caber).</p>
                        <input type="text" id="chapter_custom_running_header" name="custom_running_header" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-3 py-2 text-[var(--text-main)] focus:outline-none focus:border-black dark:border-white" placeholder="Ej: El misterio de la montaña...">
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
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-black"></div>
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
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-black"></div>
                    </label>
                </div>

                <!-- Alineación Vertical Página 1 -->
                <div class="grid grid-cols-1 gap-4">
                    <div class="p-4 rounded-xl border border-[var(--border-color)] bg-[var(--bg-sidebar)]">
                        <label class="block font-semibold mb-1">Alineación Vertical de la Página 1</label>
                        <p class="text-xs text-[var(--text-muted)]">Esta opción se define globalmente en Ajustes del Libro &gt; Capítulos y se aplica a todos los capítulos por igual.</p>
                    </div>
                </div>


                    </div><!-- End tab-general -->

                    <!-- TAB CONTENT: Subtitle -->
                    <div id="tab-subtitle" class="chapter-tab-content space-y-6 hidden">
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
                            <select id="chapter_first_page_header_type" name="first_page_header_type" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-2 text-xs focus:outline-none focus:ring-2 focus:ring-black" onchange="toggleChapterCustomFirstPageHeader()">
                                <option value="global" selected>Usar Global</option>
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
                                <option value="global" selected>Usar Global</option>
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

                <!-- Modo de Imagen de Paridad -->
                <div class="grid grid-cols-1 gap-4 pb-4">
                    <div>
                        <label class="block font-semibold mb-1">Imagen de Paridad (Página en Blanco)</label>
                        <p class="text-xs text-[var(--text-muted)] mb-2">Selecciona la imagen que se mostrará en la página opuesta al inicio del capítulo.</p>
                        <div class="flex items-center gap-2 mb-4">
                            <button type="button" onclick="openParityImageUploader()" class="px-4 py-2 bg-neutral-100 text-black dark:text-white hover:bg-neutral-200 dark:bg-neutral-800 dark:text-neutral-300 dark:hover:bg-neutral-700 border border-neutral-300 dark:border-neutral-700 rounded-lg text-sm font-semibold transition flex items-center gap-2">
                                <i class="fa-solid fa-upload"></i> Subir / Seleccionar Imagen
                            </button>
                        </div>

                        <label class="block font-semibold mb-1">Modo de Imagen de Paridad</label>
                        <p class="text-xs text-[var(--text-muted)] mb-2">Define si la imagen que llena la página en blanco se extiende hasta el borde (Sangría) o respeta los márgenes.</p>
                        <select id="chapter_parity_image_mode" name="parity_image_mode" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-3 py-2 text-[var(--text-main)] focus:outline-none focus:border-black dark:border-white" onchange="toggleParityImageSizeInputs()">
                            <option value="content">Full dentro del content box</option>
                            <option value="bleed">Full page con 5mm bleed (3 lados)</option>
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
                    </div><!-- End tab-design -->
                </div><!-- End normal-chapter-settings -->
