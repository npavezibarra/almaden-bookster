<?php
define( 'ABSPATH', __DIR__ . '/' );
define( 'ALMADEN_TYPST_TESTING', true );
define( 'MB_IN_BYTES', 1048576 );
define( 'DAY_IN_SECONDS', 86400 );

function add_action() {}
function absint( $value ) { return abs( (int) $value ); }
function trailingslashit( $value ) { return rtrim( $value, '/\\' ) . '/'; }
function get_temp_dir() { return sys_get_temp_dir() . '/'; }
function wp_json_encode( $value ) { return json_encode( $value ); }
function wp_generate_uuid4() { return uniqid( 'cache-', true ); }
function wp_mkdir_p( $path ) { return is_dir( $path ) || mkdir( $path, 0777, true ); }

require_once dirname( __DIR__ ) . '/includes/ajax/ajax-typst-pdf.php';

$client_state_source = file_get_contents( dirname( __DIR__ ) . '/assets/js/pdf/typst/editor-typst-pdf-state.js' );
if ( false === strpos( $client_state_source, "const PREVIEW_CACHE_VERSION = 'v9';" ) ) {
	fwrite( STDERR, "La caché persistente del navegador no fue invalidada para el nuevo renderer del Índice.\n" );
	exit( 1 );
}

$book_id = 987654;
$cache_dir = almaden_bookster_typst_preview_cache_dir( $book_id );
$asset = sys_get_temp_dir() . '/almaden-cache-regression-asset.txt';
file_put_contents( $asset, 'asset-v1' );
$document = array(
	'source'         => '#set page(width: 20cm)',
	'page_templates' => array(),
	'assets'         => array( 'asset.txt' => $asset ),
	'font_assets'    => array(),
);
$key = almaden_bookster_typst_preview_cache_key( $document );
$pdf = "%PDF-1.7\ncache regression\n%%EOF";
$meta = array( 'universal_counter' => array( 'version' => 1 ) );

$missing_assets_a = array();
$missing_assets_b = array();
$missing_source = sys_get_temp_dir() . '/almaden-cache-regression-missing.jpg';
$missing_asset_a = almaden_bookster_typst_page_template_asset_path_from_source( $missing_source, $missing_assets_a );
sleep( 1 );
$missing_asset_b = almaden_bookster_typst_page_template_asset_path_from_source( $missing_source, $missing_assets_b );
if ( $missing_asset_a !== $missing_asset_b || array_keys( $missing_assets_a ) !== array_keys( $missing_assets_b ) ) {
	fwrite( STDERR, "Un asset ausente produjo una firma no determinista.\n" );
	exit( 1 );
}

almaden_bookster_typst_preview_cache_write( $book_id, $key, $pdf, $meta );
$cached = almaden_bookster_typst_preview_cache_read( $book_id, $key );
if ( ! is_array( $cached ) || $pdf !== $cached['pdf'] || $meta !== $cached['meta'] ) {
	fwrite( STDERR, "El caché no pudo recuperar una compilación válida.\n" );
	exit( 1 );
}

file_put_contents( $asset, 'asset-v2-with-new-size' );
clearstatcache( true, $asset );
$changed_key = almaden_bookster_typst_preview_cache_key( $document );
if ( $key === $changed_key ) {
	fwrite( STDERR, "El caché no se invalidó al cambiar un asset.\n" );
	exit( 1 );
}

foreach ( glob( $cache_dir . '/*' ) ?: array() as $path ) {
	unlink( $path );
}
if ( is_dir( $cache_dir ) ) {
	rmdir( $cache_dir );
}
@rmdir( dirname( $cache_dir ) );
unlink( $asset );

echo "Typst preview cache regression: OK\n";
