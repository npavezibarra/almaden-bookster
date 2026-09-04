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
	'title' => 'Regresión de colores de apertura',
	'settings' => array(
		'unit' => 'cm',
		'page_width' => 14,
		'page_height' => 20,
		'margin_top' => 2,
		'margin_bottom' => 2,
		'margin_left' => 2,
		'margin_right' => 2,
		'font_family_content' => 'Libertinus Serif',
		'chapter_title_font_family' => 'Libertinus Serif',
		'chapter_prefix_font_family' => 'Libertinus Serif',
		'chapter_subtitle_font_family' => 'Libertinus Serif',
		'chapter_prefix_show' => 1,
		'chapter_prefix_template' => 'CAPÍTULO {R}',
		'chapter_subtitle_show' => 1,
		'book_separate_opening_content' => 1,
		'page_styles' => array(
			array(
				'page_number' => 1,
				'resolved_page' => 1,
				'style' => array(
					'text_colors' => array(
						'opening_prefix' => '#e49595',
						'opening_title' => '#fefefe',
						'opening_subtitle' => '#19a7a0',
					),
				),
			),
		),
	),
	'chapters' => array(
		array(
			'title' => 'Título cromático',
			'subtitle_text' => 'Metadata cromática',
			'content' => '',
		),
	),
);

$document = almaden_bookster_build_typst_document( $payload );
$source = (string) ( $document['source'] ?? '' );
$expected_source_fragments = array(
	'else if kind == "opening_prefix" { if current == 1 { rgb("#e49595") }',
	'else if kind == "opening_title" { if current == 1 { rgb("#fefefe") }',
	'else if kind == "opening_subtitle" { if current == 1 { rgb("#19a7a0") }',
	'almaden-page-colored("opening_prefix", fill => text(fill: fill,',
	'almaden-page-colored("opening_title", fill => text(fill: fill,',
	'almaden-page-colored("opening_subtitle", fill => text(fill: fill,',
);
foreach ( $expected_source_fragments as $fragment ) {
	if ( false === strpos( $source, $fragment ) ) {
		fwrite( STDERR, "El source de apertura no contiene: {$fragment}\n" );
		exit( 1 );
	}
}

$typst = dirname( __DIR__ ) . '/runtime/typst/typst';
if ( ! is_file( $typst ) || ! is_executable( $typst ) ) {
	fwrite( STDERR, "No está disponible el runtime Typst para la regresión renderizada.\n" );
	exit( 1 );
}

$base = sys_get_temp_dir() . '/almaden-opening-color-' . uniqid( '', true );
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
	fwrite( STDERR, "Typst no pudo renderizar la apertura de control:\n" . implode( "\n", $diagnostics ) . "\n" );
	@unlink( $input );
	@unlink( $output );
	exit( 1 );
}

$svg = strtolower( (string) file_get_contents( $output ) );
foreach ( array( '#e49595', '#fefefe', '#19a7a0' ) as $color ) {
	if ( false === strpos( $svg, $color ) ) {
		fwrite( STDERR, "El SVG renderizado no contiene el color de apertura {$color}.\n" );
		@unlink( $input );
		@unlink( $output );
		exit( 1 );
	}
}

@unlink( $input );
@unlink( $output );

echo "Typst opening color render regression: OK\n";
