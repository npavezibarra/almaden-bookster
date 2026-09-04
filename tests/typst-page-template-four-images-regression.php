<?php
define( 'ALMADEN_TYPST_TESTING', true );

require_once dirname( __DIR__ ) . '/includes/pdf-typst/page-templates/bootstrap.php';

$template = array(
	'id'          => 'tpl-four-images',
	'instance_id' => 'tpl-four-images',
	'page_number' => 2,
	'template_id' => 'four-images',
);
$registry = almaden_bookster_typst_page_template_registry();
if ( 4 !== count( $registry['four-images']['slots'] ?? array() ) ) {
	fwrite( STDERR, "La plantilla 4 images no expuso cuatro slots.\n" );
	exit( 1 );
}
foreach ( $registry['four-images']['slots'] as $slot ) {
	if ( 5 !== (int) ( $slot['aspect_ratio']['width'] ?? 0 ) || 4 !== (int) ( $slot['aspect_ratio']['height'] ?? 0 ) ) {
		fwrite( STDERR, "La plantilla 4 images no expuso la proporción editorial de sus slots.\n" );
		exit( 1 );
	}
}
if ( 'four-images-grid' !== almaden_bookster_typst_page_template_layout_mode( $template ) ) {
	fwrite( STDERR, "La plantilla 4 images no resolvió su layout.\n" );
	exit( 1 );
}

$context = array( 'columns_gap' => 0.8, 'unit' => 'cm', 'asset_mode' => 'original' );
$assets = array();
$placeholder = almaden_bookster_typst_page_template_placeholder( $template, $context, $assets );
if ( false === strpos( $placeholder, '#grid(columns: (1fr, 1fr), rows: (1fr, 1fr)' ) ) {
	fwrite( STDERR, "La plantilla 4 images no armó una grilla 2x2.\n" );
	exit( 1 );
}
foreach ( array( 'image-1', 'image-2', 'image-3', 'image-4' ) as $slot_id ) {
	$anchor = almaden_bookster_typst_page_template_slot_anchor_id( $template, array( 'id' => $slot_id ) );
	if ( false === strpos( $placeholder, '<' . $anchor . '>' ) ) {
		fwrite( STDERR, "La plantilla 4 images no renderizó el slot {$slot_id}.\n" );
		exit( 1 );
	}
}

$source = <<<'TYPST'
#let almaden-page-styled(kind, body) = body
#set page(width: 20cm, height: 12cm, margin: 1cm, columns: 2)
#metadata("almaden-flow-1") <almaden-flow-1>

#par[Texto que debe continuar después de la plantilla.]
TYPST;
$flow_map = array(
	array( 'id' => 'almaden-flow-1', 'page' => 2, 'x' => 10 ),
);
$output = almaden_bookster_typst_apply_page_template_flow( $source, $context, $flow_map, $template );
if ( false === strpos( $output, '#grid(columns: (1fr, 1fr), rows: (1fr, 1fr)' )
	|| false === strpos( $output, 'Texto que debe continuar después de la plantilla.' ) ) {
	fwrite( STDERR, "La plantilla 4 images no se aplicó conservando el texto diferido.\n" );
	exit( 1 );
}

$binary = dirname( __DIR__ ) . '/runtime/typst/typst';
if ( is_executable( $binary ) ) {
	$temp_dir = sys_get_temp_dir() . '/almaden-four-images-' . getmypid();
	if ( ! is_dir( $temp_dir ) ) {
		mkdir( $temp_dir, 0700, true );
	}
	$input = $temp_dir . '/book.typ';
	$pdf = $temp_dir . '/book.pdf';
	file_put_contents( $input, $output, LOCK_EX );
	$command = escapeshellarg( $binary ) . ' compile --root ' . escapeshellarg( $temp_dir ) . ' --diagnostic-format short ' . escapeshellarg( $input ) . ' ' . escapeshellarg( $pdf );
	exec( $command, $compile_output, $status );
	unlink( $input );
	if ( file_exists( $pdf ) ) {
		unlink( $pdf );
	}
	rmdir( $temp_dir );
	if ( 0 !== $status ) {
		fwrite( STDERR, "Typst no pudo compilar la plantilla 4 images:\n" . implode( "\n", $compile_output ) . "\n" );
		exit( 1 );
	}
}

echo "typst-page-template-four-images-regression-ok\n";
