<div id="tab-page" class="setting-tab-content space-y-4">
            <section class="settings-section-card">
                <h4><i class="fa-solid fa-file-lines" aria-hidden="true"></i> Formato de página</h4>
                <div class="settings-section-card-body space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-semibold uppercase tracking-wider text-[var(--text-muted)] mb-1">Unidad de Medida</label>
                        <select id="setting-unit" onchange="updateUnitFields()" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-2 text-xs focus:ring-2 focus:ring-black focus:outline-none">
                            <option value="cm">Centímetros (cm)</option>
                            <option value="in">Pulgadas (inches)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold uppercase tracking-wider text-[var(--text-muted)] mb-1">Tamaño de Página</label>
                        <select id="setting-page-size" onchange="toggleCustomPageFields()" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-2 text-xs focus:ring-2 focus:ring-black focus:outline-none">
                            <option value="A4">A4 (21 x 29.7 cm)</option>
                            <option value="Letter">Carta / Letter (8.5 x 11 in)</option>
                            <option value="Custom">Personalizado</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold uppercase tracking-wider text-[var(--text-muted)] mb-1">Color de Impresión</label>
                        <label class="relative inline-flex items-center cursor-pointer mt-1">
                            <input type="checkbox" id="setting-export-grayscale" class="sr-only peer">
                            <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-black rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-black"></div>
                            <span class="ml-2 text-xs font-semibold text-[var(--text-main)]">Forzar Blanco y Negro</span>
                        </label>
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold uppercase tracking-wider text-[var(--text-muted)] mb-1">Columnas</label>
                        <label class="relative inline-flex items-center cursor-pointer mt-1">
                            <input type="checkbox" id="setting-page-columns-enabled" class="sr-only peer" onchange="togglePageColumnsSettings()">
                            <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-black rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-black"></div>
                            <span class="ml-2 text-xs font-semibold text-[var(--text-main)]">Activar columnas</span>
                        </label>
                    </div>
                </div>

                <div id="setting-page-columns-fields" class="grid grid-cols-2 gap-4 hidden">
                    <div>
                        <label class="block text-[10px] font-semibold uppercase tracking-wider text-[var(--text-muted)] mb-1">Número de columnas</label>
                        <input id="setting-page-columns-count" type="number" min="1" max="4" step="1" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-2 text-xs focus:ring-2 focus:ring-black focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold uppercase tracking-wider text-[var(--text-muted)] mb-1">Gap entre columnas (<span class="unit-label">cm</span>)</label>
                        <input id="setting-page-columns-gap" type="number" min="0" step="0.1" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-2 text-xs focus:ring-2 focus:ring-black focus:outline-none">
                    </div>
                </div>

                <div id="custom-page-dimensions" class="grid grid-cols-2 gap-4 hidden">
                    <div>
                        <label class="block text-[10px] font-semibold uppercase tracking-wider text-[var(--text-muted)] mb-1">Ancho (<span class="unit-label">cm</span>)</label>
                        <input id="setting-page-width" type="number" step="0.1" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-2 text-xs focus:ring-2 focus:ring-black focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold uppercase tracking-wider text-[var(--text-muted)] mb-1">Alto (<span class="unit-label">cm</span>)</label>
                        <input id="setting-page-height" type="number" step="0.1" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-2 text-xs focus:ring-2 focus:ring-black focus:outline-none">
                    </div>
                </div>
                </div>
            </section>

            <section class="settings-section-card">
                <h4><i class="fa-solid fa-ruler-combined" aria-hidden="true"></i> Márgenes y área de contenido</h4>
                <div class="settings-section-card-body space-y-5">
                <div>
                    <h5 class="settings-group-title">Márgenes superior e inferior</h5>
                    <div class="grid grid-cols-2 gap-2 mb-3">
                        <div>
                            <label class="block text-[9px] text-[var(--text-muted)] mb-1">Arriba (<span class="unit-label">cm</span>)</label>
                            <input id="setting-margin-top" type="number" step="0.1" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                        <div>
                            <label class="block text-[9px] text-[var(--text-muted)] mb-1">Abajo (<span class="unit-label">cm</span>)</label>
                            <input id="setting-margin-bottom" type="number" step="0.1" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                    </div>
                    
                    <h5 class="settings-group-title">Márgenes de encuadernación</h5>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2 bg-[var(--bg-app)] p-2 rounded-lg border border-[var(--border-color)]">
                            <label class="block text-[10px] font-bold text-[var(--text-main)] text-center">Página Impar (Derecha)</label>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-[9px] text-[var(--text-muted)] mb-1">Interior/Izq (<span class="unit-label">cm</span>)</label>
                                    <input id="setting-margin-left-odd" type="number" step="0.1" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-black">
                                </div>
                                <div>
                                    <label class="block text-[9px] text-[var(--text-muted)] mb-1">Exterior/Der (<span class="unit-label">cm</span>)</label>
                                    <input id="setting-margin-right-odd" type="number" step="0.1" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-black">
                                </div>
                            </div>
                        </div>
                        <div class="space-y-2 bg-[var(--bg-app)] p-2 rounded-lg border border-[var(--border-color)]">
                            <label class="block text-[10px] font-bold text-[var(--text-main)] text-center">Página Par (Izquierda)</label>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-[9px] text-[var(--text-muted)] mb-1">Exterior/Izq (<span class="unit-label">cm</span>)</label>
                                    <input id="setting-margin-left-even" type="number" step="0.1" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-black">
                                </div>
                                <div>
                                    <label class="block text-[9px] text-[var(--text-muted)] mb-1">Interior/Der (<span class="unit-label">cm</span>)</label>
                                    <input id="setting-margin-right-even" type="number" step="0.1" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-black">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <h5 class="settings-group-title">Área de contenido</h5>
                    <div class="grid grid-cols-4 gap-2">
                        <div>
                            <label class="block text-[9px] text-[var(--text-muted)] mb-1">Arriba (<span class="unit-label">cm</span>)</label>
                            <input id="setting-padding-top" type="number" step="0.1" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                        <div>
                            <label class="block text-[9px] text-[var(--text-muted)] mb-1">Abajo (<span class="unit-label">cm</span>)</label>
                            <input id="setting-padding-bottom" type="number" step="0.1" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                        <div>
                            <label class="block text-[9px] text-[var(--text-muted)] mb-1">Izquierda (<span class="unit-label">cm</span>)</label>
                            <input id="setting-padding-left" type="number" step="0.1" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                        <div>
                            <label class="block text-[9px] text-[var(--text-muted)] mb-1">Derecha (<span class="unit-label">cm</span>)</label>
                            <input id="setting-padding-right" type="number" step="0.1" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                    </div>
                </div>
                </div>
            </section>

            <section class="settings-section-card">
                <h4><i class="fa-solid fa-scissors" aria-hidden="true"></i> Sangrado</h4>
                <div class="settings-section-card-body">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-semibold text-[var(--text-muted)] mb-1">Espacio de sangrado (<span class="unit-label">cm</span>)</label>
                            <input id="setting-bleeding" type="number" step="0.1" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-2 text-xs focus:ring-2 focus:ring-black focus:outline-none">
                        </div>
                    </div>
                </div>
            </section>
            </div>
