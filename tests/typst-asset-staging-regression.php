<?php
define( 'ABSPATH', __DIR__ . '/' );
define( 'ALMADEN_TYPST_TESTING', true );

class WP_Error {
	public $code;
	public $message;

	public function __construct( $code, $message ) {
		$this->code    = $code;
		$this->message = $message;
	}
}

function trailingslashit( $value ) { return rtrim( $value, '/\\' ) . '/'; }
function wp_generate_uuid4() { return uniqid( 'asset-', true ); }
function wp_mkdir_p( $path ) { return is_dir( $path ) || mkdir( $path, 0777, true ); }

require_once dirname( __DIR__ ) . '/includes/pdf-typst/typst-compiler-assets.php';
require_once dirname( __DIR__ ) . '/includes/pdf-typst/typst-fonts.php';

$temp_dir = sys_get_temp_dir() . '/almaden-asset-staging-' . wp_generate_uuid4();
$source   = $temp_dir . '-source.jpg';
$name     = hash( 'sha256', $source ) . '.jpg';
$bytes    = random_bytes( 2048 );
file_put_contents( $source, $bytes );

$result = almaden_bookster_typst_stage_assets( array( $name => $source ), $temp_dir );
$target = $temp_dir . '/assets/' . $name;
if ( $result instanceof WP_Error || ! is_file( $target ) || file_get_contents( $target ) !== $bytes ) {
	fwrite( STDERR, "El staging no conservó el asset completo.\n" );
	exit( 1 );
}

$missing = almaden_bookster_typst_stage_assets( array( hash( 'sha256', 'missing' ) . '.jpg' => $source . '.missing' ), $temp_dir );
if ( ! $missing instanceof WP_Error || 'typst_asset_missing' !== $missing->code ) {
	fwrite( STDERR, "Un asset ausente no produjo el error esperado.\n" );
	exit( 1 );
}

$font = almaden_bookster_typst_resolve_font( 'garamond', 400 );
if ( 'Libertinus Serif' !== ( $font['family'] ?? '' ) ) {
	fwrite( STDERR, "Garamond no se normalizó a una fuente disponible.\n" );
	exit( 1 );
}

unlink( $target );
rmdir( dirname( $target ) );
rmdir( $temp_dir );
unlink( $source );

echo "Typst asset staging regression: OK\n";
