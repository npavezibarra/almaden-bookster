<?php
define( 'ALMADEN_TYPST_TESTING', true );

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $value ) {
		return trim( preg_replace( '/[\r\n\t]+/', ' ', strip_tags( (string) $value ) ) );
	}
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	function esc_url_raw( $value ) {
		return trim( (string) $value );
	}
}

require_once dirname( __DIR__ ) . '/includes/pdf-typst/page-styles/bootstrap.php';

$normalized = almaden_bookster_typst_page_style_normalize(
	array(
		array(
			'page_number' => 4,
			'style'       => array(
				'background' => array(
					'type'  => 'color',
					'color' => '#eeeeee',
				),
			),
		),
	)
);

if ( 1 !== count( $normalized ) ) {
	fwrite( STDERR, "El normalizador de estilos de página perdió una entrada válida.\n" );
	exit( 1 );
}

$text_colors = $normalized[0]['style']['text_colors'] ?? array();
foreach ( array( 'content', 'header', 'footer', 'opening' ) as $kind ) {
	if ( '#111111' !== ( $text_colors[ $kind ] ?? null ) ) {
		fwrite( STDERR, "El color de texto por defecto no se restauró para {$kind}.\n" );
		exit( 1 );
	}
}

echo "typst-page-style-regression-ok\n";
