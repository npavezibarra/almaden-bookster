<div id="tab-typography" class="setting-tab-content space-y-4 hidden">
                <nav class="settings-inner-tabs" role="tablist" aria-label="Tipos de tipografía">
                    <button type="button" role="tab" aria-controls="typography-body-panel" id="btn-typography-body" class="typography-tab-btn header-footer-tab-btn is-active" onclick="switchTypographyTab('body')" aria-selected="true">Cuerpo</button>
                    <button type="button" role="tab" aria-controls="typography-headings-panel" id="btn-typography-headings" class="typography-tab-btn header-footer-tab-btn" onclick="switchTypographyTab('headings')" aria-selected="false">Títulos</button>
                </nav>

                <div id="typography-body-panel" class="typography-tab-panel" role="tabpanel" aria-labelledby="btn-typography-body">
                <section class="settings-section-card">
                    <h4><i class="fa-solid fa-font" aria-hidden="true"></i> Tipografía del cuerpo</h4>
                    <div class="settings-section-card-body space-y-4">
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
                    <div class="grid grid-cols-5 gap-2 mt-2">
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
                            <label class="block text-[9px] text-[var(--text-muted)] mb-1">Última línea</label>
                            <select id="setting-content-text-align-last" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                                <option value="left">Izquierda</option>
                                <option value="center">Centro</option>
                                <option value="right">Derecha</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[9px] text-[var(--text-muted)] mb-1">Separación silábica</label>
                            <select id="setting-content-hyphenation" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
                                <option value="1">Activado</option>
                                <option value="0">Desactivado</option>
                            </select>
                        </div>
                        <input id="setting-content-language" type="hidden" value="es">
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
                    <div class="mt-2">
                        <label class="block text-[9px] text-[var(--text-muted)] mb-1">Excepciones de separación silábica</label>
                        <textarea id="setting-content-hyphenation-exceptions" rows="3" placeholder="realidad, prohibición, extraordinario" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-2 text-xs focus:outline-none focus:ring-2 focus:ring-black"></textarea>
                        <p class="mt-1 text-[10px] text-[var(--text-muted)]">Una palabra por línea o separadas por comas. Estas palabras no se partirán al final de línea.</p>
                    </div>
                    </div>
                </section>
                </div>

                <div id="typography-headings-panel" class="typography-tab-panel hidden" role="tabpanel" aria-labelledby="btn-typography-headings">
                <section class="settings-section-card">
                    <h4><i class="fa-solid fa-heading" aria-hidden="true"></i> Tipografía de títulos</h4>
                    <div class="settings-section-card-body">
                    
                    <div class="space-y-3">
                        <?php
                        $heading_levels = array(
                            'h1' => 'Título principal (H1)',
                            'h2' => 'Subtítulo (H2)',
                            'h3' => 'Tercer nivel (H3)',
                        );
                        foreach ( $heading_levels as $heading_key => $heading_label ) :
                        ?>
                        <div class="settings-type-level-card p-2 border border-[var(--border-color)] rounded-lg bg-[var(--bg-app)]">
                            <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                                <span class="settings-type-level-title text-[9px] font-bold text-[var(--text-main)] block"><?php echo esc_html( $heading_label ); ?></span>
                                <nav class="settings-inner-tabs" role="tablist" aria-label="<?php echo esc_attr( 'Ajustes ' . strtoupper( $heading_key ) ); ?>">
                                    <button type="button" role="tab" aria-controls="typography-<?php echo esc_attr( $heading_key ); ?>-font-panel" id="btn-typography-<?php echo esc_attr( $heading_key ); ?>-font" class="heading-typography-tab-btn header-footer-tab-btn is-active" onclick="switchHeadingTypographyTab('<?php echo esc_attr( $heading_key ); ?>', 'font')" aria-selected="true">Fuente</button>
                                    <button type="button" role="tab" aria-controls="typography-<?php echo esc_attr( $heading_key ); ?>-composition-panel" id="btn-typography-<?php echo esc_attr( $heading_key ); ?>-composition" class="heading-typography-tab-btn header-footer-tab-btn" onclick="switchHeadingTypographyTab('<?php echo esc_attr( $heading_key ); ?>', 'composition')" aria-selected="false">Composición</button>
                                </nav>
                            </div>
                            <div id="typography-<?php echo esc_attr( $heading_key ); ?>-font-panel" class="heading-typography-panel" role="tabpanel" aria-labelledby="btn-typography-<?php echo esc_attr( $heading_key ); ?>-font">
                                <div class="grid grid-cols-4 gap-2">
                                    <div>
                                        <label class="block text-[8px] text-[var(--text-muted)] mb-1">Familia</label>
                                        <select id="setting-font-family-<?php echo esc_attr( $heading_key ); ?>" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded p-1 text-xs focus:outline-none focus:ring-1 focus:ring-black">
                                            <?php almaden_render_font_options( $heading_default_fonts, $selector_fonts ); ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-[8px] text-[var(--text-muted)] mb-1">Tamaño (pt)</label>
                                        <input id="setting-font-size-<?php echo esc_attr( $heading_key ); ?>" type="number" step="0.5" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded p-1 text-xs text-center focus:outline-none focus:ring-1 focus:ring-black">
                                    </div>
                                    <div>
                                        <label class="block text-[8px] text-[var(--text-muted)] mb-1">Estilo</label>
                                        <select id="setting-font-style-<?php echo esc_attr( $heading_key ); ?>" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded p-1 text-xs focus:outline-none focus:ring-1 focus:ring-black">
                                            <option value="normal">Normal</option>
                                            <option value="italic">Cursiva</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-[8px] text-[var(--text-muted)] mb-1">Peso</label>
                                        <select id="setting-font-weight-<?php echo esc_attr( $heading_key ); ?>" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded p-1 text-xs focus:outline-none focus:ring-1 focus:ring-black">
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
                            <div id="typography-<?php echo esc_attr( $heading_key ); ?>-composition-panel" class="heading-typography-panel hidden" role="tabpanel" aria-labelledby="btn-typography-<?php echo esc_attr( $heading_key ); ?>-composition">
                                <div class="grid grid-cols-3 gap-2">
                                    <div>
                                        <label class="block text-[8px] text-[var(--text-muted)] mb-1">Interlineado</label>
                                        <input id="setting-line-height-<?php echo esc_attr( $heading_key ); ?>" type="number" step="0.05" min="0.8" max="4" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded p-1 text-xs text-center focus:outline-none focus:ring-1 focus:ring-black">
                                    </div>
                                    <div>
                                        <label class="block text-[8px] text-[var(--text-muted)] mb-1">Alineación</label>
                                        <select id="setting-text-align-<?php echo esc_attr( $heading_key ); ?>" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded p-1 text-xs focus:outline-none focus:ring-1 focus:ring-black">
                                            <option value="left">Izquierda</option>
                                            <option value="center">Centro</option>
                                            <option value="right">Derecha</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-[8px] text-[var(--text-muted)] mb-1">Espaciado letras (pt)</label>
                                        <input id="setting-letter-spacing-<?php echo esc_attr( $heading_key ); ?>" type="number" step="0.1" min="-20" max="20" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded p-1 text-xs text-center focus:outline-none focus:ring-1 focus:ring-black">
                                    </div>
                                </div>
                                <label class="mt-3 flex items-center gap-2 text-xs font-semibold text-[var(--text-main)]">
                                    <input id="setting-hyphenate-<?php echo esc_attr( $heading_key ); ?>" type="checkbox" class="rounded border-[var(--border-color)] text-black focus:ring-black bg-[var(--bg-app)] h-4 w-4">
                                    <span>Hyphenate</span>
                                </label>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    </div>
                </section>
                </div>
            </div>
