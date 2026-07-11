<div id="tab-footnotes" class="setting-tab-content space-y-4 hidden">
    <div class="border border-[var(--border-color)] rounded-xl p-4 bg-[var(--bg-app)] space-y-3 shadow-sm">
        <h4 class="text-xs font-bold text-black dark:text-white uppercase tracking-wider flex items-center gap-1.5 border-b border-[var(--border-color)] pb-2 mb-1">
            <i class="fa-solid fa-bookmark text-[10px]"></i> Configuración de Notas al Pie
        </h4>
        <p class="text-[10px] text-[var(--text-muted)]">
            Estos controles estilizan el área de footnotes nativa de Paged.js, que vive entre el contenido y el pie de página.
        </p>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Familia de Fuente</label>
                <select id="setting-footnote-font-family" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                    <?php almaden_render_font_options( $default_fonts, $selector_fonts ); ?>
                </select>
            </div>
            <div>
                <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Tamaño de Fuente (pt)</label>
                <input id="setting-footnote-font-size" type="number" step="0.1" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Peso Fuente</label>
                <select id="setting-footnote-font-weight" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
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
            <div>
                <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Alineación / Justificación</label>
                <select id="setting-footnote-align" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                    <option value="left">Izquierda</option>
                    <option value="center">Centro</option>
                    <option value="right">Derecha</option>
                    <option value="justify">Justificado</option>
                </select>
            </div>
        </div>

        <div>
            <h5 class="text-[10px] font-bold uppercase tracking-wider text-black dark:text-white mb-2 border-b border-[var(--border-color)] pb-1">Marcador inline</h5>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Tamaño del número</label>
                    <input id="setting-footnote-call-scale" type="number" step="0.1" min="0.1" max="2" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-black">
                </div>
                <div>
                    <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Elevación sobre la línea base (em)</label>
                    <input id="setting-footnote-call-raise" type="number" step="0.1" min="0" max="2" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-black">
                </div>
            </div>
        </div>

        <div>
            <h5 class="text-[10px] font-bold uppercase tracking-wider text-black dark:text-white mb-2 border-b border-[var(--border-color)] pb-1">Padding del área</h5>
            <div class="grid grid-cols-4 gap-2">
                <div>
                    <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Arriba (cm)</label>
                    <input id="setting-footnote-padding-top" type="number" step="0.1" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-black">
                </div>
                <div>
                    <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Abajo (cm)</label>
                    <input id="setting-footnote-padding-bottom" type="number" step="0.1" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-black">
                </div>
                <div>
                    <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Izquierda (cm)</label>
                    <input id="setting-footnote-padding-left" type="number" step="0.1" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-black">
                </div>
                <div>
                    <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Derecha (cm)</label>
                    <input id="setting-footnote-padding-right" type="number" step="0.1" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-black">
                </div>
            </div>
        </div>

        <div>
            <h5 class="text-[10px] font-bold uppercase tracking-wider text-black dark:text-white mb-2 border-b border-[var(--border-color)] pb-1">Separador visual</h5>
            <div class="space-y-3">
                <label class="flex items-center gap-2 text-xs font-semibold text-[var(--text-main)]">
                    <input id="setting-footnote-separator-show" type="checkbox" class="rounded border-[var(--border-color)] text-black dark:text-white focus:ring-black bg-[var(--bg-sidebar)] h-4 w-4">
                    <span>Mostrar línea separadora sobre las notas</span>
                </label>

                <div class="grid grid-cols-4 gap-3">
                    <div>
                        <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Alineación</label>
                        <select id="setting-footnote-separator-align" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                            <option value="left">Izquierda</option>
                            <option value="center">Centro</option>
                            <option value="right">Derecha</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Ancho</label>
                        <select id="setting-footnote-separator-width" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                            <option value="100">100%</option>
                            <option value="75">75%</option>
                            <option value="50">50%</option>
                            <option value="25">25%</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Grosor (pt)</label>
                        <input id="setting-footnote-separator-thickness" type="number" step="0.1" min="0.1" max="5" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-black">
                    </div>
                    <div>
                        <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Margen inferior (cm)</label>
                        <input id="setting-footnote-separator-margin-bottom" type="number" step="0.1" min="0" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-black">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
