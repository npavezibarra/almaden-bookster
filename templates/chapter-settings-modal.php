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
                
                <!-- Ocultar Título -->
                <div class="flex items-center justify-between p-4 bg-[var(--bg-sidebar)] rounded-xl border border-[var(--border-color)]">
                    <div>
                        <label class="font-semibold block mb-1">Ocultar Título del Capítulo</label>
                        <span class="text-xs text-[var(--text-muted)]">No muestra el título en el PDF. Ideal para prólogos o continuaciones.</span>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="chapter_hide_title" name="hide_chapter_title" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                    </label>
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
                
                <!-- Mostrar cabecera en página 1 -->
                <div class="flex items-center justify-between p-4 bg-[var(--bg-sidebar)] rounded-xl border border-[var(--border-color)]">
                    <div>
                        <label class="font-semibold block mb-1">Mostrar Cabecera en la Página 1</label>
                        <span class="text-xs text-[var(--text-muted)]">Normalmente las cabeceras se ocultan al inicio de un capítulo.</span>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="chapter_show_header_page_one" name="show_header_page_one" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                    </label>
                </div>

                <!-- Modo de Imagen de Paridad -->
                <div class="grid grid-cols-1 gap-4 pb-4">
                    <div>
                        <label class="block font-semibold mb-1">Modo de Imagen de Paridad</label>
                        <p class="text-xs text-[var(--text-muted)] mb-2">Define si la imagen que llena la página en blanco se extiende hasta el borde (Sangría) o respeta los márgenes.</p>
                        <select id="chapter_parity_image_mode" name="parity_image_mode" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg px-3 py-2 text-[var(--text-main)] focus:outline-none focus:border-indigo-500">
                            <option value="content">Dentro de los márgenes</option>
                            <option value="bleed">A Sangría (100% de la hoja + Extra Bleed)</option>
                        </select>
                    </div>
                </div>

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
