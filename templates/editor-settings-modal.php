<?php
// Cargar fuentes instaladas para los selectores
$selector_fonts = almaden_bookster_get_installed_fonts_list();

// Fuentes predeterminadas que siempre están disponibles
$default_fonts = array(
	array( 'family' => 'Merriweather', 'category' => 'serif', 'label' => 'Merriweather (Serif)' ),
	array( 'family' => 'Georgia', 'category' => 'serif', 'label' => 'Georgia (Serif)' ),
	array( 'family' => 'Baskerville', 'category' => 'serif', 'label' => 'Baskerville (Serif tradicional)' ),
	array( 'family' => 'Lora', 'category' => 'serif', 'label' => 'Lora (Serif elegante)' ),
	array( 'family' => 'Inter', 'category' => 'sans-serif', 'label' => 'Inter (Sans-Serif moderno)' ),
	array( 'family' => 'Garamond', 'category' => 'serif', 'label' => 'Garamond (Serif clásico)' ),
);

$heading_default_fonts = array(
	array( 'family' => 'Playfair Display', 'category' => 'serif', 'label' => 'Playfair Display (Serif de alto contraste)' ),
	array( 'family' => 'Lora', 'category' => 'serif', 'label' => 'Lora (Serif clásica)' ),
	array( 'family' => 'Cinzel', 'category' => 'serif', 'label' => 'Cinzel (Serif clásico de estilo romano)' ),
	array( 'family' => 'Cormorant Garamond', 'category' => 'serif', 'label' => 'Cormorant Garamond (Serif fina y elegante)' ),
	array( 'family' => 'Georgia', 'category' => 'serif', 'label' => 'Georgia (Serif común)' ),
	array( 'family' => 'Outfit', 'category' => 'sans-serif', 'label' => 'Outfit (Sans-Serif geométrica)' ),
	array( 'family' => 'Inter', 'category' => 'sans-serif', 'label' => 'Inter (Sans-Serif neutra)' ),
);

$hf_default_fonts = array(
	array( 'family' => 'Merriweather', 'category' => 'serif', 'label' => 'Merriweather (Serif)' ),
	array( 'family' => 'Georgia', 'category' => 'serif', 'label' => 'Georgia (Serif)' ),
	array( 'family' => 'Inter', 'category' => 'sans-serif', 'label' => 'Inter (Sans-serif)' ),
);

// Función auxiliar para renderizar opciones de fuentes
function almaden_render_font_options( $defaults, $installed ) {
	$rendered_families = array();
	foreach ( $defaults as $font ) {
		$rendered_families[] = $font['family'];
		echo '<option value="' . esc_attr( $font['family'] ) . '">' . esc_html( $font['label'] ) . '</option>' . "\n";
	}
	if ( ! empty( $installed ) ) {
		$has_extra = false;
		foreach ( $installed as $ifont ) {
			if ( ! in_array( $ifont['family'], $rendered_families, true ) ) {
				if ( ! $has_extra ) {
					echo '<option disabled>── Instaladas ──</option>' . "\n";
					$has_extra = true;
				}
				$label = $ifont['family'] . ' (' . ucfirst( $ifont['category'] ) . ')';
				echo '<option value="' . esc_attr( $ifont['family'] ) . '">' . esc_html( $label ) . '</option>' . "\n";
			}
		}
	}
}
?>
<!-- MODAL DE CONFIGURACIÓN DEL LIBRO -->
<div id="settings-modal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center hidden transition-all duration-300 opacity-0 no-print">
    <div class="bg-[var(--bg-sidebar)] border border-[var(--border-color)] text-[var(--text-main)] w-full max-w-xl rounded-2xl shadow-2xl p-6 relative transform scale-95 transition-transform duration-300">
        <!-- Header Modal -->
        <div class="flex items-center justify-between pb-3 border-b border-[var(--border-color)]">
            <h3 class="text-md font-bold flex items-center gap-2">
                <i class="fa-solid fa-sliders text-indigo-500"></i> Ajustes de Maquetación del PDF
            </h3>
            <button onclick="toggleSettingsModal(false)" class="text-[var(--text-muted)] hover:text-[var(--text-main)] transition">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <!-- Navigation Tabs -->
        <div class="flex border-b border-[var(--border-color)] mb-4 -mx-6 px-6 overflow-x-auto gap-4 scrollbar-none">
            <button type="button" onclick="switchSettingTab('tab-page')" class="setting-tab-btn py-2 border-b-2 border-indigo-500 text-indigo-500 font-semibold text-xs transition focus:outline-none whitespace-nowrap" id="btn-tab-page">
                Página
            </button>
            <button type="button" onclick="switchSettingTab('tab-typography')" class="setting-tab-btn py-2 border-b-2 border-transparent text-[var(--text-muted)] hover:text-[var(--text-main)] font-semibold text-xs transition focus:outline-none whitespace-nowrap" id="btn-tab-typography">
                Tipografía
            </button>
            <button type="button" onclick="switchSettingTab('tab-header-footer')" class="setting-tab-btn py-2 border-b-2 border-transparent text-[var(--text-muted)] hover:text-[var(--text-main)] font-semibold text-xs transition focus:outline-none whitespace-nowrap" id="btn-tab-header-footer">
                Cabecera y Pie
            </button>
            <button type="button" onclick="switchSettingTab('tab-chapters')" class="setting-tab-btn py-2 border-b-2 border-transparent text-[var(--text-muted)] hover:text-[var(--text-main)] font-semibold text-xs transition focus:outline-none whitespace-nowrap" id="btn-tab-chapters">
                Capítulos
            </button>
        </div>

        <!-- Formulario Ajustes -->
        <div class="space-y-4 max-h-[55vh] overflow-y-auto pr-1">
            
            <!-- PESTAÑA 1: PÁGINA FÍSICA -->
            <div id="tab-page" class="setting-tab-content space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-semibold uppercase tracking-wider text-[var(--text-muted)] mb-1">Unidad de Medida</label>
                        <select id="setting-unit" onchange="updateUnitFields()" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-2 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                            <option value="cm">Centímetros (cm)</option>
                            <option value="in">Pulgadas (inches)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold uppercase tracking-wider text-[var(--text-muted)] mb-1">Tamaño de Página</label>
                        <select id="setting-page-size" onchange="toggleCustomPageFields()" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-2 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                            <option value="A4">A4 (21 x 29.7 cm)</option>
                            <option value="Letter">Carta / Letter (8.5 x 11 in)</option>
                            <option value="Custom">Personalizado</option>
                        </select>
                    </div>
                </div>

                <div id="custom-page-dimensions" class="grid grid-cols-2 gap-4 hidden">
                    <div>
                        <label class="block text-[10px] font-semibold uppercase tracking-wider text-[var(--text-muted)] mb-1">Ancho (<span class="unit-label">cm</span>)</label>
                        <input id="setting-page-width" type="number" step="0.01" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-2 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold uppercase tracking-wider text-[var(--text-muted)] mb-1">Alto (<span class="unit-label">cm</span>)</label>
                        <input id="setting-page-height" type="number" step="0.01" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-2 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                </div>

                <div>
                    <h4 class="text-[10px] font-bold uppercase tracking-wider text-indigo-500 mb-2 border-b border-[var(--border-color)] pb-1">Márgenes Globales (Arriba / Abajo)</h4>
                    <div class="grid grid-cols-2 gap-2 mb-3">
                        <div>
                            <label class="block text-[9px] text-[var(--text-muted)] mb-1">Arriba (<span class="unit-label">cm</span>)</label>
                            <input id="setting-margin-top" type="number" step="0.01" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[9px] text-[var(--text-muted)] mb-1">Abajo (<span class="unit-label">cm</span>)</label>
                            <input id="setting-margin-bottom" type="number" step="0.01" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>
                    
                    <h4 class="text-[10px] font-bold uppercase tracking-wider text-indigo-500 mb-2 border-b border-[var(--border-color)] pb-1">Márgenes de Encuadernación</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2 bg-[var(--bg-app)] p-2 rounded-lg border border-[var(--border-color)]">
                            <label class="block text-[10px] font-bold text-[var(--text-main)] text-center">Página Impar (Derecha)</label>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-[9px] text-[var(--text-muted)] mb-1">Interior/Izq (<span class="unit-label">cm</span>)</label>
                                    <input id="setting-margin-left-odd" type="number" step="0.01" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-[9px] text-[var(--text-muted)] mb-1">Exterior/Der (<span class="unit-label">cm</span>)</label>
                                    <input id="setting-margin-right-odd" type="number" step="0.01" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>
                            </div>
                        </div>
                        <div class="space-y-2 bg-[var(--bg-app)] p-2 rounded-lg border border-[var(--border-color)]">
                            <label class="block text-[10px] font-bold text-[var(--text-main)] text-center">Página Par (Izquierda)</label>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-[9px] text-[var(--text-muted)] mb-1">Exterior/Izq (<span class="unit-label">cm</span>)</label>
                                    <input id="setting-margin-left-even" type="number" step="0.01" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-[9px] text-[var(--text-muted)] mb-1">Interior/Der (<span class="unit-label">cm</span>)</label>
                                    <input id="setting-margin-right-even" type="number" step="0.01" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <h4 class="text-[10px] font-bold uppercase tracking-wider text-indigo-500 mb-2 border-b border-[var(--border-color)] pb-1">Padding de Contenido</h4>
                    <div class="grid grid-cols-4 gap-2">
                        <div>
                            <label class="block text-[9px] text-[var(--text-muted)] mb-1">Arriba (<span class="unit-label">cm</span>)</label>
                            <input id="setting-padding-top" type="number" step="0.01" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[9px] text-[var(--text-muted)] mb-1">Abajo (<span class="unit-label">cm</span>)</label>
                            <input id="setting-padding-bottom" type="number" step="0.01" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[9px] text-[var(--text-muted)] mb-1">Izquierda (<span class="unit-label">cm</span>)</label>
                            <input id="setting-padding-left" type="number" step="0.01" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[9px] text-[var(--text-muted)] mb-1">Derecha (<span class="unit-label">cm</span>)</label>
                            <input id="setting-padding-right" type="number" step="0.01" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>
                </div>

                <div>
                    <h4 class="text-[10px] font-bold uppercase tracking-wider text-indigo-500 mb-2 border-b border-[var(--border-color)] pb-1">Sangría / Bleeding</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-semibold text-[var(--text-muted)] mb-1">Espacio de Sangría (<span class="unit-label">cm</span>)</label>
                            <input id="setting-bleeding" type="number" step="0.01" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-2 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        </div>
                    </div>
                </div>
            </div>

            <!-- PESTAÑA 2: TIPOGRAFÍA -->
            <div id="tab-typography" class="setting-tab-content space-y-4 hidden">
                <div>
                    <h4 class="text-[10px] font-bold uppercase tracking-wider text-indigo-500 mb-2 border-b border-[var(--border-color)] pb-1">Tipografía del Cuerpo</h4>
                    <div class="grid grid-cols-3 gap-2">
                        <div class="col-span-2">
                            <label class="block text-[9px] text-[var(--text-muted)] mb-1">Familia de Fuente</label>
                            <select id="setting-font-family-content" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <?php almaden_render_font_options( $default_fonts, $selector_fonts ); ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[9px] text-[var(--text-muted)] mb-1">Tamaño (pt)</label>
                            <input id="setting-font-size-content" type="number" step="0.1" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>
                    <div class="grid grid-cols-4 gap-2 mt-2">
                        <div>
                            <label class="block text-[9px] text-[var(--text-muted)] mb-1">Interlineado</label>
                            <input id="setting-line-height-content" type="number" step="0.05" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[9px] text-[var(--text-muted)] mb-1">Alineación</label>
                            <select id="setting-content-text-align" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="left">Izquierda</option>
                                <option value="center">Centro</option>
                                <option value="right">Derecha</option>
                                <option value="justify">Justificado</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[9px] text-[var(--text-muted)] mb-1">Guiones (Separación)</label>
                            <select id="setting-content-hyphenation" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="1">Activado</option>
                                <option value="0">Desactivado</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[9px] text-[var(--text-muted)] mb-1">Idioma Reglas</label>
                            <select id="setting-content-language" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
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
                            <input id="setting-content-paragraph-indent" type="number" step="0.5" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[9px] text-[var(--text-muted)] mb-1">Espaciado entre párrafos (pt)</label>
                            <input id="setting-content-paragraph-spacing" type="number" step="0.5" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>
                </div>

                <div>
                    <h4 class="text-[10px] font-bold uppercase tracking-wider text-indigo-500 mb-2 border-b border-[var(--border-color)] pb-1">Tipografía de Títulos (H1, H2, H3)</h4>
                    
                    <div class="space-y-3">
                        <!-- H1 -->
                        <div class="p-2 border border-[var(--border-color)] rounded-lg bg-[var(--bg-app)]">
                            <span class="text-[9px] font-bold text-[var(--text-main)] block mb-2">Título Principal (H1)</span>
                            <div class="grid grid-cols-3 gap-2">
                                <div>
                                    <label class="block text-[8px] text-[var(--text-muted)] mb-1">Familia</label>
                                    <select id="setting-font-family-h1" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded p-1 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                        <?php almaden_render_font_options( $heading_default_fonts, $selector_fonts ); ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[8px] text-[var(--text-muted)] mb-1">Tamaño (pt)</label>
                                    <input id="setting-font-size-h1" type="number" step="0.5" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded p-1 text-xs text-center focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-[8px] text-[var(--text-muted)] mb-1">Peso</label>
                                    <select id="setting-font-weight-h1" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded p-1 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-500">
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
                                    <select id="setting-font-family-h2" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded p-1 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                        <?php almaden_render_font_options( $heading_default_fonts, $selector_fonts ); ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[8px] text-[var(--text-muted)] mb-1">Tamaño (pt)</label>
                                    <input id="setting-font-size-h2" type="number" step="0.5" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded p-1 text-xs text-center focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-[8px] text-[var(--text-muted)] mb-1">Peso</label>
                                    <select id="setting-font-weight-h2" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded p-1 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-500">
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
                                    <select id="setting-font-family-h3" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded p-1 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                        <?php almaden_render_font_options( $heading_default_fonts, $selector_fonts ); ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[8px] text-[var(--text-muted)] mb-1">Tamaño (pt)</label>
                                    <input id="setting-font-size-h3" type="number" step="0.5" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded p-1 text-xs text-center focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-[8px] text-[var(--text-muted)] mb-1">Peso</label>
                                    <select id="setting-font-weight-h3" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded p-1 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-500">
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

            <!-- PESTAÑA 3: CABECERA Y PIE DE PÁGINA -->
            <div id="tab-header-footer" class="setting-tab-content space-y-5 hidden">
                <!-- SECCIÓN CABECERA -->
                <div class="border border-[var(--border-color)] rounded-xl p-4 bg-[var(--bg-app)] space-y-3 shadow-sm">
                    <h4 class="text-xs font-bold text-indigo-500 uppercase tracking-wider flex items-center gap-1.5 border-b border-[var(--border-color)] pb-2 mb-1">
                        <i class="fa-solid fa-window-maximize text-[10px]"></i> Configuración de Cabecera
                    </h4>
                    
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Familia de Fuente</label>
                            <select id="setting-header-font-family" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <?php almaden_render_font_options( $hf_default_fonts, $selector_fonts ); ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Tamaño de Fuente (pt)</label>
                            <input id="setting-header-font-size" type="number" step="0.1" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-4 gap-2">
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Peso Fuente</label>
                            <select id="setting-header-font-weight" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="normal">Normal</option>
                                <option value="bold">Negrita (Bold)</option>
                                <option value="300">Ligera (300)</option>
                                <option value="500">Mediana (500)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Estilo Fuente</label>
                            <select id="setting-header-font-style" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="normal">Normal</option>
                                <option value="italic">Itálica</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Letter Spacing (pt)</label>
                            <input id="setting-header-letter-spacing" type="number" step="0.1" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Alineación Texto</label>
                            <select id="setting-header-align" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="center">Centrado</option>
                                <option value="left">Izquierda</option>
                                <option value="right">Derecha</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 pt-1">
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Margen Superior (<span class="unit-label">cm</span>)</label>
                            <input id="setting-header-margin-top" type="number" step="0.01" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Margen Inferior (<span class="unit-label">cm</span>)</label>
                            <input id="setting-header-margin-bottom" type="number" step="0.01" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 pt-1 border-t border-[var(--border-color)]/50 mt-1">
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Contenido Página Par</label>
                            <select id="setting-header-even-type" onchange="toggleCustomHeaderFields()" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="book_title">Título del Libro</option>
                                <option value="chapter_title">Título del Capítulo</option>
                                <option value="custom">Texto personalizado</option>
                                <option value="none">Ninguno</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Contenido Página Impar</label>
                            <select id="setting-header-odd-type" onchange="toggleCustomHeaderFields()" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="chapter_title">Título del Capítulo</option>
                                <option value="book_title">Título del Libro</option>
                                <option value="custom">Texto personalizado</option>
                                <option value="none">Ninguno</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 mt-1">
                        <div id="custom-header-even-container" class="hidden">
                            <label class="block text-[9px] text-[var(--text-muted)] mb-1">Texto Personalizado Par</label>
                            <input id="setting-header-even-custom" type="text" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div id="custom-header-odd-container" class="hidden">
                            <label class="block text-[9px] text-[var(--text-muted)] mb-1">Texto Personalizado Impar</label>
                            <input id="setting-header-odd-custom" type="text" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN PIE DE PÁGINA -->
                <div class="border border-[var(--border-color)] rounded-xl p-4 bg-[var(--bg-app)] space-y-3 shadow-sm">
                    <h4 class="text-xs font-bold text-indigo-500 uppercase tracking-wider flex items-center gap-1.5 border-b border-[var(--border-color)] pb-2 mb-1">
                        <i class="fa-solid fa-window-minimize text-[10px]"></i> Configuración de Pie de Página
                    </h4>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Familia de Fuente</label>
                            <select id="setting-footer-font-family" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <?php almaden_render_font_options( $hf_default_fonts, $selector_fonts ); ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Tamaño de Fuente (pt)</label>
                            <input id="setting-footer-font-size" type="number" step="0.1" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-4 gap-2">
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Peso Fuente</label>
                            <select id="setting-footer-font-weight" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="normal">Normal</option>
                                <option value="bold">Negrita (Bold)</option>
                                <option value="300">Ligera (300)</option>
                                <option value="500">Mediana (500)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Estilo Fuente</label>
                            <select id="setting-footer-font-style" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="normal">Normal</option>
                                <option value="italic">Itálica</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Letter Spacing (pt)</label>
                            <input id="setting-footer-letter-spacing" type="number" step="0.1" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Alineación Texto</label>
                            <select id="setting-footer-align" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="center">Centrado</option>
                                <option value="left">Izquierda</option>
                                <option value="right">Derecha</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 pt-1">
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Margen Superior (<span class="unit-label">cm</span>)</label>
                            <input id="setting-footer-margin-top" type="number" step="0.01" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Margen Inferior (<span class="unit-label">cm</span>)</label>
                            <input id="setting-footer-margin-bottom" type="number" step="0.01" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 pt-1 border-t border-[var(--border-color)]/50 mt-1">
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Contenido Página Par</label>
                            <select id="setting-footer-even-type" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="page_number">Número de Página</option>
                                <option value="none">Ninguno</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Contenido Página Impar</label>
                            <select id="setting-footer-odd-type" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="page_number">Número de Página</option>
                                <option value="none">Ninguno</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- OPCIONES GENERALES / GLOBALES -->
                <div class="px-2 pt-1">
                    <div class="flex items-center gap-2">
                        <input id="setting-show-header-page-one" type="checkbox" value="1" class="rounded text-indigo-600 focus:ring-indigo-500 h-4 w-4 bg-[var(--bg-app)] border-[var(--border-color)]">
                        <label for="setting-show-header-page-one" class="text-xs text-[var(--text-main)] select-none">¿Llevar cabecera/pie en la primera página de cada capítulo?</label>
                    </div>
                </div>
            </div>

            <!-- PESTAÑA 4: COMPORTAMIENTO DE CAPÍTULOS -->
            <div id="tab-chapters" class="setting-tab-content space-y-4 hidden">
                <div>
                    <h4 class="text-[10px] font-bold uppercase tracking-wider text-indigo-500 mb-2 border-b border-[var(--border-color)] pb-1">Flujo y Páginas de Inicio</h4>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[9px] text-[var(--text-muted)] mb-1">Forzar Inicio de Capítulo</label>
                            <select id="setting-chapter-start-parity" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="any">Cualquier página (Corrido)</option>
                                <option value="odd">Página impar (Lado derecho - Recomendado)</option>
                                <option value="even">Página par (Lado izquierdo)</option>
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
                                <option value="normal">Normal</option>
                                <option value="bold">Negrita</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Padding Arriba (cm)</label>
                            <input id="setting-chapter-title-padding-top" type="number" step="0.1" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Padding Abajo (cm)</label>
                            <input id="setting-chapter-title-padding-bottom" type="number" step="0.1" class="w-full bg-[var(--bg-app)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Footer Modal -->
        <div class="mt-4 flex justify-end gap-3 pt-3 border-t border-[var(--border-color)]">
            <button onclick="toggleSettingsModal(false)" class="px-4 py-2 border border-[var(--border-color)] text-[var(--text-muted)] hover:text-[var(--text-main)] hover:bg-[var(--bg-app)] text-xs font-semibold rounded-lg transition">
                Cancelar
            </button>
            <button onclick="savePDFSettings()" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-md hover:shadow-lg transition">
                Guardar Cambios
            </button>
        </div>
    </div>
</div>
