<!-- Modal de Configuración Local (Capítulo) -->
<div id="chapter-settings-modal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden z-50 flex items-center justify-center transition-opacity opacity-0">
    <div class="bg-[var(--bg-app)] border border-[var(--border-color)] w-full max-w-2xl rounded-2xl shadow-2xl overflow-hidden flex flex-col transform scale-95 transition-transform" style="max-height: 90vh;">
        
        <!-- Header -->
        <div class="px-6 py-4 border-b border-[var(--border-color)] flex justify-between items-center bg-[var(--bg-sidebar)]">
            <div>
                <h3 id="chapter-settings-modal-title" class="font-bold text-lg text-[var(--text-main)] flex items-center gap-2">
                    <i class="fa-solid fa-gear text-black dark:text-white"></i> Ajustes del Capítulo de Contenido
                </h3>
                <p id="chapter-settings-modal-subtitle" class="text-xs text-[var(--text-muted)] mt-1">
                    Estas configuraciones sobrescriben las reglas globales solo para el capítulo de contenido actual.
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
                        <label id="chapter-settings-modal-start-label" class="block font-semibold mb-1">¿Dónde debe iniciar el contenido de este capítulo?</label>
                        <p id="chapter-settings-modal-start-subtitle" class="text-xs text-[var(--text-muted)] mb-2">Define el lado donde empieza el contenido. La apertura se configura aparte en la pestaña "Apertura".</p>
                        <select id="chapter_start_parity" name="chapter_start_parity" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-3 py-2 text-[var(--text-main)] focus:outline-none focus:border-black">
                            <option value="any">Continuo / Cualquiera (Por defecto)</option>
                            <option value="odd">Página Impar (Lado Derecho)</option>
                            <option value="even">Página Par (Lado Izquierdo)</option>
                        </select>
                    </div>
                </div>

                <!-- Ajustes normales de capítulo -->
                <?php include plugin_dir_path( __FILE__ ) . 'chapter-settings-normal.php'; ?>

                <!-- Ajustes exclusivos para la Tabla de Contenidos -->
                <?php include plugin_dir_path( __FILE__ ) . 'chapter-settings-toc.php'; ?>

                <!-- Ajustes para Créditos -->
                <div id="credits-chapter-settings" class="hidden space-y-6">
                    <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-lg p-4 flex gap-3">
                        <i class="fa-solid fa-circle-info mt-0.5 text-amber-500"></i>
                        <div class="text-sm">
                            <p class="font-bold mb-1">Página de Créditos</p>
                            <p>El contenido de esta página se genera automáticamente utilizando los datos configurados en los <strong>Ajustes Globales > Créditos</strong>.</p>
                            <p class="mt-2">Puedes elegir en qué lado de la página debe iniciar utilizando el selector de arriba.</p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 gap-4">
                        <h4 class="font-bold border-b border-[var(--border-color)] pb-2">Tipografía de la Página de Créditos</h4>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1">Tipografía</label>
                            <select id="chapter_credits_font_family" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                                <option value="">Usar Global</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1">Alineación</label>
                            <select id="chapter_credits_align" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                                <option value="">Global / Centro</option>
                                <option value="left">Izquierda</option>
                                <option value="center">Centro</option>
                                <option value="right">Derecha</option>
                                <option value="justify">Justificado</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-[10px] font-semibold text-[var(--text-muted)] mb-1">Tamaño (pt)</label>
                            <input type="number" step="0.5" id="chapter_credits_font_size" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-2 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-black" placeholder="Ej: 9">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-[var(--text-muted)] mb-1">Espaciado de Letras (px)</label>
                            <input type="number" step="0.1" id="chapter_credits_letter_spacing" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-2 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-black" placeholder="Ej: 0.5">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-[var(--text-muted)] mb-1">Peso</label>
                            <select id="chapter_credits_font_weight" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-2 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                                <option value="">Global</option>
                                <option value="normal">Normal (400)</option>
                                <option value="bold">Bold (700)</option>
                                <option value="300">Light (300)</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-[var(--bg-sidebar)] rounded-xl border border-[var(--border-color)]">
                        <div>
                            <label class="font-semibold block mb-1">Ocultar Cabecera</label>
                            <span class="text-xs text-[var(--text-muted)]">Oculta la cabecera superior en esta página de créditos.</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" id="chapter_credits_hide_header" name="credits_hide_header" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-black"></div>
                        </label>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-[var(--bg-sidebar)] rounded-xl border border-[var(--border-color)]">
                        <div>
                            <label class="font-semibold block mb-1">Ocultar número de página</label>
                            <span class="text-xs text-[var(--text-muted)]">Oculta el número de página en este capítulo de créditos.</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" id="chapter_credits_hide_page_number" name="credits_hide_page_number" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-black"></div>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 gap-4">
                        <h4 class="font-bold border-b border-[var(--border-color)] pb-2">Márgenes de la Página de Créditos</h4>
                        <p class="text-xs text-[var(--text-muted)] -mt-2">Deja estos campos vacíos para usar los márgenes globales del libro.</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-semibold text-[var(--text-muted)] mb-1">Margen superior (<span class="unit-label">cm</span>)</label>
                            <input type="number" step="0.1" id="chapter_credits_margin_top" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-2 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-black" placeholder="Global">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-[var(--text-muted)] mb-1">Margen inferior (<span class="unit-label">cm</span>)</label>
                            <input type="number" step="0.1" id="chapter_credits_margin_bottom" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-2 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-black" placeholder="Global">
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Footer / Controles -->
        <div class="px-6 py-4 border-t border-[var(--border-color)] flex justify-end gap-3 bg-[var(--bg-sidebar)]">
            <button type="button" onclick="closeChapterSettingsModal()" class="px-4 py-2 rounded-lg text-sm font-semibold border border-[var(--border-color)] hover:bg-[var(--bg-app)] transition">
                Cancelar
            </button>
            <button type="button" onclick="saveChapterSettings()" class="px-4 py-2 rounded-lg text-sm font-semibold bg-black text-white hover:bg-neutral-800 shadow-lg shadow-black/30 transition flex items-center gap-2">
                <i class="fa-solid fa-check"></i> Aplicar al Capítulo de Contenido
            </button>
        </div>
        
    </div>
</div>
