<?php
define( 'ALMADEN_TYPST_TESTING', true );

require_once dirname( __DIR__ ) . '/includes/pdf-typst/page-templates/bootstrap.php';

$registry = almaden_bookster_typst_page_template_registry();
if ( empty( $registry['image-top-two-column-bottom'] ) ) {
	fwrite( STDERR, "El registry no expuso la plantilla de imagen superior con dos columnas inferiores.\n" );
	exit( 1 );
}

$template = array(
	'id'          => 'tpl-top-bottom',
	'instance_id' => 'tpl-top-bottom',
	'page_number' => 2,
	'template_id' => 'image-top-two-column-bottom',
);
if ( 'image-top-two-column-bottom' !== almaden_bookster_typst_page_template_layout_mode( $template ) ) {
	fwrite( STDERR, "La plantilla nueva no resolvió su modo de layout.\n" );
	exit( 1 );
}

$final = almaden_bookster_typst_page_template_render_image_top_two_column_bottom_replacement(
	'0.8cm',
	'Texto inferior',
	'Imagen superior'
);
if ( false === strpos( $final, '#grid(columns: (1fr,), rows: (42%, 1fr), gutter: 0.8cm)' ) || false === strpos( $final, '#columns(2, gutter: 0.8cm)' ) ) {
	fwrite( STDERR, "El render final no armó imagen superior y texto inferior en dos columnas.\n" );
	exit( 1 );
}
if ( false !== strpos( $final, 'height: 58%' ) ) {
	fwrite( STDERR, "El render final conserva un alto inferior que desborda junto al gutter.\n" );
	exit( 1 );
}
if ( false !== strpos( $final, 'almaden-template-probe-bottom' ) ) {
	fwrite( STDERR, "El render final incluyó metadata interna del probe.\n" );
	exit( 1 );
}

$context = array(
	'columns_count' => 2,
	'columns_gap'   => 0.8,
	'unit'          => 'cm',
	'templates'     => array(),
);
$probe = almaden_bookster_typst_page_template_probe_page( $context, $template, '#par[Texto inferior medible]' );
if ( false === strpos( $probe, 'almaden-template-probe-bottom' ) || false === strpos( $probe, '#columns(2, gutter: 0.8cm)' ) ) {
	fwrite( STDERR, "El probe no midió la geometría de dos columnas inferiores.\n" );
	exit( 1 );
}

$source = <<<'TYPST'
#let almaden-page-styled(kind, body) = body
#set page(width: 20cm, height: 12cm, margin: 1cm, columns: 2)
#metadata("almaden-flow-1") <almaden-flow-1>

#par[Texto que queda dentro de la plantilla.]
#metadata("almaden-flow-2") <almaden-flow-2>

#par[Texto que continúa después.]
TYPST;

$flow_map = array(
	array( 'id' => 'almaden-flow-1', 'page' => 2, 'x' => 10 ),
	array( 'id' => 'almaden-flow-2', 'page' => 3, 'x' => 10 ),
);
$output = almaden_bookster_typst_apply_page_template_flow( $source, $context, $flow_map, $template );
if ( false === strpos( $output, '#grid(columns: (1fr,), rows: (42%, 1fr), gutter: 0.8cm)' ) || false === strpos( $output, '#columns(2, gutter: 0.8cm)' ) ) {
	fwrite( STDERR, "La plantilla nueva no se aplicó sobre el flujo de página.\n" );
	exit( 1 );
}

$binary = dirname( __DIR__ ) . '/runtime/typst/typst';
if ( is_executable( $binary ) ) {
	$temp_dir = sys_get_temp_dir() . '/almaden-top-bottom-' . getmypid();
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
		fwrite( STDERR, "Typst no pudo compilar la plantilla nueva:\n" . implode( "\n", $compile_output ) . "\n" );
		exit( 1 );
	}
}

echo "typst-page-template-top-bottom-regression-ok\n";
