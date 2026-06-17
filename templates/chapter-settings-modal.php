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
                <?php include plugin_dir_path( __FILE__ ) . 'chapter-settings-normal.php'; ?>

                <!-- Ajustes exclusivos para la Tabla de Contenidos -->
                <?php include plugin_dir_path( __FILE__ ) . 'chapter-settings-toc.php'; ?>

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
