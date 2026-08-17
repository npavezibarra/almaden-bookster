<?php
define( 'ABSPATH', __DIR__ . '/' );
define( 'ALMADEN_TYPST_TESTING', true );

$uploads_dir = sys_get_temp_dir() . '/almaden-chapter-image-' . uniqid();
mkdir( $uploads_dir );
$image_path = $uploads_dir . '/chapter.png';
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
function wp_generate_uuid4() { return uniqid( 'chapter-', true ); }
function wp_mkdir_p( $path ) { return is_dir( $path ) || mkdir( $path, 0777, true ); }

class WP_Error {
	public function __construct( public $code, public $message ) {}
}

require_once dirname( __DIR__ ) . '/includes/pdf-typst/typst-document.php';
require_once dirname( __DIR__ ) . '/includes/pdf-typst/typst-compiler-assets.php';

$chapter_image = 'http://almaden.local/wp-content/uploads/chapter.png';
$chapter = static function ( $title, $mode, $width = 100 ) use ( $chapter_image ) {
	return array(
		'title'                     => $title,
		'content'                   => 'Contenido de prueba.',
		'chapter_image_enabled'     => '1',
		'chapter_image_mode'        => $mode,
		'chapter_image_url'         => $chapter_image,
		'chapter_image_inner_width' => $width,
		'hide_header'               => '1',
		'hide_footer'               => '1',
	);
};
$document = almaden_bookster_build_typst_document(
	array(
		'title'    => 'Regresion de imagen de capitulo',
		'settings' => array(
			'unit'                      => 'cm',
			'bleed'                     => 0.3,
			'font_family_content'       => 'Libertinus Serif',
			'chapter_title_font_family' => 'Libertinus Serif',
			'header_font_family'        => 'Libertinus Serif',
			'footer_font_family'        => 'Libertinus Serif',
		),
		'chapters' => array(
			$chapter( 'Content box', 'page_blank' ),
			$chapter( 'Full bleed', 'image_full_page' ),
			$chapter( 'Ajustable', 'image_inner', 50 ),
		),
	)
);

$source = $document['source'];
$expected_fragments = array(
	'background: {',
	'almaden-page-background()',
	'box(width: 100%, height: 100%)[#place(top + left)[#almaden-page-background()]#place(center + horizon)',
	'width: 100%, height: 100%, fit: "cover"',
	'<almaden-chapter-image-page>',
	'<almaden-hide-header-page>',
	'<almaden-hide-footer-page>',
	'#set page(background: almaden-page-background())',
);
foreach ( $expected_fragments as $fragment ) {
	if ( false === strpos( $source, $fragment ) ) {
		fwrite( STDERR, "Falta geometria Typst para imagen de capitulo: {$fragment}\n" );
		exit( 1 );
	}
}
if ( false !== strpos( $source, "background: {\n#" ) ) {
	fwrite( STDERR, "El fondo de imagen reintrodujo markup (#) dentro de un bloque de codigo Typst.\n" );
	exit( 1 );
}
if ( preg_match( '/#metadata\("[^"]*"\) <almaden-hide-header>/', $source ) || preg_match( '/#metadata\("[^"]*"\) <almaden-hide-footer>/', $source ) ) {
	fwrite( STDERR, "Las banderas de ocultacion siguen afectando todo el capitulo en vez de solo la primera pagina de texto.\n" );
	exit( 1 );
}
$adjustable_source = almaden_bookster_typst_chapter_image_background_source( 'assets/chapter.png', 'image_inner', 9.8, 'cm', 50 );
if ( false === strpos( $adjustable_source, 'width: 4.9cm' ) || false !== strpos( $adjustable_source, 'fit: "cover"' ) ) {
	fwrite( STDERR, "El modo ajustable no conserva ancho porcentual y alto automatico.\n" );
	exit( 1 );
}

$compile_dir = $uploads_dir . '/compile';
mkdir( $compile_dir );
almaden_bookster_typst_stage_assets( $document['assets'], $compile_dir );
file_put_contents( $compile_dir . '/book.typ', $source );
$binary = dirname( __DIR__ ) . '/runtime/typst/typst';
$command = escapeshellarg( $binary ) . ' compile --root ' . escapeshellarg( $compile_dir ) . ' --diagnostic-format short ' . escapeshellarg( $compile_dir . '/book.typ' ) . ' ' . escapeshellarg( $compile_dir . '/book.pdf' ) . ' 2>&1';
exec( $command, $diagnostics, $status );
if ( 0 !== $status || ! is_file( $compile_dir . '/book.pdf' ) ) {
	fwrite( STDERR, "Typst no compilo los tres modos de imagen:\n" . implode( "\n", $diagnostics ) . "\n" );
	exit( 1 );
}

unlink( $compile_dir . '/book.pdf' );
unlink( $compile_dir . '/book.typ' );
foreach ( array_keys( $document['assets'] ) as $asset_name ) {
	unlink( $compile_dir . '/assets/' . $asset_name );
}
rmdir( $compile_dir . '/assets' );
rmdir( $compile_dir );
unlink( $image_path );
rmdir( $uploads_dir );

echo "Typst chapter image regression: OK\n";
