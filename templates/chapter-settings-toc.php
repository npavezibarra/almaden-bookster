                <!-- Ajustes exclusivos para la Tabla de Contenidos -->
                <div id="toc-chapter-settings" class="hidden">
                    <!-- TABS NAVIGATION TOC -->
                    <div class="border-b border-[var(--border-color)] mb-4">
                        <nav class="-mb-px flex gap-6" aria-label="Tabs">
                            <button type="button" onclick="switchTocTab('toc-tab-general')" id="btn-toc-tab-general" class="toc-tab-btn whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-indigo-500 text-indigo-600">General</button>
                            <button type="button" onclick="switchTocTab('toc-tab-list')" id="btn-toc-tab-list" class="toc-tab-btn whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-[var(--text-muted)] hover:text-[var(--text-main)] hover:border-gray-300">Lista de Capítulos</button>
                            <button type="button" onclick="switchTocTab('toc-tab-title')" id="btn-toc-tab-title" class="toc-tab-btn whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-[var(--text-muted)] hover:text-[var(--text-main)] hover:border-gray-300">Título del Índice</button>
                        </nav>
                    </div>

                    <!-- TOC TAB: General -->
                    <div id="toc-tab-general" class="toc-tab-content space-y-6 block">
                        <div class="p-4 bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-200 dark:border-indigo-800 rounded-xl mb-4 text-indigo-800 dark:text-indigo-300">
                            <p class="text-sm font-semibold mb-1"><i class="fa-solid fa-circle-info mr-1"></i> Configuración General</p>
                            <p class="text-xs">Estás editando el Índice. Configura opciones globales para esta sección.</p>
                        </div>

                        <!-- NUEVA OPCIÓN: Ocultar números de página en el pie del Índice -->
                        <div class="flex items-center justify-between p-4 bg-[var(--bg-sidebar)] rounded-xl border border-[var(--border-color)] mb-4">
                            <div>
                                <label class="font-semibold block mb-1 text-sm">Ocultar Numeración de Páginas</label>
                                <span class="text-xs text-[var(--text-muted)]">Oculta los números de página en el pie de las hojas que forman parte del Índice.</span>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" id="chapter_toc_hide_page_numbers" name="toc_hide_page_numbers" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                            </label>
                        </div>
                    </div><!-- End TOC Tab: General -->

                    <!-- TOC TAB: Lista de Capítulos -->
                    <div id="toc-tab-list" class="toc-tab-content space-y-6 hidden">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1">Alineación de Capítulos</label>
                                <select id="chapter_toc_item_align" name="toc_item_align" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <option value="left">Izquierda</option>
                                    <option value="center">Centro</option>
                                    <option value="right">Derecha</option>
                                </select>
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
                                <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1">Tamaño de Fuente (px)</label>
                                <input type="number" step="0.5" id="chapter_toc_font_size" name="toc_font_size" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Ej: 11.5">
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
                                <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1">Estilo de Fuente</label>
                                <select id="chapter_toc_font_style" name="toc_font_style" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <option value="normal">Normal</option>
                                    <option value="italic">Itálica (Cursiva)</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1">Transformación</label>
                                <select id="chapter_toc_text_transform" name="toc_text_transform" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <option value="none">Ninguna</option>
                                    <option value="uppercase">MAYÚSCULAS (All Caps)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1">Letter Spacing (px)</label>
                                <input type="number" step="0.1" id="chapter_toc_letter_spacing" name="toc_letter_spacing" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Ej: 0.5">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1">Interlineado de texto (Line Height)</label>
                                <input type="number" step="0.1" id="chapter_toc_line_height" name="toc_line_height" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Ej: 1.8">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1">Espacio entre Capítulos (mm)</label>
                                <input type="number" step="1" id="chapter_toc_item_spacing" name="toc_item_spacing" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Ej: 8">
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
                    </div><!-- End TOC Tab: Lista -->

                    <!-- TOC TAB: Título del Índice -->
                    <div id="toc-tab-title" class="toc-tab-content space-y-6 hidden">
                        <div class="p-4 bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-200 dark:border-indigo-800 rounded-xl mb-4 text-indigo-800 dark:text-indigo-300">
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
                    </div><!-- End TOC Tab: Title -->

                </div><!-- End toc-chapter-settings -->
