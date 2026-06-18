<div id="tab-typography" class="setting-tab-content space-y-4 hidden">
                <div>
                    <h4 class="text-[10px] font-bold uppercase tracking-wider text-black dark:text-white mb-2 border-b border-[var(--border-color)] pb-1">Tipografía del Cuerpo</h4>
                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label class="block text-[9px] text-[var(--text-muted)] mb-1">Familia de Fuente</label>
                            <select id="setting-font-family-content" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                                <?php almaden_render_font_options( $default_fonts, $selector_fonts ); ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[9px] text-[var(--text-muted)] mb-1">Tamaño (pt)</label>
                            <input id="setting-font-size-content" type="number" step="0.1" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                        <div>
                            <label class="block text-[9px] text-[var(--text-muted)] mb-1">Peso</label>
                            <select id="setting-font-weight-content" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                                <option value="100">100 - Fino</option>
                                <option value="200">200 - Extra Ligero</option>
                                <option value="300">300 - Ligero</option>
                                <option value="normal">400 - Normal</option>
                                <option value="500">500 - Medio</option>
                                <option value="600">600 - Semi Negrita</option>
                                <option value="bold">700 - Negrita</option>
                                <option value="800">800 - Extra Negrita</option>
                                <option value="900">900 - Negro</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-4 gap-2 mt-2">
                        <div>
                            <label class="block text-[9px] text-[var(--text-muted)] mb-1">Interlineado</label>
                            <input id="setting-line-height-content" type="number" step="0.05" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                        <div>
                            <label class="block text-[9px] text-[var(--text-muted)] mb-1">Alineación</label>
                            <select id="setting-content-text-align" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                                <option value="left">Izquierda</option>
                                <option value="center">Centro</option>
                                <option value="right">Derecha</option>
                                <option value="justify">Justificado</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[9px] text-[var(--text-muted)] mb-1">Guiones (Separación)</label>
                            <select id="setting-content-hyphenation" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                                <option value="1">Activado</option>
                                <option value="0">Desactivado</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[9px] text-[var(--text-muted)] mb-1">Idioma Reglas</label>
                            <select id="setting-content-language" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                                <option value="es">Español</option>
                                <option value="en">Inglés</option>
                                <option value="fr">Francés</option>
                                <option value="de">Alemán</option>
                                <option value="pt">Portugués</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2 mt-2">
                        <div>
                            <label class="block text-[9px] text-[var(--text-muted)] mb-1">Sangría 1ª línea (pt)</label>
                            <input id="setting-content-paragraph-indent" type="number" step="0.5" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                        <div>
                            <label class="block text-[9px] text-[var(--text-muted)] mb-1">Espaciado entre párrafos (pt)</label>
                            <input id="setting-content-paragraph-spacing" type="number" step="0.5" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                    </div>
                </div>

                <div>
                    <h4 class="text-[10px] font-bold uppercase tracking-wider text-black dark:text-white mb-2 border-b border-[var(--border-color)] pb-1">Tipografía de Títulos (H1, H2, H3)</h4>
                    
                    <div class="space-y-3">
                        <!-- H1 -->
                        <div class="p-2 border border-[var(--border-color)] rounded-lg bg-[var(--bg-app)]">
                            <span class="text-[9px] font-bold text-[var(--text-main)] block mb-2">Título Principal (H1)</span>
                            <div class="grid grid-cols-3 gap-2">
                                <div>
                                    <label class="block text-[8px] text-[var(--text-muted)] mb-1">Familia</label>
                                    <select id="setting-font-family-h1" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded p-1 text-xs focus:outline-none focus:ring-1 focus:ring-black">
                                        <?php almaden_render_font_options( $heading_default_fonts, $selector_fonts ); ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[8px] text-[var(--text-muted)] mb-1">Tamaño (pt)</label>
                                    <input id="setting-font-size-h1" type="number" step="0.5" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded p-1 text-xs text-center focus:outline-none focus:ring-1 focus:ring-black">
                                </div>
                                <div>
                                    <label class="block text-[8px] text-[var(--text-muted)] mb-1">Peso</label>
                                    <select id="setting-font-weight-h1" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded p-1 text-xs focus:outline-none focus:ring-1 focus:ring-black">
                                        <option value="100">100 - Fino</option>
                                        <option value="200">200 - Extra Ligero</option>
                                        <option value="300">300 - Ligero</option>
                                        <option value="normal">400 - Normal</option>
                                        <option value="500">500 - Medio</option>
                                        <option value="600">600 - Semi Negrita</option>
                                        <option value="bold">700 - Negrita</option>
                                        <option value="800">800 - Extra Negrita</option>
                                        <option value="900">900 - Negro</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- H2 -->
                        <div class="p-2 border border-[var(--border-color)] rounded-lg bg-[var(--bg-app)]">
                            <span class="text-[9px] font-bold text-[var(--text-main)] block mb-2">Subtítulo (H2)</span>
                            <div class="grid grid-cols-3 gap-2">
                                <div>
                                    <label class="block text-[8px] text-[var(--text-muted)] mb-1">Familia</label>
                                    <select id="setting-font-family-h2" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded p-1 text-xs focus:outline-none focus:ring-1 focus:ring-black">
                                        <?php almaden_render_font_options( $heading_default_fonts, $selector_fonts ); ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[8px] text-[var(--text-muted)] mb-1">Tamaño (pt)</label>
                                    <input id="setting-font-size-h2" type="number" step="0.5" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded p-1 text-xs text-center focus:outline-none focus:ring-1 focus:ring-black">
                                </div>
                                <div>
                                    <label class="block text-[8px] text-[var(--text-muted)] mb-1">Peso</label>
                                    <select id="setting-font-weight-h2" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded p-1 text-xs focus:outline-none focus:ring-1 focus:ring-black">
                                        <option value="100">100 - Fino</option>
                                        <option value="200">200 - Extra Ligero</option>
                                        <option value="300">300 - Ligero</option>
                                        <option value="normal">400 - Normal</option>
                                        <option value="500">500 - Medio</option>
                                        <option value="600">600 - Semi Negrita</option>
                                        <option value="bold">700 - Negrita</option>
                                        <option value="800">800 - Extra Negrita</option>
                                        <option value="900">900 - Negro</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- H3 -->
                        <div class="p-2 border border-[var(--border-color)] rounded-lg bg-[var(--bg-app)]">
                            <span class="text-[9px] font-bold text-[var(--text-main)] block mb-2">Tercer Nivel (H3)</span>
                            <div class="grid grid-cols-3 gap-2">
                                <div>
                                    <label class="block text-[8px] text-[var(--text-muted)] mb-1">Familia</label>
                                    <select id="setting-font-family-h3" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded p-1 text-xs focus:outline-none focus:ring-1 focus:ring-black">
                                        <?php almaden_render_font_options( $heading_default_fonts, $selector_fonts ); ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[8px] text-[var(--text-muted)] mb-1">Tamaño (pt)</label>
                                    <input id="setting-font-size-h3" type="number" step="0.5" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded p-1 text-xs text-center focus:outline-none focus:ring-1 focus:ring-black">
                                </div>
                                <div>
                                    <label class="block text-[8px] text-[var(--text-muted)] mb-1">Peso</label>
                                    <select id="setting-font-weight-h3" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded p-1 text-xs focus:outline-none focus:ring-1 focus:ring-black">
                                        <option value="100">100 - Fino</option>
                                        <option value="200">200 - Extra Ligero</option>
                                        <option value="300">300 - Ligero</option>
                                        <option value="normal">400 - Normal</option>
                                        <option value="500">500 - Medio</option>
                                        <option value="600">600 - Semi Negrita</option>
                                        <option value="bold">700 - Negrita</option>
                                        <option value="800">800 - Extra Negrita</option>
                                        <option value="900">900 - Negro</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
