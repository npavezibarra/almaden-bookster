<?php
define( 'ALMADEN_TYPST_TESTING', true );
require_once dirname( __DIR__ ) . '/includes/pdf-typst/page-templates/bootstrap.php';

$source = <<<'TYPST'
#let almaden-page-styled(kind, body) = body
#set page(width: 20cm, height: 12cm, margin: 1cm, columns: 2)
#set align(left)
#metadata("almaden-flow-1") <almaden-flow-1>

#par[Primer bloque de la columna izquierda.]
#metadata("almaden-flow-2") <almaden-flow-2>

#par[Segundo bloque de la columna izquierda.]
#metadata("almaden-flow-3") <almaden-flow-3>

#par[Primer bloque que ocupa la columna derecha.]
#metadata("almaden-flow-4") <almaden-flow-4>

#par[Segundo bloque que ocupa la columna derecha.]
#metadata("almaden-flow-5") <almaden-flow-5>

#par[Contenido de la página siguiente.]
TYPST;

$context = array(
	'columns_count' => 2,
	'columns_gap'   => 0.8,
	'unit'          => 'cm',
	'templates'     => array(),
);
$flow_map = array(
	array( 'id' => 'almaden-flow-1', 'page' => 2, 'x' => 10 ),
	array( 'id' => 'almaden-flow-2', 'page' => 2, 'x' => 10 ),
	array( 'id' => 'almaden-flow-3', 'page' => 2, 'x' => 105 ),
	array( 'id' => 'almaden-flow-4', 'page' => 2, 'x' => 105 ),
	array( 'id' => 'almaden-flow-5', 'page' => 3, 'x' => 10 ),
);
$template = array(
	'id'          => 'tpl-primary',
	'instance_id' => 'tpl-primary',
	'page_number' => 2,
	'template_id' => 'one-column-one-image',
);

$anchored = almaden_bookster_typst_resolve_page_template(
	array_merge( $template, array( 'anchor' => array( 'flow_id' => 'almaden-flow-3' ) ) ),
	$flow_map
);
if ( empty( $anchored['applied'] ) || 2 !== ( $anchored['template']['resolved_page'] ?? 0 ) ) {
	fwrite( STDERR, "La identidad estable no resolvió su página desde el ancla.\n" );
	exit( 1 );
}

$anchored_rows = almaden_bookster_typst_page_template_target_rows(
	$flow_map,
	array_merge( $template, array( 'anchor' => array( 'flow_id' => 'almaden-flow-3' ) ) )
);
if ( array( 'almaden-flow-3', 'almaden-flow-4' ) !== array_column( $anchored_rows, 'id' ) ) {
	fwrite( STDERR, "La selección incluyó bloques anteriores al ancla de la plantilla.\n" );
	exit( 1 );
}
$reserved_rows = almaden_bookster_typst_page_template_rows_before_anchor( $flow_map, 'almaden-flow-3' );
if ( array( 'almaden-flow-1', 'almaden-flow-2' ) !== array_column( $reserved_rows, 'id' ) ) {
	fwrite( STDERR, "La plantilla anterior consumió el ancla reservada para la siguiente.\n" );
	exit( 1 );
}

$registry = almaden_bookster_typst_page_template_registry();
if ( empty( $registry['inner-full-page'] ) ) {
	fwrite( STDERR, "El registry no expuso la nueva plantilla Inner Full Page.\n" );
	exit( 1 );
}

$probe = almaden_bookster_typst_page_template_prepare_word_probe( $source, $context, $flow_map, $template );
if ( empty( $probe['source'] ) || empty( $probe['word_map'] ) ) {
	fwrite( STDERR, "La sonda de palabras no pudo preparar la página de plantilla.\n" );
	exit( 1 );
}
$default_slots = almaden_bookster_typst_page_template_normalize_slots( 'one-column-one-image', array() );
if ( 1 !== count( $default_slots ) || 'image-1' !== ( $default_slots[0]['id'] ?? '' ) ) {
	fwrite( STDERR, "La plantilla no normalizó el slot por defecto.\n" );
	exit( 1 );
}
$slot_assets = array();
$multi_slot_placeholder = almaden_bookster_typst_page_template_placeholder(
	array(
		'id'          => 'tpl-multi',
		'instance_id' => 'tpl-multi',
		'page_number' => 7,
		'template_id' => 'one-column-one-image',
		'slots'       => array(
			array( 'id' => 'image-1', 'label' => 'Imagen 1', 'kind' => 'image' ),
			array( 'id' => 'image-2', 'label' => 'Imagen 2', 'kind' => 'image' ),
			array( 'id' => 'image-3', 'label' => 'Imagen 3', 'kind' => 'image' ),
		),
	),
	$context,
	$slot_assets
);
if ( 3 !== substr_count( $multi_slot_placeholder, 'almaden-template-slot-tpl-multi-image-' ) ) {
	fwrite( STDERR, "La plantilla no renderizó los tres slots esperados.\n" );
	exit( 1 );
}
if ( ! empty( $slot_assets ) ) {
	fwrite( STDERR, "La plantilla no debería registrar assets sin imágenes adjuntas.\n" );
	exit( 1 );
}

$full_slots = almaden_bookster_typst_page_template_normalize_slots( 'inner-full-page', array() );
if ( 1 !== count( $full_slots ) || 'image-1' !== ( $full_slots[0]['id'] ?? '' ) ) {
	fwrite( STDERR, "La plantilla Inner Full Page no normalizó el slot por defecto.\n" );
	exit( 1 );
}
$full_placeholder = almaden_bookster_typst_page_template_placeholder(
	array(
		'id'          => 'tpl-full',
		'instance_id' => 'tpl-full',
		'page_number' => 7,
		'template_id' => 'inner-full-page',
		'slots'       => array(
			array( 'id' => 'image-1', 'label' => 'Imagen 1', 'kind' => 'image' ),
		),
	),
	$context,
	$slot_assets
);
if ( false === strpos( $full_placeholder, 'almaden-template-slot-tpl-full-image-1' ) ) {
	fwrite( STDERR, "La plantilla Inner Full Page no etiquetó su slot de imagen.\n" );
	exit( 1 );
}

$probe_output = sys_get_temp_dir() . '/almaden-typst-page-template-probe.typ';
file_put_contents( $probe_output, $probe['source'] );

list( $blocks, $ordered_ids ) = almaden_bookster_typst_page_template_source_blocks( $source );
$partial_layout = almaden_bookster_typst_page_template_fragment_layout(
	$blocks,
	$probe['target_ids'],
	array( 'block_id' => 'almaden-flow-2', 'word_count' => 2 )
);
if ( false === strpos( $partial_layout['left_body'] ?? '', 'Segundo bloque' ) || false === strpos( $partial_layout['deferred_body'] ?? '', 'de la columna izquierda.' ) ) {
	fwrite( STDERR, "El corte dentro del párrafo no preservó ambos fragmentos.\n" );
	exit( 1 );
}
$partial_debug = array();
$partial_source = almaden_bookster_typst_page_template_apply_blocks(
	$source,
	$context,
	$template,
	$blocks,
	$ordered_ids,
	$probe['target_ids'],
	$partial_layout['left_ids'],
	$partial_debug,
	$partial_layout
);
if ( 1 !== substr_count( $partial_source, '#metadata("almaden-flow-2")' ) ) {
	fwrite( STDERR, "El corte duplicó el identificador del flujo diferido.\n" );
	exit( 1 );
}
file_put_contents( sys_get_temp_dir() . '/almaden-typst-page-template-partial.typ', $partial_source );

$result = almaden_bookster_typst_apply_page_template_flow( $source, $context, $flow_map, $template );
if ( false === strpos( $result, '#page(columns: 1)[' ) ) {
	fwrite( STDERR, "La plantilla no emitió una página de ancho completo.\n" );
	exit( 1 );
}
if ( false === strpos( $result, 'almaden-template-slot-tpl-primary-image-1' ) ) {
	fwrite( STDERR, "La plantilla física no etiquetó el slot de imagen.\n" );
	exit( 1 );
}

if ( ! preg_match( '/#page\(columns: 1\).*?#metadata\("almaden-flow-2"\)/s', $result ) ) {
	fwrite( STDERR, "El contenido de la columna reemplazada no se difirió después de la plantilla.\n" );
	exit( 1 );
}

if ( false === strpos( $result, '#metadata("almaden-flow-5")' ) ) {
	fwrite( STDERR, "El contenido posterior del libro se perdió al aplicar la plantilla.\n" );
	exit( 1 );
}

$full_template = array(
	'id'          => 'tpl-full',
	'instance_id' => 'tpl-full',
	'page_number' => 2,
	'template_id' => 'inner-full-page',
);
$full_result = almaden_bookster_typst_apply_page_template_flow( $source, $context, $flow_map, $full_template );
if ( false === strpos( $full_result, '#almaden-page-styled("content")[' ) ) {
	fwrite( STDERR, "La plantilla Inner Full Page no se emitió dentro del área de content.\n" );
	exit( 1 );
}
if ( false !== strpos( $full_result, '#grid(columns: (1fr, 1fr)' ) ) {
	fwrite( STDERR, "La plantilla Inner Full Page no debería usar el layout split de dos columnas.\n" );
	exit( 1 );
}

// A second template must target the reflowed right-column content, not erase
// the first page template or reuse its source blocks.
$second_flow_map = array(
	array( 'id' => 'almaden-flow-1', 'page' => 2, 'x' => 10 ),
	array( 'id' => 'almaden-flow-2', 'page' => 2, 'x' => 10 ),
	array( 'id' => 'almaden-flow-3', 'page' => 3, 'x' => 10 ),
	array( 'id' => 'almaden-flow-4', 'page' => 3, 'x' => 105 ),
	array( 'id' => 'almaden-flow-5', 'page' => 4, 'x' => 10 ),
);
$second_template = array(
	'id'          => 'tpl-secondary',
	'instance_id' => 'tpl-secondary',
	'page_number' => 3,
	'template_id' => 'one-column-one-image',
);
$result = almaden_bookster_typst_apply_page_template_flow( $result, $context, $second_flow_map, $second_template );
if ( 2 !== substr_count( $result, '#page(columns: 1)[' ) ) {
	fwrite( STDERR, "La segunda plantilla no generó una segunda página de ancho completo.\n" );
	exit( 1 );
}
if ( ! preg_match( '/#page\(columns: 1\).*?#metadata\("almaden-flow-4"\)/s', $result ) ) {
	fwrite( STDERR, "La segunda columna reemplazada no continuó después de la segunda plantilla.\n" );
	exit( 1 );
}

$output = isset( $argv[1] ) ? $argv[1] : sys_get_temp_dir() . '/almaden-typst-page-template-regression.typ';
file_put_contents( $output, $result );
echo $output . PHP_EOL;
