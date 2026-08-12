<?php
define( 'ABSPATH', __DIR__ . '/' );
define( 'ALMADEN_TYPST_TESTING', true );

$uploads_dir = sys_get_temp_dir() . '/almaden-content-image-' . uniqid();
mkdir( $uploads_dir );
$image_path = $uploads_dir . '/image.png';
file_put_contents( $image_path, base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=' ) );

function wp_upload_dir() {
	global $uploads_dir;
	return array(
		'baseurl' => 'http://almaden.local/wp-content/uploads',
		'basedir' => $uploads_dir,
	);
}

function esc_url_raw( $value ) { return trim( (string) $value ); }
function trailingslashit( $value ) { return rtrim( $value, '/\\' ) . '/'; }
function wp_generate_uuid4() { return uniqid( 'image-', true ); }
function wp_mkdir_p( $path ) { return is_dir( $path ) || mkdir( $path, 0777, true ); }

class WP_Error {
	public function __construct( public $code, public $message ) {}
}

require_once dirname( __DIR__ ) . '/includes/pdf-typst/typst-document.php';
require_once dirname( __DIR__ ) . '/includes/pdf-typst/typst-compiler-assets.php';

$html = '<figure class="pdf-book-image-block" data-image-block="1" data-image-block-id="photo-joyce" data-height-mode="fixed" data-height-percent="35" data-margin-top-mm="4" data-margin-bottom-mm="6" data-caption-gap-mm="0.5" data-caption-align="center" data-zoom="1.2" data-position="75% 25%"><div><img src="http://almaden.local/wp-content/uploads/image.png" /></div><figcaption class="pdf-book-image-caption">James Joyce en París, 1924.</figcaption></figure>';
$second_html = str_replace( array( 'photo-joyce', 'James Joyce en París, 1924.' ), array( 'photo-joyce-2', 'James Joyce en Zúrich, 1918.' ), $html );
$document = almaden_bookster_build_typst_document(
	array(
		'title'    => 'Regresión de imagen',
		'settings' => array(
			'unit' => 'cm',
			'font_family_content' => 'Libertinus Serif',
			'chapter_title_font_family' => 'Libertinus Serif',
			'header_font_family' => 'Libertinus Serif',
			'footer_font_family' => 'Libertinus Serif',
		),
		'chapters' => array(
			array( 'title' => 'Capítulo', 'content' => $html . "\n\n" . $second_html ),
		),
	)
);
$name = hash( 'sha256', $image_path . '|' . filemtime( $image_path ) ) . '.png';

if ( false === strpos( $document['source'], 'assets/' . $name ) ) {
	fwrite( STDERR, "El markup Typst no contiene la referencia de imagen esperada.\n" );
	exit( 1 );
}
if ( ! isset( $document['assets'][ $name ] ) || $image_path !== $document['assets'][ $name ] ) {
	fwrite( STDERR, "El renderer perdió el asset asociado al bloque de imagen.\n" );
	exit( 1 );
}
foreach ( array( '#v(4mm)', '#v(6mm)', '#v(0.5mm)', 'height:', '#scale(x: 120%, y: 120%, origin: top + right)', 'James Joyce en París, 1924.', '<almaden-image-report>' ) as $expected ) {
	if ( false === strpos( $document['source'], $expected ) ) {
		fwrite( STDERR, "Falta configuración de imagen Typst: {$expected}\n" );
		exit( 1 );
	}
}

$compile_dir = $uploads_dir . '/compile';
mkdir( $compile_dir );
almaden_bookster_typst_stage_assets( $document['assets'], $compile_dir );
file_put_contents( $compile_dir . '/book.typ', $document['source'] );
$binary = dirname( __DIR__ ) . '/runtime/typst/typst';
$command = escapeshellarg( $binary ) . ' compile --root ' . escapeshellarg( $compile_dir ) . ' --diagnostic-format short ' . escapeshellarg( $compile_dir . '/book.typ' ) . ' ' . escapeshellarg( $compile_dir . '/book.pdf' ) . ' 2>&1';
exec( $command, $diagnostics, $status );
if ( 0 !== $status || ! is_file( $compile_dir . '/book.pdf' ) ) {
	fwrite( STDERR, "Typst no compiló el bloque de imagen:\n" . implode( "\n", $diagnostics ) . "\n" );
	exit( 1 );
}
$query_command = escapeshellarg( $binary ) . ' query --root ' . escapeshellarg( $compile_dir ) . ' --diagnostic-format short ' . escapeshellarg( $compile_dir . '/book.typ' ) . ' ' . escapeshellarg( '<almaden-image-report>' ) . ' 2>&1';
$query_output = array();
exec( $query_command, $query_output, $query_status );
$query_json = implode( "\n", $query_output );
$query_json = substr( $query_json, strpos( $query_json, '[' ), strrpos( $query_json, ']' ) - strpos( $query_json, '[' ) + 1 );
$reports = json_decode( $query_json, true );
$report_ids = array_column( array_column( (array) $reports, 'value' ), 'id' );
if ( 0 !== $query_status || ! in_array( 'photo-joyce', $report_ids, true ) || ! in_array( 'photo-joyce-2', $report_ids, true ) || empty( $reports[0]['value']['page'] ) ) {
	fwrite( STDERR, "Typst no devolvió la geometría seleccionable de la imagen.\n" );
	exit( 1 );
}

unlink( $compile_dir . '/book.pdf' );
unlink( $compile_dir . '/book.typ' );
unlink( $compile_dir . '/assets/' . $name );
rmdir( $compile_dir . '/assets' );
rmdir( $compile_dir );
unlink( $image_path );
rmdir( $uploads_dir );

echo "Typst content image regression: OK\n";
