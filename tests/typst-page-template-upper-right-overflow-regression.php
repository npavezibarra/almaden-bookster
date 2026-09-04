<?php
define( 'ALMADEN_TYPST_TESTING', true );

require_once dirname( __DIR__ ) . '/includes/pdf-typst/page-templates/bootstrap.php';

list( $inline_left, $inline_right ) = almaden_bookster_typst_page_template_split_body_at_word(
	'#emph[Santiago, agosto-setiembre 2020, luego enero del 2021.]',
	3
);
if ( '#emph[Santiago, agosto-setiembre 2020, luego ]' !== $inline_left
	|| '#emph[enero del 2021.]' !== $inline_right ) {
	fwrite( STDERR, "El corte dentro de emph no reabrió el formato en el fragmento derecho.\n" );
	exit( 1 );
}

list( $nested_left, $nested_right ) = almaden_bookster_typst_page_template_split_body_at_word(
	'#text(lang: "es")[uno #emph[dos tres] cuatro]',
	2
);
if ( '#text(lang: "es")[uno #emph[dos ]]' !== $nested_left
	|| '#text(lang: "es")[#emph[tres] cuatro]' !== $nested_right ) {
	fwrite( STDERR, "El corte de formatos inline anidados quedó desbalanceado.\n" );
	exit( 1 );
}

$source = <<<'TYPST'
#let almaden-page-styled(kind, body) = body
#set page(width: 20cm, height: 12cm, margin: 1cm, columns: 2)
#metadata("almaden-flow-1") <almaden-flow-1>

#par[Texto inferior izquierdo.]
#metadata("almaden-flow-2") <almaden-flow-2>

#par[Texto derecho que deberia continuar sin desbordar hacia abajo.]
TYPST;

$context = array(
	'columns_gap' => 0.8,
	'unit' => 'cm',
	'asset_mode' => 'original',
	'font_size' => 10,
	'line_height' => 1.2,
);
$flow_map = array(
	array( 'id' => 'almaden-flow-1', 'page' => 2, 'x' => 10 ),
	array( 'id' => 'almaden-flow-2', 'page' => 2, 'x' => 10 ),
);
$template = array(
	'id' => 'tpl-upper',
	'instance_id' => 'tpl-upper',
	'template_id' => 'upper-image-bottom-text-split',
	'page_number' => 2,
);

$left_probe = almaden_bookster_typst_page_template_prepare_word_probe( $source, $context, $flow_map, $template );
if ( 12.0 !== (float) ( $left_probe['bottom_safety_pt'] ?? 0 ) ) {
	fwrite( STDERR, "La sonda principal no recibió el margen inferior de una línea.\n" );
	exit( 1 );
}
$left_cut = almaden_bookster_typst_page_template_probe_cut(
	$left_probe,
	array(
		'bottom' => array( 'page' => 2, 'y' => '100pt' ),
		'words' => array(
			array( 'id' => 'almaden-template-probe-word-1', 'page' => 2, 'y' => '84pt' ),
			array( 'id' => 'almaden-template-probe-word-2', 'page' => 2, 'y' => '90pt' ),
		),
	)
);
if ( 1 !== (int) ( $left_cut['word_count'] ?? 0 ) ) {
	fwrite( STDERR, "La sonda principal aceptó texto dentro del margen inferior reservado.\n" );
	exit( 1 );
}

$probe = almaden_bookster_typst_page_template_prepare_upper_bottom_right_probe(
	$source,
	$context,
	$flow_map,
	$template,
	array( 'block_id' => 'almaden-flow-1', 'word_count' => 2 )
);
if ( empty( $probe['source'] ) || false === strpos( $probe['source'], 'almaden-template-probe-bottom' ) ) {
	fwrite( STDERR, "No se preparó la sonda de capacidad de la columna derecha.\n" );
	exit( 1 );
}

$report = array(
	'bottom' => array( 'page' => 2, 'y' => '100pt' ),
	'words' => array(
		array( 'id' => 'almaden-template-upper-right-word-1', 'page' => 2, 'y' => '20pt' ),
		array( 'id' => 'almaden-template-upper-right-word-2', 'page' => 2, 'y' => '120pt' ),
	),
);
$layout = almaden_bookster_typst_page_template_refine_upper_bottom_right_layout( $probe, $report );
if ( '' === trim( (string) ( $layout['deferred_body'] ?? '' ) )
	|| '' === trim( (string) ( $layout['overflow_body'] ?? '' ) ) ) {
	fwrite( STDERR, "La columna derecha no separó texto visible y excedente.\n" );
	exit( 1 );
}

$safe_probe = $probe;
$safe_probe['bottom_safety_pt'] = 12;
$safe_layout = almaden_bookster_typst_page_template_refine_upper_bottom_right_layout(
	$safe_probe,
	array(
		'bottom' => array( 'page' => 2, 'y' => '100pt' ),
		'words' => array(
			array( 'id' => 'almaden-template-upper-right-word-1', 'page' => 2, 'y' => '84pt' ),
			array( 'id' => 'almaden-template-upper-right-word-2', 'page' => 2, 'y' => '90pt' ),
		),
	)
);
if ( false === strpos( (string) ( $safe_layout['overflow_body'] ?? '' ), 'continuar' ) ) {
	fwrite( STDERR, "La sonda aceptó una línea demasiado cercana al borde inferior.\n" );
	exit( 1 );
}

$page_start_layout = array(
	'pre_body' => '#par[Texto anterior en el mismo parrafo.]',
	'left_body' => '#par[Texto inferior izquierdo.]',
	'deferred_body' => '#par[Fragmento sin marcador que tambien debe medirse.]',
	'left_ids' => array( 'almaden-flow-1' ),
	'deferred_ids' => array( 'almaden-flow-1' ),
	'page_ids' => array( 'almaden-flow-1', 'almaden-flow-2' ),
	'deferred_segments' => array(
		array( 'id' => 'almaden-flow-1', 'prefix' => '', 'body' => 'Fragmento sin marcador que tambien debe medirse.' ),
	),
);
$page_start_probe = almaden_bookster_typst_page_template_prepare_upper_bottom_right_layout_probe( $source, $context, $template, $page_start_layout );
if ( empty( $page_start_probe['source'] ) || false === strpos( $page_start_probe['source'], 'Fragmento#metadata' ) ) {
	fwrite( STDERR, "La ruta de inicio de pagina no midio el fragmento sin marcador.\n" );
	exit( 1 );
}
$page_start_report = array(
	'bottom' => array( 'page' => 2, 'y' => '100pt' ),
	'words' => array(
		array( 'id' => 'almaden-template-upper-right-word-1', 'page' => 2, 'y' => '20pt' ),
		array( 'id' => 'almaden-template-upper-right-word-2', 'page' => 2, 'y' => '120pt' ),
	),
);
$page_start_refined = almaden_bookster_typst_page_template_refine_upper_bottom_right_layout( $page_start_probe, $page_start_report );
if ( '' === trim( (string) ( $page_start_refined['overflow_body'] ?? '' ) ) ) {
	fwrite( STDERR, "La ruta de inicio de pagina no devolvio el excedente al flujo.\n" );
	exit( 1 );
}

$output = almaden_bookster_typst_apply_page_template_flow(
	$source,
	$context,
	$flow_map,
	$template,
	array( 'layout' => $layout )
);
if ( false === strpos( $output, $layout['overflow_body'] ) ) {
	fwrite( STDERR, "El excedente de la columna derecha no volvió al flujo normal.\n" );
	exit( 1 );
}
if ( false === strpos( $output, '#grid(columns: (1fr,), rows: (42%, 1fr), gutter: 0.8cm)' )
	|| false !== strpos( $output, 'height: 58%' ) ) {
	fwrite( STDERR, "La plantilla superior/inferior conserva geometría vertical que puede desbordar.\n" );
	exit( 1 );
}

$binary = dirname( __DIR__ ) . '/runtime/typst/typst';
if ( is_executable( $binary ) ) {
	$temp_dir = sys_get_temp_dir() . '/almaden-upper-right-' . getmypid();
	if ( ! is_dir( $temp_dir ) ) {
		mkdir( $temp_dir, 0700, true );
	}
	$input = $temp_dir . '/probe.typ';
	$physical_source = <<<'TYPST'
#let almaden-page-styled(kind, body) = body
#set page(width: 20cm, height: 12cm, margin: 1cm, columns: 1)
#set text(size: 10pt)
#metadata("almaden-flow-1") <almaden-flow-1>
#par[Contenido original de la pagina.]
#metadata("almaden-flow-2") <almaden-flow-2>
#par[Segundo bloque original.]
TYPST;
	$physical_layout = array(
		'pre_body' => '#par[Continuacion anterior.]',
		'left_body' => '#par[' . implode( ' ', array_fill( 0, 70, 'izquierda' ) ) . ']',
		'deferred_body' => '#par[' . implode( ' ', array_fill( 0, 180, 'derecha' ) ) . ']',
		'left_ids' => array( 'almaden-flow-1' ),
		'deferred_ids' => array( 'almaden-flow-1' ),
		'page_ids' => array( 'almaden-flow-1', 'almaden-flow-2' ),
		'deferred_segments' => array(
			array( 'id' => 'almaden-flow-1', 'prefix' => '', 'body' => implode( ' ', array_fill( 0, 180, 'derecha' ) ) ),
		),
	);
	$physical_probe = almaden_bookster_typst_page_template_prepare_upper_bottom_right_layout_probe( $physical_source, $context, $template, $physical_layout );
	file_put_contents( $input, $physical_probe['source'], LOCK_EX );
	$command = escapeshellarg( $binary ) . ' query --root ' . escapeshellarg( $temp_dir ) . ' --diagnostic-format short ' . escapeshellarg( $input ) . ' ' . escapeshellarg( '<almaden-template-probe-report>' );
	exec( $command, $query_output, $query_status );
	$decoded = 0 === $query_status ? json_decode( implode( "\n", $query_output ), true ) : null;
	$physical_report = is_array( $decoded ) && is_array( $decoded[0]['value'] ?? null ) ? $decoded[0]['value'] : array();
	$physical_refined = almaden_bookster_typst_page_template_refine_upper_bottom_right_layout( $physical_probe, $physical_report );

	$compile_input = $temp_dir . '/balanced-inline.typ';
	$compile_output = $temp_dir . '/balanced-inline.pdf';
	$replacement = almaden_bookster_typst_page_template_render_upper_bottom_replacement(
		'0.8cm',
		'#par[' . $inline_left . ']',
		'#rect(width: 100%, height: 100%, fill: orange)',
		'#par[' . $inline_right . ']'
	);
	$compile_source = "#let almaden-page-styled(kind, body) = body\n#set page(width: 20cm, height: 12cm, margin: 1cm)\n#set text(size: 10pt)\n" . $replacement;
	file_put_contents( $compile_input, $compile_source, LOCK_EX );
	$compile_command = escapeshellarg( $binary ) . ' compile --root ' . escapeshellarg( $temp_dir ) . ' --diagnostic-format short ' . escapeshellarg( $compile_input ) . ' ' . escapeshellarg( $compile_output );
	exec( $compile_command, $compile_output_lines, $compile_status );

	unlink( $input );
	if ( is_file( $compile_input ) ) {
		unlink( $compile_input );
	}
	if ( is_file( $compile_output ) ) {
		unlink( $compile_output );
	}
	rmdir( $temp_dir );
	if ( empty( $physical_report['words'] ) || '' === trim( (string) ( $physical_refined['overflow_body'] ?? '' ) ) ) {
		fwrite( STDERR, "La medicion fisica de Typst no separo el excedente de la columna derecha.\n" );
		exit( 1 );
	}
	if ( 0 !== $compile_status ) {
		fwrite( STDERR, "El layout con formato inline cortado no compilo en Typst:\n" . implode( "\n", $compile_output_lines ) . "\n" );
		exit( 1 );
	}
}

echo "typst-page-template-upper-right-overflow-regression-ok\n";
