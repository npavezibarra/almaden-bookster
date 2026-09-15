<?php
define( 'ABSPATH', __DIR__ . '/' );
define( 'ALMADEN_TYPST_TESTING', true );
define( 'MB_IN_BYTES', 1048576 );

require_once dirname( __DIR__ ) . '/includes/pdf-typst/typst-fonts.php';
require_once dirname( __DIR__ ) . '/includes/pdf-typst/typst-markup.php';

$heading_styles = array(
	2 => array(
		'font_family'    => 'Libertinus Serif',
		'font_size'      => 18,
		'font_weight'    => 600,
		'font_style'     => 'normal',
		'line_height'    => 1.3,
		'align'          => 'left',
		'letter_spacing' => 0,
		'margin_top'     => 0,
		'margin_bottom'  => 0,
		'hyphenate'      => false,
	),
);

$source = almaden_bookster_typst_render_blocks(
	"[align=center]\n## Vocación, Servicio y el Deber <br>de Dejar un Legado\n[/align]",
	array( 'heading_styles' => $heading_styles )
);

if ( false === strpos( $source, '#align(center)[#heading(level: 2)' ) ) {
	fwrite( STDERR, "Un heading H2 dentro de [align=center] no sobreescribió el alineamiento global.\n" );
	exit( 1 );
}
if ( false !== strpos( $source, '#align(left)[#heading(level: 2)' ) ) {
	fwrite( STDERR, "El alineamiento global del H2 prevaleció sobre el shortcode RAW local.\n" );
	exit( 1 );
}
if ( false === strpos( $source, 'Vocación, Servicio y el Deber' ) || false === strpos( $source, 'de Dejar un Legado' ) ) {
	fwrite( STDERR, "El texto del heading alineado localmente no se conservó completo.\n" );
	exit( 1 );
}

echo "Typst RAW align heading regression: OK\n";
