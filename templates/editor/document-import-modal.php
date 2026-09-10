<!-- Modal de Importación de Documento -->
<div id="document-import-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 backdrop-blur-sm opacity-0 transition-opacity">
    <div class="w-full max-w-3xl max-h-[90vh] overflow-hidden rounded-2xl border border-[var(--border-color)] bg-[var(--bg-app)] shadow-2xl transform scale-95 opacity-0 transition-all flex flex-col">
        <div class="flex items-start justify-between gap-4 border-b border-[var(--border-color)] bg-[var(--bg-sidebar)] px-6 py-4">
            <div>
                <h3 class="text-lg font-bold text-[var(--text-main)] flex items-center gap-2">
                    <i class="fa-solid fa-file-import text-sky-600"></i>
                    Upload Document
                </h3>
                <p class="mt-1 text-xs text-[var(--text-muted)]">
                    Sube un archivo y define qué estilo separa cada capítulo antes de importarlo.
                </p>
            </div>
            <button type="button" onclick="closeDocumentImportModal()" class="p-2 text-[var(--text-muted)] hover:text-rose-500 transition-colors">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto p-6 space-y-6">
            <section class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-5">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-slate-900">1. Sube el archivo</p>
                        <p class="text-xs text-slate-500 mt-1">Formatos soportados: `.docx`, `.rtf`, `.txt`, `.pdf`.</p>
                    </div>
                    <label class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-black px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-black/20 hover:bg-neutral-800 transition">
                        <i class="fa-solid fa-upload"></i>
                        <span>Seleccionar archivo</span>
                        <input id="document-import-file" type="file" accept=".docx,.rtf,.txt,.pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/rtf,text/plain,application/pdf" class="hidden">
                    </label>
                </div>
                <div id="document-import-file-meta" class="mt-4 hidden rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="font-semibold text-slate-900" id="document-import-file-name">-</span>
                        <span class="text-xs text-slate-500" id="document-import-file-size">-</span>
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600" id="document-import-file-type">-</span>
                    </div>
                </div>
            </section>

            <section id="document-import-analysis" class="hidden rounded-2xl border border-[var(--border-color)] bg-[var(--bg-sidebar)] p-5">
                <div class="flex flex-col gap-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-[var(--text-main)]">2. Análisis automático</p>
                            <p class="text-xs text-[var(--text-muted)] mt-1">El sistema detectó estilos y posibles capítulos.</p>
                        </div>
                        <span id="document-import-status" class="inline-flex items-center gap-2 rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold text-sky-700">
                            <i class="fa-solid fa-circle-notch fa-spin"></i>
                            Esperando archivo
                        </span>
                    </div>

                    <div class="grid gap-3 md:grid-cols-5">
                        <div class="rounded-xl border border-[var(--border-color)] bg-[var(--bg-app)] p-3">
                            <p class="text-[10px] uppercase tracking-wider text-[var(--text-muted)]">Formato</p>
                            <p id="document-import-format" class="mt-1 text-sm font-semibold text-[var(--text-main)]">-</p>
                        </div>
                        <div class="rounded-xl border border-[var(--border-color)] bg-[var(--bg-app)] p-3">
                            <p class="text-[10px] uppercase tracking-wider text-[var(--text-muted)]">Bloques</p>
                            <p id="document-import-blocks" class="mt-1 text-sm font-semibold text-[var(--text-main)]">-</p>
                        </div>
                        <div class="rounded-xl border border-[var(--border-color)] bg-[var(--bg-app)] p-3">
                            <p class="text-[10px] uppercase tracking-wider text-[var(--text-muted)]">Capítulos sugeridos</p>
                            <p id="document-import-chapters" class="mt-1 text-sm font-semibold text-[var(--text-main)]">-</p>
                        </div>
                        <div class="rounded-xl border border-[var(--border-color)] bg-[var(--bg-app)] p-3">
                            <p class="text-[10px] uppercase tracking-wider text-[var(--text-muted)]">Tablas</p>
                            <p id="document-import-tables" class="mt-1 text-sm font-semibold text-[var(--text-main)]">-</p>
                        </div>
                        <div class="rounded-xl border border-[var(--border-color)] bg-[var(--bg-app)] p-3">
                            <p class="text-[10px] uppercase tracking-wider text-[var(--text-muted)]">Confianza</p>
                            <p id="document-import-confidence" class="mt-1 text-sm font-semibold text-[var(--text-main)]">-</p>
                        </div>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-[var(--text-muted)] mb-3">Estilos detectados</p>
                        <div id="document-import-style-cards" class="flex flex-wrap gap-2"></div>
                    </div>
                </div>
            </section>

            <section id="document-import-separator-section" class="hidden rounded-2xl border border-[var(--border-color)] bg-[var(--bg-app)] p-5">
                <p class="text-sm font-semibold text-[var(--text-main)]">3. ¿Qué estilo separa cada capítulo?</p>
                <p class="mt-1 text-xs text-[var(--text-muted)]">Elige el estilo que funciona como frontera principal de capítulo.</p>
                <div id="document-import-separator-options" class="mt-4 grid gap-3 md:grid-cols-2"></div>
            </section>

            <section id="document-import-mapping-section" class="hidden rounded-2xl border border-[var(--border-color)] bg-[var(--bg-sidebar)] p-5">
                <div class="flex flex-col gap-4">
                    <div>
                        <p class="text-sm font-semibold text-[var(--text-main)]">4. Ajusta la jerarquía interna</p>
                        <p class="mt-1 text-xs text-[var(--text-muted)]">Aquí puedes corregir qué estilo debe convertirse en Title, Subtitle, Heading 1, Heading 2 y Heading 3.</p>
                    </div>
                    <div id="document-import-mapping-options" class="grid gap-3 lg:grid-cols-2"></div>
                </div>
            </section>

            <section id="document-import-table-style-section" class="hidden rounded-2xl border border-[var(--border-color)] bg-[var(--bg-app)] p-5">
                <div class="flex flex-col gap-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-[var(--text-main)]">5. Estilo de tablas detectadas</p>
                            <p class="mt-1 text-xs text-[var(--text-muted)]">Se aplicará a las <span id="document-import-table-count">0</span> tablas detectadas durante la importación.</p>
                        </div>
                        <select id="document-import-table-enabled" data-import-table-style-field class="rounded-xl border border-[var(--border-color)] bg-[var(--bg-sidebar)] px-3 py-2 text-xs font-semibold text-[var(--text-main)] focus:outline-none focus:ring-2 focus:ring-sky-500">
                            <option value="1">Caja editorial</option>
                            <option value="0">Texto plano</option>
                        </select>
                    </div>
                    <div class="grid gap-3 md:grid-cols-3">
                        <label class="text-xs font-semibold text-[var(--text-muted)]">
                            Fondo
                            <input id="document-import-table-background" data-import-table-style-field type="color" class="mt-1 h-10 w-full rounded-xl border border-[var(--border-color)] bg-[var(--bg-sidebar)] px-2 py-1">
                        </label>
                        <label class="text-xs font-semibold text-[var(--text-muted)]">
                            Borde
                            <input id="document-import-table-border-color" data-import-table-style-field type="color" class="mt-1 h-10 w-full rounded-xl border border-[var(--border-color)] bg-[var(--bg-sidebar)] px-2 py-1">
                        </label>
                        <label class="text-xs font-semibold text-[var(--text-muted)]">
                            Alineación
                            <select id="document-import-table-text-align" data-import-table-style-field class="mt-1 w-full rounded-xl border border-[var(--border-color)] bg-[var(--bg-sidebar)] px-3 py-2 text-sm text-[var(--text-main)] focus:outline-none focus:ring-2 focus:ring-sky-500">
                                <option value="left">Izquierda</option>
                                <option value="center">Centro</option>
                                <option value="right">Derecha</option>
                                <option value="justify">Justificado</option>
                            </select>
                        </label>
                        <label class="text-xs font-semibold text-[var(--text-muted)]">
                            Grosor borde px
                            <input id="document-import-table-border-width" data-import-table-style-field type="number" min="0" max="12" step="1" class="mt-1 w-full rounded-xl border border-[var(--border-color)] bg-[var(--bg-sidebar)] px-3 py-2 text-sm text-[var(--text-main)] focus:outline-none focus:ring-2 focus:ring-sky-500">
                        </label>
                        <label class="text-xs font-semibold text-[var(--text-muted)]">
                            Padding vertical px
                            <input id="document-import-table-padding-y" data-import-table-style-field type="number" min="0" max="80" step="1" class="mt-1 w-full rounded-xl border border-[var(--border-color)] bg-[var(--bg-sidebar)] px-3 py-2 text-sm text-[var(--text-main)] focus:outline-none focus:ring-2 focus:ring-sky-500">
                        </label>
                        <label class="text-xs font-semibold text-[var(--text-muted)]">
                            Padding horizontal px
                            <input id="document-import-table-padding-x" data-import-table-style-field type="number" min="0" max="100" step="1" class="mt-1 w-full rounded-xl border border-[var(--border-color)] bg-[var(--bg-sidebar)] px-3 py-2 text-sm text-[var(--text-main)] focus:outline-none focus:ring-2 focus:ring-sky-500">
                        </label>
                        <label class="text-xs font-semibold text-[var(--text-muted)]">
                            Radio px
                            <input id="document-import-table-border-radius" data-import-table-style-field type="number" min="0" max="48" step="1" class="mt-1 w-full rounded-xl border border-[var(--border-color)] bg-[var(--bg-sidebar)] px-3 py-2 text-sm text-[var(--text-main)] focus:outline-none focus:ring-2 focus:ring-sky-500">
                        </label>
                        <label class="text-xs font-semibold text-[var(--text-muted)]">
                            Tamaño fuente px
                            <input id="document-import-table-font-size" data-import-table-style-field type="number" min="8" max="32" step="0.5" placeholder="Global" class="mt-1 w-full rounded-xl border border-[var(--border-color)] bg-[var(--bg-sidebar)] px-3 py-2 text-sm text-[var(--text-main)] focus:outline-none focus:ring-2 focus:ring-sky-500">
                        </label>
                        <label class="text-xs font-semibold text-[var(--text-muted)]">
                            Interlineado
                            <input id="document-import-table-line-height" data-import-table-style-field type="number" min="1" max="3" step="0.05" class="mt-1 w-full rounded-xl border border-[var(--border-color)] bg-[var(--bg-sidebar)] px-3 py-2 text-sm text-[var(--text-main)] focus:outline-none focus:ring-2 focus:ring-sky-500">
                        </label>
                    </div>
                </div>
            </section>

            <section id="document-import-validation-section" class="hidden rounded-2xl border border-[var(--border-color)] bg-[var(--bg-app)] p-5">
                <div class="flex items-start gap-3">
                    <div id="document-import-validation-icon" class="mt-0.5 flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-600">
                        <i class="fa-solid fa-circle-info"></i>
                    </div>
                    <div class="flex-1">
                        <p id="document-import-validation-title" class="text-sm font-semibold text-[var(--text-main)]">Validación de estructura</p>
                        <div id="document-import-validation-list" class="mt-2 space-y-2 text-sm text-[var(--text-main)]"></div>
                    </div>
                </div>
            </section>

            <section id="document-import-preview-section" class="hidden rounded-2xl border border-[var(--border-color)] bg-[var(--bg-sidebar)] p-5">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-[var(--text-main)]">5. Vista previa de capítulos</p>
                        <p class="text-xs text-[var(--text-muted)] mt-1">Así quedará la importación antes de confirmarla.</p>
                    </div>
                    <span id="document-import-preview-count" class="rounded-full bg-neutral-200 px-3 py-1 text-xs font-semibold text-neutral-700">0</span>
                </div>
                <div id="document-import-preview-list" class="mt-4 space-y-2"></div>
            </section>
        </div>

        <div class="flex flex-col gap-3 border-t border-[var(--border-color)] bg-[var(--bg-sidebar)] px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
            <p id="document-import-hint" class="text-xs text-[var(--text-muted)]">Primero selecciona un archivo para analizarlo.</p>
            <div class="flex flex-wrap gap-3">
                <button type="button" onclick="closeDocumentImportModal()" class="rounded-lg border border-[var(--border-color)] px-4 py-2 text-sm font-semibold text-[var(--text-main)] hover:bg-[var(--bg-app)] transition">
                    Cancelar
                </button>
                <button id="document-import-analyze-btn" type="button" onclick="analyzeDocumentImport()" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-black transition disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                    Analizar archivo
                </button>
                <button id="document-import-commit-btn" type="button" onclick="commitDocumentImport()" class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700 transition disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                    Importar capítulos
                </button>
            </div>
        </div>
    </div>
</div>
