<?php
define( 'ALMADEN_TYPST_TESTING', true );

require_once dirname( __DIR__ ) . '/includes/pdf-typst/page-templates/bootstrap.php';

$source = "#metadata(\"almaden-flow-1\") <almaden-flow-1>\n#par[Texto previo que termina en la pagina anterior y continua en la pagina elegida.]\n#metadata(\"almaden-flow-2\") <almaden-flow-2>\n#par[Texto que ya nace dentro de la pagina elegida.]\n";
$flow_map = array(
	array( 'id' => 'almaden-flow-1', 'page' => 15, 'x' => '10pt', 'y' => '20pt' ),
	array( 'id' => 'almaden-flow-2', 'page' => 16, 'x' => '10pt', 'y' => '120pt' ),
);
$template = array(
	'template_id' => 'one-image-one-column',
	'page_number' => 16,
	'anchor' => array( 'flow_id' => 'almaden-flow-2' ),
);
$probe = almaden_bookster_typst_page_template_prepare_page_start_probe( $source, $flow_map, $template );
if ( false === strpos( $probe['source'] ?? '', 'Texto#metadata("almaden-template-page-start-word-1")' )
	|| false !== strpos( $probe['source'] ?? '', '> Texto' ) ) {
	fwrite( STDERR, "La sonda alteró el espaciado o dejó el marcador antes de la palabra.\n" );
	exit( 1 );
}
$report = array(
	'block_id' => 'almaden-flow-1',
	'words' => array(
		array( 'word_count' => 1, 'page' => 15 ),
		array( 'word_count' => 2, 'page' => 15 ),
		array( 'word_count' => 3, 'page' => 16 ),
	),
);

$layout = almaden_bookster_typst_page_template_page_start_layout( $source, $flow_map, $template, $report );
if ( empty( $layout['pre_body'] ) || empty( $layout['left_body'] ) || ! in_array( 'almaden-flow-1', $layout['extra_page_ids'] ?? array(), true ) ) {
	fwrite( STDERR, "No se preparó el corte al inicio real de la página seleccionada.\n" );
	exit( 1 );
}

$result = almaden_bookster_typst_apply_page_template_flow(
	$source,
	array( 'columns_gap' => 0.8, 'unit' => 'cm', 'asset_mode' => 'original' ),
	$flow_map,
	$template,
	array( 'layout' => $layout, 'extra_page_ids' => $layout['extra_page_ids'] )
);
if ( false === strpos( $result, '#par[Texto previo ' ) || false === strpos( $result, '#grid(columns: (1fr, 1fr)' ) ) {
	fwrite( STDERR, "La plantilla no preservó el texto previo antes de insertar el layout.\n" );
	exit( 1 );
}

$binary = dirname( __DIR__ ) . '/runtime/typst/typst';
if ( is_executable( $binary ) ) {
	$temp_dir = sys_get_temp_dir() . '/almaden-page-start-' . getmypid();
	if ( ! is_dir( $temp_dir ) ) {
		mkdir( $temp_dir, 0700, true );
	}
	$input = $temp_dir . '/book.typ';
	$query = static function ( $source_value, $selector ) use ( $binary, $temp_dir, $input ) {
		file_put_contents( $input, $source_value, LOCK_EX );
		$command = escapeshellarg( $binary ) . ' query --root ' . escapeshellarg( $temp_dir ) . ' --diagnostic-format short ' . escapeshellarg( $input ) . ' ' . escapeshellarg( $selector );
		exec( $command, $output, $status );
		$decoded = 0 === $status ? json_decode( implode( "\n", $output ), true ) : null;
		return is_array( $decoded ) && is_array( $decoded[0]['value'] ?? null ) ? $decoded[0]['value'] : array();
	};
	$physical_template = array(
		'id' => 'tpl-physical',
		'instance_id' => 'tpl-physical',
		'template_id' => 'one-image-one-column',
		'page_number' => 2,
		'resolved_page' => 2,
		'anchor' => array( 'flow_id' => 'almaden-flow-2' ),
	);
	$physical_context = array(
		'templates' => array( $physical_template ),
		'columns_count' => 1,
		'columns_gap' => 0.8,
		'unit' => 'cm',
		'asset_mode' => 'original',
	);
	$base = <<<'TYPST'
#let almaden-page-styled(kind, body) = body
#set page(width: 10cm, height: 8cm, margin: 1cm, columns: 1)
#set text(size: 10pt)
#set par(justify: true)
TYPST;
	$fixture = '';
	$physical_flow = array();
	foreach ( range( 100, 240, 10 ) as $word_total ) {
		$fixture = $base . "\n#par[" . implode( ' ', array_fill( 0, $word_total, 'palabra' ) ) . "]\n#par[Ancla posterior dentro de la segunda pagina.]\n";
		$fixture = almaden_bookster_typst_compose_page_templates( $fixture, $physical_context );
		$physical_flow = $query( $fixture, '<almaden-flow-report>' );
		$anchor_y = (float) preg_replace( '/[^0-9.-]/', '', (string) ( $physical_flow[1]['y'] ?? 0 ) );
		if ( 1 === (int) ( $physical_flow[0]['page'] ?? 0 ) && 2 === (int) ( $physical_flow[1]['page'] ?? 0 ) && $anchor_y > 150 ) {
			break;
		}
	}
	$physical_probe = almaden_bookster_typst_page_template_prepare_page_start_probe( $fixture, $physical_flow, $physical_template );
	$physical_report = $query( $physical_probe['source'] ?? '', '<almaden-template-page-start-report>' );
	$physical_layout = almaden_bookster_typst_page_template_page_start_layout( $fixture, $physical_flow, $physical_template, $physical_report );
	$layout_probe = almaden_bookster_typst_page_template_prepare_layout_probe( $fixture, $physical_context, $physical_template, $physical_layout );
	$layout_report = $query( $layout_probe['source'] ?? '', '<almaden-template-probe-report>' );
	$physical_layout = almaden_bookster_typst_page_template_refine_layout( $physical_layout, $layout_probe, $layout_report );
	if ( '' === trim( (string) ( $physical_layout['deferred_body'] ?? '' ) ) ) {
		fwrite( STDERR, "La sonda de capacidad no difirió el texto que excedía el frame.\n" );
		exit( 1 );
	}
	$assets = array();
	$physical_result = almaden_bookster_typst_apply_page_template_flow(
		$fixture,
		$physical_context,
		$physical_flow,
		$physical_template,
		array( 'layout' => $physical_layout, 'extra_page_ids' => $physical_layout['extra_page_ids'] ?? array() ),
		$assets
	);
	$physical_result .= "\n#context [#metadata((page: query(<almaden-template-slot-tpl-physical-image-1>).first().location().page(), deferred_page: query(<almaden-flow-2>).first().location().page())) <almaden-physical-template-report>]\n";
	$physical_page = $query( $physical_result, '<almaden-physical-template-report>' );
	unlink( $input );
	rmdir( $temp_dir );
	if ( 2 !== (int) ( $physical_page['page'] ?? 0 ) || 3 > (int) ( $physical_page['deferred_page'] ?? 0 ) ) {
		fwrite( STDERR, 'La plantilla física no quedó en la página seleccionada: ' . json_encode( array( 'page' => $physical_page, 'flow' => $physical_flow, 'probe' => $physical_report, 'layout' => $physical_layout ) ) . "\n" );
		exit( 1 );
	}
}

echo "Page-template page-start regression: OK\n";
