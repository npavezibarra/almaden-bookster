<?php
/**
 * Regression test for hierarchical TOC level styles.
 *
 * @package AlmadenBookster
 */

define( 'ABSPATH', 1 );
define( 'ALMADEN_TYPST_TESTING', 1 );

require_once dirname( __DIR__ ) . '/includes/pdf-typst/typst-toc.php';

$assets = array();
$resolve_font = function ( $family, $weight = 'normal', $style = 'normal' ) {
	return array(
		'family' => $family,
		'source' => 'system',
	);
};

// Test 1: TOC with custom level styles
$chapter_with_levels = array(
	'id'                 => 'toc_1',
	'is_toc'             => '1',
	'title'              => 'Índice',
	'toc_item_align'     => 'left',
	'toc_text_transform' => 'none',
	'toc_levels'         => array(
		'chapter' => array(
			'font_size'      => 14,
			'text_transform' => 'uppercase',
			'font_weight'    => 'bold',
			'font_style'     => 'normal',
			'line_height'    => 1.4,
			'letter_spacing' => 1.0,
			'align'          => 'left',
			'hyphenate'      => 1,
			'indent'         => 0,
		),
		'h1'      => array(
			'font_size'      => 10,
			'text_transform' => 'none',
			'font_weight'    => 'normal',
			'font_style'     => 'italic',
			'line_height'    => 1.2,
			'letter_spacing' => 0.2,
			'align'          => 'justify',
			'hyphenate'      => 0,
			'indent'         => 5, // 5mm = 14.173pt
		),
	),
);

$chapters = array(
	array(
		'id'      => '1',
		'title'   => 'Primer Capítulo',
		'content' => "# Sección Primera\nContenido de prueba\n## Subsección Segunda",
	),
);

$settings = array(
	'chapter_title_font_size'   => 24,
	'chapter_title_font_weight' => 'bold',
	'font_size_content'         => 11,
);

$fallbacks = array(
	'title_family'      => 'Newsreader',
	'title_size'        => 24,
	'title_weight'      => 'bold',
	'title_line_height' => 1.2,
	'item_family'       => 'Newsreader',
	'item_size'         => 11,
	'item_weight'       => 'normal',
	'item_line_height'  => 1.5,
);

$typst_output = almaden_bookster_typst_render_toc(
	$chapter_with_levels,
	$chapters,
	$settings,
	$fallbacks,
	$assets,
	$resolve_font,
	true
);

// Assert Chapter level has uppercase, bold, size: 14pt, and tracking
if ( false === strpos( $typst_output, 'PRIMER CAPÍTULO' ) ) {
	fwrite( STDERR, "Fallo: El título del capítulo no fue transformado a MAYÚSCULAS en el nivel 0.\n" );
	exit( 1 );
}
if ( false === strpos( $typst_output, 'weight: bold' ) ) {
	fwrite( STDERR, "Fallo: El nivel 0 no tiene weight: bold.\n" );
	exit( 1 );
}
if ( false === strpos( $typst_output, 'size: 14pt' ) ) {
	fwrite( STDERR, "Fallo: El nivel 0 no tiene size: 14pt.\n" );
	exit( 1 );
}

// Assert H1 level has 'Sección Primera', italic, size: 10pt, hyphenate: false, indent 14.173pt, and justify
if ( false === strpos( $typst_output, 'Sección Primera' ) ) {
	fwrite( STDERR, "Fallo: El título H1 no aparece en el TOC.\n" );
	exit( 1 );
}
if ( false === strpos( $typst_output, 'style: "italic"' ) ) {
	fwrite( STDERR, "Fallo: El nivel H1 no tiene style: italic.\n" );
	exit( 1 );
}
if ( false === strpos( $typst_output, 'size: 10pt' ) ) {
	fwrite( STDERR, "Fallo: El nivel H1 no tiene size: 10pt.\n" );
	exit( 1 );
}
if ( false === strpos( $typst_output, 'hyphenate: false' ) ) {
	fwrite( STDERR, "Fallo: El nivel H1 no tiene hyphenate: false.\n" );
	exit( 1 );
}
if ( false === strpos( $typst_output, '#pad(left: 14.173pt)' ) ) {
	fwrite( STDERR, "Fallo: La sangría del nivel H1 no corresponde a 5mm (14.173pt).\n" );
	exit( 1 );
}
if ( false === strpos( $typst_output, '#set par(justify: true);' ) ) {
	fwrite( STDERR, "Fallo: La alineación justificada del nivel H1 no generó la directiva de justificación.\n" );
	exit( 1 );
}

echo "TOC level styles regression test: OK\n";

// Test 2: Fallback to settings['toc_levels'] when chapter['toc_levels'] is empty
$chapter_empty_levels = array(
	'id'     => 'toc_2',
	'is_toc' => '1',
	'title'  => 'Índice',
);
$settings_with_levels = array_merge( $settings, array(
	'toc_levels' => array(
		'chapter' => array(
			'text_transform' => 'uppercase',
			'font_weight'    => 'bold',
		),
	),
) );

$output_fallback = almaden_bookster_typst_render_toc(
	$chapter_empty_levels,
	$chapters,
	$settings_with_levels,
	$fallbacks,
	$assets,
	$resolve_font,
	true
);

if ( false === strpos( $output_fallback, 'PRIMER CAPÍTULO' ) ) {
	fwrite( STDERR, "Fallo: El fallback a settings['toc_levels'] no transformó el título del capítulo.\n" );
	exit( 1 );
}

echo "TOC settings fallback regression test: OK\n";
