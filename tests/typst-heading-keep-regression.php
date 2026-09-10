<?php
define( 'ALMADEN_TYPST_TESTING', true );
require_once dirname( __DIR__ ) . '/includes/pdf-typst/typst-fonts.php';
require_once dirname( __DIR__ ) . '/includes/pdf-typst/typst-markup.php';

$raw = <<<RAW
### 5.3.3 Diversificación de la economía

Las ciudades cuya economía depende de una sola industria dominante pueden volverse vulnerables.
RAW;

$rendered = almaden_bookster_typst_render_blocks(
	$raw,
	array(
		'heading_keep_with_next' => array(
			'enabled'   => true,
			'min_lines' => 3,
			'reserve_pt' => 56.925,
			'levels'    => array( 3 ),
		),
		'heading_styles' => array(
			3 => array(
				'font_family'   => 'Merriweather',
				'font_size'     => 13,
				'font_weight'   => 'bold',
				'font_style'    => 'normal',
				'line_height'   => 1.4,
				'align'         => 'left',
				'margin_top'    => 25,
				'margin_bottom' => 15,
			),
		),
	)
);

if ( false === strpos( $rendered, '#block(width: 100%, breakable: false, sticky: true)[' ) ) {
	fwrite( STDERR, "El encabezado no fue protegido en un bloque no quebrable.\n" );
	exit( 1 );
}

if ( false === strpos( $rendered, '#heading(level: 3)' ) || false === strpos( $rendered, '#par[Las ciudades cuya economía depende' ) ) {
	fwrite( STDERR, "El render protegido no conserva el encabezado y el primer párrafo posterior.\n" );
	exit( 1 );
}

if ( false === strpos( $rendered, '#v(25pt)' ) || false === strpos( $rendered, '#v(15pt)' ) ) {
	fwrite( STDERR, "Los márgenes superior e inferior del encabezado no se emitieron como espaciado explícito.\n" );
	exit( 1 );
}

if ( false === strpos( $rendered, '#v(56.925pt)' ) || false === strpos( $rendered, '#v(-56.925pt)' ) ) {
	fwrite( STDERR, "La protección del encabezado no reservó y compensó las líneas mínimas posteriores.\n" );
	exit( 1 );
}

if ( false === strpos( $rendered, "#v(-56.925pt)\n\n#par[Las ciudades cuya economía depende" ) ) {
	fwrite( STDERR, "El párrafo posterior quedó dentro del bloque no quebrable y podría desbordar la página.\n" );
	exit( 1 );
}

$disabled = almaden_bookster_typst_render_blocks(
	$raw,
	array(
		'heading_keep_with_next' => array(
			'enabled' => false,
			'levels'  => array( 3 ),
		),
	)
);

if ( preg_match( '/^#block\(width: 100%, breakable: false, sticky: true\)\[/', trim( $disabled ) ) ) {
	fwrite( STDERR, "El encabezado se protegió aunque la regla estaba desactivada.\n" );
	exit( 1 );
}

echo "typst-heading-keep-regression-ok\n";
