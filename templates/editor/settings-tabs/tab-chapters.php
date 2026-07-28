<div id="tab-chapters" class="setting-tab-content space-y-4 hidden">
                <div>
                    <h4 class="text-[10px] font-bold uppercase tracking-wider text-black dark:text-white mb-2 border-b border-[var(--border-color)] pb-1">Ajustes de Libro</h4>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[9px] text-[var(--text-muted)] mb-1">Separar apertura de contenido</label>
                            <label class="relative inline-flex items-center cursor-pointer mt-1">
                                <input type="checkbox" id="setting-book-separate-opening-content" class="sr-only peer">
                                <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-black rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-black"></div>
                            </label>
                            <p class="text-[10px] text-[var(--text-muted)] mt-1">Si está activo, la apertura editorial y el contenido se distribuyen en páginas distintas cuando el capítulo lo requiere.</p>
                        </div>
                        <div>
                            <label class="block text-[9px] text-[var(--text-muted)] mb-1">Flujo de capítulos</label>
                            <select id="setting-book-chapter-flow-mode" onchange="syncBookFlowParityMode()" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                                <option value="continuous">Continuo / cualquiera</option>
                                <option value="left">Iniciar izquierda (par)</option>
                            </select>
                            <p class="text-[10px] text-[var(--text-muted)] mt-1">Esta regla define cómo encadena el libro los capítulos completos, no solo el contenido.</p>
                        </div>
                        <div id="chapter-transition-blank-mode-wrapper" class="col-span-2 hidden">
                            <label class="block text-[9px] text-[var(--text-muted)] mb-1">Página en blanco entre capítulos</label>
                            <select id="setting-chapter-transition-blank-mode" onchange="toggleChapterTransitionBlankSettings()" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                                <option value="full_blank">Full Blanco (sin cabecera ni pie)</option>
                                <option value="blank_with_header_footer">Blanco con cabecera y pie</option>
                                <option value="intentional_text">Blanco intencional con texto</option>
                            </select>
                            <p class="text-[10px] text-[var(--text-muted)] mt-1">Solo aparece cuando el flujo del libro inicia a la izquierda. Esta opción controla cómo se dibuja la página de transición entre capítulos.</p>
                        </div>
                        <div id="chapter-transition-blank-text-wrapper" class="col-span-2 hidden">
                            <label class="block text-[9px] text-[var(--text-muted)] mb-1">Texto centrado</label>
                            <input id="setting-chapter-transition-blank-text" type="text" value="..." class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black" placeholder="...">
                            <p class="text-[10px] text-[var(--text-muted)] mt-1">Se mostrará centrado dentro de la página en blanco cuando selecciones el modo de texto intencional.</p>
                        </div>
                        <div>
                            <label class="block text-[9px] text-[var(--text-muted)] mb-1">Pie de la página inicial del libro</label>
                            <select id="setting-book-start-page-footer-type" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                                <option value="blank">Dejar en blanco</option>
                                <option value="page_number">Mostrar número 1</option>
                            </select>
                        </div>
                        <input type="hidden" id="setting-chapter-start-parity" value="any">
                    </div>
                </div>

                <div>
                    <h4 class="text-[10px] font-bold uppercase tracking-wider text-black dark:text-white mb-2 border-b border-[var(--border-color)] pb-1">Prefijo de Capítulo (Ej: Capítulo 1)</h4>
                    <div class="grid grid-cols-2 gap-3 mb-2">
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Mostrar Prefijo</label>
                            <label class="relative inline-flex items-center cursor-pointer mt-1">
                                <input type="checkbox" id="setting-chapter-prefix-show" class="sr-only peer">
                                <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-black rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-black"></div>
                            </label>
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Texto (usa {N} para número, {R} para romano)</label>
                            <input id="setting-chapter-prefix-template" type="text" value="Capítulo {N}" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                    </div>
                    <div class="grid grid-cols-4 gap-2 mb-2">
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Posición</label>
                            <select id="setting-chapter-prefix-position" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                                <option value="above">Arriba del título</option>
                                <option value="below">Abajo del título</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Ornamento</label>
                            <select id="setting-chapter-prefix-ornament" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                                <option value="none">Ninguno</option>
                                <option value="line_below">Línea debajo</option>
                                <option value="line_above_below">Línea arriba y abajo</option>
                                <option value="asterisks">Asteriscos (***)</option>
                            </select>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Fuente</label>
                            <select id="setting-chapter-prefix-font-family" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                                <?php almaden_render_font_options( $heading_default_fonts, $selector_fonts ); ?>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-4 gap-2">
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Tamaño (pt)</label>
                            <input id="setting-chapter-prefix-font-size" type="number" step="0.5" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Estilo</label>
                            <select id="setting-chapter-prefix-font-style" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                                <option value="normal">Normal</option>
                                <option value="italic">Cursiva</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Peso</label>
                            <select id="setting-chapter-prefix-font-weight" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                                <option value="300">Light (300)</option>
                                <option value="normal">Normal (400)</option>
                                <option value="600">Semi Bold (600)</option>
                                <option value="bold">Bold (700)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Let. Spacing</label>
                            <input id="setting-chapter-prefix-letter-spacing" type="number" step="0.1" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                    </div>
                </div>

                <div>
                    <h4 class="text-[10px] font-bold uppercase tracking-wider text-black dark:text-white mb-2 border-b border-[var(--border-color)] pb-1">Diseño de Página 1 del Capítulo</h4>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="col-span-2">
                            <label class="block text-[9px] text-[var(--text-muted)] mb-1">Diseño Vertical</label>
                            <select id="setting-chapter-page-one-vertical" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                                <option value="top">Arriba (Margen estándar)</option>
                                <option value="center">Centrado vertical</option>
                                <option value="bottom">Abajo</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div>
                    <h4 class="text-[10px] font-bold uppercase tracking-wider text-black dark:text-white mb-2 border-b border-[var(--border-color)] pb-1">Formato del Título de Capítulo</h4>
                    <div class="grid grid-cols-2 gap-3 mb-2">
                        <div class="col-span-2">
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Familia de Fuente</label>
                            <select id="setting-chapter-title-font-family" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                                <?php almaden_render_font_options( $heading_default_fonts, $selector_fonts ); ?>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-5 gap-2 mb-2">
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Tamaño (pt)</label>
                            <input id="setting-chapter-title-font-size" type="number" step="0.5" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Alineación</label>
                            <select id="setting-chapter-title-align" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                                <option value="left">Izquierda</option>
                                <option value="center">Centro</option>
                                <option value="right">Derecha</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Estilo</label>
                            <select id="setting-chapter-title-font-style" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                                <option value="normal">Normal</option>
                                <option value="italic">Cursiva</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Transformación</label>
                            <select id="setting-chapter-title-text-transform" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                                <option value="none">Normal</option>
                                <option value="uppercase">ALL CAPS</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Peso</label>
                            <select id="setting-chapter-title-font-weight" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
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
                    <div class="grid grid-cols-5 gap-2">
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Padd. Arriba (cm)</label>
                            <input id="setting-chapter-title-padding-top" type="number" step="0.1" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Padd. Abajo (cm)</label>
                            <input id="setting-chapter-title-padding-bottom" type="number" step="0.1" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Padd. Izq. (cm)</label>
                            <input id="setting-chapter-title-padding-left" type="number" step="0.1" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Padd. Der. (cm)</label>
                            <input id="setting-chapter-title-padding-right" type="number" step="0.1" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Altura Línea</label>
                            <input id="setting-chapter-title-line-height" type="number" step="0.1" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                    </div>
                </div>

                <div>
                    <h4 class="text-[10px] font-bold uppercase tracking-wider text-black dark:text-white mb-2 border-b border-[var(--border-color)] pb-1">Formato del Subtítulo / Meta</h4>
                    <div class="grid grid-cols-2 gap-3 mb-2">
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Mostrar Subtítulo</label>
                            <label class="relative inline-flex items-center cursor-pointer mt-1">
                                <input type="checkbox" id="setting-chapter-subtitle-show" class="sr-only peer" checked>
                                <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-black rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-black"></div>
                            </label>
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Familia de Fuente</label>
                            <select id="setting-chapter-subtitle-font-family" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                                <?php almaden_render_font_options( $heading_default_fonts, $selector_fonts ); ?>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-5 gap-2 mb-2">
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Tamaño (pt)</label>
                            <input id="setting-chapter-subtitle-font-size" type="number" step="0.5" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Alineación</label>
                            <select id="setting-chapter-subtitle-align" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                                <option value="left">Izquierda</option>
                                <option value="center">Centro</option>
                                <option value="right">Derecha</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Estilo</label>
                            <select id="setting-chapter-subtitle-font-style" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                                <option value="normal">Normal</option>
                                <option value="italic">Cursiva</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Transformación</label>
                            <select id="setting-chapter-subtitle-text-transform" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                                <option value="none">Normal</option>
                                <option value="uppercase">ALL CAPS</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Peso</label>
                            <select id="setting-chapter-subtitle-font-weight" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
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
                    <div class="grid grid-cols-5 gap-2">
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Margen Arr. (cm)</label>
                            <input id="setting-chapter-subtitle-margin-top" type="number" step="0.1" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Margen Ab. (cm)</label>
                            <input id="setting-chapter-subtitle-margin-bottom" type="number" step="0.1" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Espaciado (Letter Spacing)</label>
                            <input id="setting-chapter-subtitle-letter-spacing" type="number" step="0.1" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                    </div>
                </div>
            </div>
