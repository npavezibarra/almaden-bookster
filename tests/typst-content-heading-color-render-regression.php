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

require_once dirname( __DIR__ ) . '/includes/pdf-typst/typst-document.php';

$payload = array(
	'title' => 'Regresión de color Content en headings',
	'settings' => array(
		'unit' => 'cm',
		'page_width' => 14,
		'page_height' => 20,
		'margin_top' => 2,
		'margin_bottom' => 2,
		'margin_left' => 2,
		'margin_right' => 2,
		'font_family_content' => 'Libertinus Serif',
		'font_family_h2' => 'Libertinus Serif',
		'hide_title' => 1,
		'chapter_prefix_show' => 0,
		'chapter_subtitle_show' => 0,
		'book_separate_opening_content' => 0,
		'page_styles' => array(
			array(
				'page_number' => 1,
				'resolved_page' => 1,
				'style' => array(
					'background' => array(
						'type' => 'color',
						'color' => '#000000',
					),
					'text_colors' => array(
						'content' => '#ffffff',
						'header' => '#777777',
						'footer' => '#777777',
					),
				),
			),
		),
	),
	'chapters' => array(
		array(
			'title' => 'Capítulo oculto',
			'hide_opening' => '1',
			'content' => "## Heading cromático\n",
		),
	),
);

$document = almaden_bookster_build_typst_document( $payload );
$source = (string) ( $document['source'] ?? '' );
$expected = '#heading(level: 2)[#almaden-page-colored("content", fill => text(fill: fill, font: "Libertinus Serif"';
if ( false === strpos( $source, $expected ) ) {
	fwrite( STDERR, "El heading H2 no quedó asociado al color Content.\n" );
	exit( 1 );
}

$typst = dirname( __DIR__ ) . '/runtime/typst/typst';
if ( ! is_file( $typst ) || ! is_executable( $typst ) ) {
	fwrite( STDERR, "No está disponible el runtime Typst para la regresión renderizada.\n" );
	exit( 1 );
}

$base = sys_get_temp_dir() . '/almaden-content-heading-color-' . uniqid( '', true );
$input = $base . '.typ';
$output = $base . '.svg';
file_put_contents( $input, $source );
$command = escapeshellarg( $typst )
	. ' compile --root / --format svg --pages 1 '
	. escapeshellarg( $input ) . ' ' . escapeshellarg( $output ) . ' 2>&1';
$diagnostics = array();
$status = 0;
exec( $command, $diagnostics, $status );
if ( 0 !== $status || ! is_file( $output ) ) {
	fwrite( STDERR, "Typst no pudo renderizar el heading de contenido:\n" . implode( "\n", $diagnostics ) . "\n" );
	@unlink( $input );
	@unlink( $output );
	exit( 1 );
}

$svg = strtolower( (string) file_get_contents( $output ) );
if ( false === strpos( $svg, '#ffffff' ) ) {
	fwrite( STDERR, "El SVG renderizado no contiene el color Content blanco para el heading.\n" );
	@unlink( $input );
	@unlink( $output );
	exit( 1 );
}

@unlink( $input );
@unlink( $output );

echo "Typst content heading color render regression: OK\n";
