<?php
define( 'ALMADEN_TYPST_TESTING', true );
require_once dirname( __DIR__ ) . '/includes/pdf-typst/typst-document.php';

$payload = array(
	'title'    => 'Regresión de créditos',
	'settings' => array(
		'unit'                 => 'cm',
		'page_width'           => 14,
		'page_height'          => 21,
		'credits_blank_before' => 9,
		'credits_blank_after'  => 9,
		'credits_config'       => array(
			'editorial' => array(
				'edition_number' => '1',
				'blank_before' => 2,
				'blank_after'  => 1,
			),
			'people' => array(
				array(
					'name'         => 'John Q. Est',
					'role'         => 'author',
					'show_contact' => 1,
					'email'        => 'john@example.com',
					'website'      => 'https://pontara.cl',
				),
			),
			'collaborators_title' => 'Instituciones colaboradoras',
			'collaborators_visible' => 1,
			'collaborators' => array(
				array(
					'name' => 'Fundación Almaden',
					'type' => 'Fundación',
					'website' => 'https://almaden.example',
				),
			),
			'collaborators_styles' => array(
				'title' => array( 'font_family' => 'Google Sans', 'font_size' => 18, 'font_weight' => 700, 'line_height' => 1.3 ),
				'item' => array( 'font_family' => 'Inter Tight', 'font_size' => 14, 'font_weight' => 400, 'line_height' => 1.6 ),
				'image_max_width' => 120,
			),
			'vertical_align' => 'bottom',
			'logos' => array(
				array(
					'show_author_name'  => 1,
					'author_font_family' => 'Google Sans',
					'author_font_size'  => 16,
				),
			),
			'section_order' => array( 'logos', 'people', 'editorial', 'legal', 'collaborators' ),
			'section_styles' => array(
				'people' => array(
					'font_family' => 'Inter Tight',
					'font_size'   => 16,
					'text_align'  => 'center',
					'line_height' => 1.4,
					'item_gap_px' => 18,
				),
				'editorial' => array(
					'font_family' => 'Google Sans',
					'font_size' => 15,
					'line_height' => 1.9,
					'text_align' => 'right',
					'show_separator' => 1,
				),
				'legal' => array(
					'font_family' => 'Google Sans',
					'font_size' => 13,
					'line_height' => 1.7,
					'text_align' => 'left',
				),
			),
		),
	),
	'chapters' => array(
		array(
			'title'   => 'Contenido',
			'content' => 'Página anterior a los créditos.',
		),
		array(
			'title'                    => 'Créditos',
			'content'                  => 'Contenido legible de créditos.',
			'is_credits'               => '1',
			'credits_hide_header'      => '1',
			'credits_hide_page_number' => '1',
			'credits_author_label'     => 'Johnny Q. Est',
			'credits_font_family'      => 'Google Sans',
			'credits_font_size'        => 14,
			'credits_font_weight'      => 300,
			'credits_letter_spacing'   => 1.5,
			'credits_margin_top'       => 3,
			'credits_margin_bottom'    => 3.5,
		),
	),
);

$document = almaden_bookster_build_typst_document( $payload );
$source   = $document['source'];

$required = array(
	'#metadata("credits-before") <almaden-intentional-blank>',
	'#metadata("credits-after") <almaden-intentional-blank>',
	'#metadata("credits") <almaden-hide-header>',
	'#metadata("credits") <almaden-hide-footer>',
	'query(<almaden-intentional-blank>)',
	'#set text(font: "Google Sans", size: 12pt',
	'#set text(font: "Inter Tight", size: 12pt',
	'#strong[John Q. Est]',
	'#emph[Autor]',
	'#linebreak()',
		'#line(length: 100%, stroke: 0.25pt)',
		'#align(center + bottom)',
		'#set page(margin: (top:',
	'#align(right)[#strong[1.ª edición]]',
	'Instituciones colaboradoras',
	'Fundación Almaden',
	'almaden.example',
	'john\@example.com',
	'pontara.cl',
);
foreach ( $required as $needle ) {
	if ( false === strpos( $source, $needle ) ) {
		fwrite( STDERR, 'Falta configuración de créditos Typst: ' . $needle . PHP_EOL );
		exit( 1 );
	}
}

$hidden_logo_assets = array();
$hidden_logo = almaden_bookster_typst_render_credits(
	array( 'logos' => array( array( 'show_author_name' => 0 ) ), 'section_order' => array( 'logos' ) ),
	'No debe aparecer',
	'Libro de prueba',
	array( 'family' => 'Merriweather', 'size' => 11, 'weight' => 400, 'line_height' => 1.5 ),
	$hidden_logo_assets,
	static function ( $family ) { return $family; }
);
if ( false !== strpos( $hidden_logo, 'No debe aparecer' ) ) {
	fwrite( STDERR, 'El checkbox del autor del logo no oculta el nombre.' . PHP_EOL );
	exit( 1 );
}

$text_logo_assets = array();
$text_logo = almaden_bookster_typst_render_credits(
	array( 'logos' => array( array( 'logo_source' => 'text' ) ), 'section_order' => array( 'logos' ) ),
	'Autor visible',
	'Libro de prueba',
	array( 'family' => 'Merriweather', 'size' => 11, 'weight' => 400, 'line_height' => 1.5 ),
	$text_logo_assets,
	static function ( $family ) { return $family; }
);
if ( false === strpos( $text_logo, 'Libro de prueba' ) ) {
	fwrite( STDERR, 'La fuente de logo en texto no está imprimiendo el título del libro.' . PHP_EOL );
	exit( 1 );
}

 $empty_collaborators_assets = array();
 $empty_collaborators = almaden_bookster_typst_render_credits(
 	array(
 		'collaborators_visible' => 1,
		'collaborators_title' => 'Colaboradores',
		'collaborators' => array(),
		'section_order' => array( 'collaborators' ),
	),
	'Sin colaboradores',
	'Libro de prueba',
	array( 'family' => 'Merriweather', 'size' => 11, 'weight' => 400, 'line_height' => 1.5 ),
	$empty_collaborators_assets,
	static function ( $family ) { return $family; }
 );
if ( false !== strpos( $empty_collaborators, 'Colaboradores' ) ) {
	fwrite( STDERR, 'La sección de colaboradores vacía no debería mostrar su título.' . PHP_EOL );
	exit( 1 );
}
$collaborator_anchor = strpos( $source, 'Instituciones colaboradoras' );
$before_collaborators = false !== $collaborator_anchor
	? substr( $source, max( 0, $collaborator_anchor - 300 ), 300 )
	: '';
if ( false === $collaborator_anchor || false === strpos( $before_collaborators, '#pagebreak()' ) ) {
	fwrite( STDERR, 'Colaboradores no está forzando un salto de página antes de su bloque.' . PHP_EOL );
	exit( 1 );
}

if ( 2 !== substr_count( $source, '#metadata("credits-before") <almaden-intentional-blank>' ) ) {
	fwrite( STDERR, 'Typst no generó exactamente dos páginas antes de créditos.' . PHP_EOL );
	exit( 1 );
}
if ( 1 !== substr_count( $source, '#metadata("credits-after") <almaden-intentional-blank>' ) ) {
	fwrite( STDERR, 'Typst no generó exactamente una página después de créditos.' . PHP_EOL );
	exit( 1 );
}

$visible_footer_payload = $payload;
$visible_footer_payload['chapters'][1]['hide_footer'] = '1';
$visible_footer_payload['chapters'][1]['hide_all_headers_footers'] = '1';
$visible_footer_payload['chapters'][1]['credits_hide_header'] = '0';
$visible_footer_payload['chapters'][1]['credits_hide_page_number'] = '0';
$visible_footer_source = almaden_bookster_build_typst_document( $visible_footer_payload )['source'];
if ( false !== strpos( $visible_footer_source, '#metadata("Créditos") <almaden-hide-footer>' )
	|| false !== strpos( $visible_footer_source, '#metadata("credits") <almaden-hide-footer>' )
	|| false !== strpos( $visible_footer_source, '#metadata("Créditos") <almaden-hide-header>' )
	|| false !== strpos( $visible_footer_source, '#metadata("credits") <almaden-hide-header>' ) ) {
	fwrite( STDERR, 'Las banderas heredadas continúan ocultando la cabecera o el pie de Créditos.' . PHP_EOL );
	exit( 1 );
}

$output = isset( $argv[1] ) ? $argv[1] : sys_get_temp_dir() . '/almaden-typst-credits-regression.typ';
file_put_contents( $output, $source );
echo $output . PHP_EOL;
