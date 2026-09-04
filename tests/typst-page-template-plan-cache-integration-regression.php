<?php
define( 'ALMADEN_TYPST_TESTING', true );

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $value ) {
		return $value instanceof WP_Error;
	}
}
if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {}
}

require_once dirname( __DIR__ ) . '/includes/pdf-typst/typst-compiler-assets.php';
require_once dirname( __DIR__ ) . '/includes/pdf-typst/page-templates/bootstrap.php';

$token = uniqid( 'cache-flow-', true );
$template = array(
	'id' => 'tpl-cache-integration',
	'instance_id' => 'tpl-cache-integration',
	'template_id' => 'one-column-one-image',
	'page_number' => 1,
	'resolved_page' => 1,
	'anchor' => array( 'flow_id' => 'almaden-flow-1' ),
	'slots' => array( array( 'id' => 'image-1', 'kind' => 'image', 'attachment_id' => 0 ) ),
);
$context = array(
	'templates' => array( $template ),
	'columns_count' => 2,
	'columns_gap' => 0.8,
	'unit' => 'cm',
	'asset_mode' => 'original',
);
$base_source = "#let almaden-page-styled(kind, body) = body\n#set page(width: 20cm, height: 12cm, margin: 1cm)\n#par[Texto $token que ocupa la primera columna de la pagina.]\n#par[Texto posterior que permanece en la pagina.]\n";
$source = almaden_bookster_typst_compose_page_templates( $base_source, $context );
$flow_map = array(
	array( 'id' => 'almaden-flow-1', 'page' => 1, 'x' => '10pt', 'y' => '20pt' ),
	array( 'id' => 'almaden-flow-2', 'page' => 1, 'x' => '110pt', 'y' => '20pt' ),
);
$temp_dir = sys_get_temp_dir() . '/almaden-plan-integration-' . getmypid();
if ( ! is_dir( $temp_dir ) ) {
	mkdir( $temp_dir, 0700, true );
}
$input = $temp_dir . '/book.typ';
$run = static function () use ( $source, $context, $template, $flow_map, $input, $temp_dir ) {
	$document = array(
		'source' => $source,
		'page_templates' => array( $template ),
		'page_template_context' => $context,
		'assets' => array(),
	);
	file_put_contents( $input, $source, LOCK_EX );
	$query_count = 0;
	$query = static function ( $selector ) use ( &$query_count, $flow_map ) {
		++$query_count;
		if ( '<almaden-flow-report>' === $selector ) {
			return $flow_map;
		}
		return array(
			'bottom' => array( 'page' => 1, 'y' => '100pt' ),
			'words' => array(
				array( 'id' => 'almaden-template-probe-word-1', 'page' => 1, 'y' => '20pt' ),
				array( 'id' => 'almaden-template-probe-word-2', 'page' => 1, 'y' => '120pt' ),
			),
		);
	};
	$GLOBALS['almaden_bookster_typst_page_template_results'] = array();
	$result = almaden_bookster_typst_compile_page_templates( $document, $input, $temp_dir, $query );
	return array( 'result' => $result, 'queries' => $query_count, 'document' => $document, 'reports' => $GLOBALS['almaden_bookster_typst_page_template_results'] );
};

$first = $run();
$second = $run();
$plan_key = almaden_bookster_typst_page_template_plan_key( $source, $template, $context, '' );
@unlink( almaden_bookster_typst_page_template_plan_cache_dir() . '/' . $plan_key . '.json' );
@unlink( $input );
@rmdir( $temp_dir );
if ( is_wp_error( $first['result'] ) || is_wp_error( $second['result'] ) || $first['queries'] <= $second['queries'] ) {
	fwrite( STDERR, 'El plan incremental no redujo consultas: ' . json_encode( array( $first['queries'], $second['queries'] ) ) . "\n" );
	exit( 1 );
}
if ( 2 !== $second['queries'] || 'hit' !== (string) ( $second['reports'][0]['debug']['plan_cache'] ?? '' ) ) {
	fwrite( STDERR, 'La segunda composicion no uso el plan cacheado: ' . json_encode( $second['reports'] ) . "\n" );
	exit( 1 );
}

echo "typst-page-template-plan-cache-integration-regression-ok\n";
