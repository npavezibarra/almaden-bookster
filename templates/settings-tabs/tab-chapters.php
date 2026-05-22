<div id="tab-chapters" class="setting-tab-content space-y-4 hidden">
                <div>
                    <h4 class="text-[10px] font-bold uppercase tracking-wider text-indigo-500 mb-2 border-b border-[var(--border-color)] pb-1">Flujo y Páginas de Inicio</h4>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[9px] text-[var(--text-muted)] mb-1">Forzar Inicio de Capítulo</label>
                            <select id="setting-chapter-start-parity" onchange="toggleParityImageMode()" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="any">Cualquier página (Corrido)</option>
                                <option value="odd">Página impar (Lado derecho - Recomendado)</option>
                                <option value="even">Página par (Lado izquierdo)</option>
                            </select>
                        </div>
                        <div id="parity-image-mode-wrapper" class="hidden">
                            <label class="block text-[9px] text-[var(--text-muted)] mb-1">Imagen de Paridad (Modo)</label>
                            <select id="setting-parity-image-mode" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="content">Ajustar al Contenido (Excluye Header/Footer)</option>
                                <option value="bleed">Sangría (100% Página + 5mm Bleed)</option>
                                <option value="fullpage">Pantalla Completa (100% Página sin Bleed)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div>
                    <h4 class="text-[10px] font-bold uppercase tracking-wider text-indigo-500 mb-2 border-b border-[var(--border-color)] pb-1">Diseño de Página 1 del Capítulo</h4>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[9px] text-[var(--text-muted)] mb-1">Alineación del Título</label>
                            <select id="setting-chapter-page-one-align" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="center">Centrado</option>
                                <option value="left">Alineado Izquierda</option>
                                <option value="right">Alineado Derecha</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[9px] text-[var(--text-muted)] mb-1">Diseño Vertical</label>
                            <select id="setting-chapter-page-one-vertical" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="top">Arriba (Margen estándar)</option>
                                <option value="half">Media Página (Centrado vertical)</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div>
                    <h4 class="text-[10px] font-bold uppercase tracking-wider text-indigo-500 mb-2 border-b border-[var(--border-color)] pb-1">Formato del Título de Capítulo</h4>
                    <div class="grid grid-cols-2 gap-3 mb-2">
                        <div class="col-span-2">
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Familia de Fuente</label>
                            <select id="setting-chapter-title-font-family" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <?php almaden_render_font_options( $heading_default_fonts, $selector_fonts ); ?>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-4 gap-2 mb-2">
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Tamaño (pt)</label>
                            <input id="setting-chapter-title-font-size" type="number" step="0.5" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Alineación</label>
                            <select id="setting-chapter-title-align" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="left">Izquierda</option>
                                <option value="center">Centro</option>
                                <option value="right">Derecha</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Estilo</label>
                            <select id="setting-chapter-title-font-style" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="normal">Normal</option>
                                <option value="italic">Cursiva</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Peso</label>
                            <select id="setting-chapter-title-font-weight" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
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
                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Padding Arriba (cm)</label>
                            <input id="setting-chapter-title-padding-top" type="number" step="0.1" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Padding Abajo (cm)</label>
                            <input id="setting-chapter-title-padding-bottom" type="number" step="0.1" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Altura de Línea</label>
                            <input id="setting-chapter-title-line-height" type="number" step="0.1" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>
                </div>
            </div>
