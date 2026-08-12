<?php
// Cargar fuentes instaladas para los selectores
$selector_fonts = function_exists( 'almaden_bookster_get_available_fonts_list' ) ? almaden_bookster_get_available_fonts_list() : almaden_bookster_get_installed_fonts_list();

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
