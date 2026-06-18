<div id="tab-page" class="setting-tab-content space-y-4">
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
                </div>

                <div id="custom-page-dimensions" class="grid grid-cols-2 gap-4 hidden">
                    <div>
                        <label class="block text-[10px] font-semibold uppercase tracking-wider text-[var(--text-muted)] mb-1">Ancho (<span class="unit-label">cm</span>)</label>
                        <input id="setting-page-width" type="number" step="0.01" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-2 text-xs focus:ring-2 focus:ring-black focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold uppercase tracking-wider text-[var(--text-muted)] mb-1">Alto (<span class="unit-label">cm</span>)</label>
                        <input id="setting-page-height" type="number" step="0.01" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-2 text-xs focus:ring-2 focus:ring-black focus:outline-none">
                    </div>
                </div>

                <div>
                    <h4 class="text-[10px] font-bold uppercase tracking-wider text-black dark:text-white mb-2 border-b border-[var(--border-color)] pb-1">Márgenes Globales (Arriba / Abajo)</h4>
                    <div class="grid grid-cols-2 gap-2 mb-3">
                        <div>
                            <label class="block text-[9px] text-[var(--text-muted)] mb-1">Arriba (<span class="unit-label">cm</span>)</label>
                            <input id="setting-margin-top" type="number" step="0.01" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                        <div>
                            <label class="block text-[9px] text-[var(--text-muted)] mb-1">Abajo (<span class="unit-label">cm</span>)</label>
                            <input id="setting-margin-bottom" type="number" step="0.01" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                    </div>
                    
                    <h4 class="text-[10px] font-bold uppercase tracking-wider text-black dark:text-white mb-2 border-b border-[var(--border-color)] pb-1">Márgenes de Encuadernación</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2 bg-[var(--bg-app)] p-2 rounded-lg border border-[var(--border-color)]">
                            <label class="block text-[10px] font-bold text-[var(--text-main)] text-center">Página Impar (Derecha)</label>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-[9px] text-[var(--text-muted)] mb-1">Interior/Izq (<span class="unit-label">cm</span>)</label>
                                    <input id="setting-margin-left-odd" type="number" step="0.01" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-black">
                                </div>
                                <div>
                                    <label class="block text-[9px] text-[var(--text-muted)] mb-1">Exterior/Der (<span class="unit-label">cm</span>)</label>
                                    <input id="setting-margin-right-odd" type="number" step="0.01" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-black">
                                </div>
                            </div>
                        </div>
                        <div class="space-y-2 bg-[var(--bg-app)] p-2 rounded-lg border border-[var(--border-color)]">
                            <label class="block text-[10px] font-bold text-[var(--text-main)] text-center">Página Par (Izquierda)</label>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-[9px] text-[var(--text-muted)] mb-1">Exterior/Izq (<span class="unit-label">cm</span>)</label>
                                    <input id="setting-margin-left-even" type="number" step="0.01" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-black">
                                </div>
                                <div>
                                    <label class="block text-[9px] text-[var(--text-muted)] mb-1">Interior/Der (<span class="unit-label">cm</span>)</label>
                                    <input id="setting-margin-right-even" type="number" step="0.01" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-black">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <h4 class="text-[10px] font-bold uppercase tracking-wider text-black dark:text-white mb-2 border-b border-[var(--border-color)] pb-1">Padding de Contenido</h4>
                    <div class="grid grid-cols-4 gap-2">
                        <div>
                            <label class="block text-[9px] text-[var(--text-muted)] mb-1">Arriba (<span class="unit-label">cm</span>)</label>
                            <input id="setting-padding-top" type="number" step="0.01" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                        <div>
                            <label class="block text-[9px] text-[var(--text-muted)] mb-1">Abajo (<span class="unit-label">cm</span>)</label>
                            <input id="setting-padding-bottom" type="number" step="0.01" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                        <div>
                            <label class="block text-[9px] text-[var(--text-muted)] mb-1">Izquierda (<span class="unit-label">cm</span>)</label>
                            <input id="setting-padding-left" type="number" step="0.01" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                        <div>
                            <label class="block text-[9px] text-[var(--text-muted)] mb-1">Derecha (<span class="unit-label">cm</span>)</label>
                            <input id="setting-padding-right" type="number" step="0.01" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                    </div>
                </div>

                <div>
                    <h4 class="text-[10px] font-bold uppercase tracking-wider text-black dark:text-white mb-2 border-b border-[var(--border-color)] pb-1">Sangría / Bleeding</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-semibold text-[var(--text-muted)] mb-1">Espacio de Sangría (<span class="unit-label">cm</span>)</label>
                            <input id="setting-bleeding" type="number" step="0.01" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-2 text-xs focus:ring-2 focus:ring-black focus:outline-none">
                        </div>
                    </div>
                </div>
            </div>
