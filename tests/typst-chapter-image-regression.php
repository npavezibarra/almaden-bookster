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
function sanitize_text_field( $value ) { return trim( preg_replace( '/[\r\n\t]+/', ' ', strip_tags( (string) $value ) ) ); }
function trailingslashit( $value ) { return rtrim( $value, '/\\' ) . '/'; }
function wp_generate_uuid4() { return uniqid( 'chapter-', true ); }
function wp_mkdir_p( $path ) { return is_dir( $path ) || mkdir( $path, 0777, true ); }

class WP_Error {
	public function __construct( public $code, public $message ) {}
}

require_once dirname( __DIR__ ) . '/includes/pdf-typst/page-styles/bootstrap.php';
require_once dirname( __DIR__ ) . '/includes/pdf-typst/typst-document.php';
require_once dirname( __DIR__ ) . '/includes/pdf-typst/typst-compiler-assets.php';
require_once dirname( __DIR__ ) . '/includes/pdf-typst/typst-pdf-boxes.php';

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
			'bleeding'                  => 0.3,
			'font_family_content'       => 'Libertinus Serif',
			'chapter_title_font_family' => 'Libertinus Serif',
			'header_font_family'        => 'Libertinus Serif',
			'footer_font_family'        => 'Libertinus Serif',
			'page_styles'               => array(
				array(
					'page_number'   => 2,
					'resolved_page' => 2,
					'style'         => array(
						'background' => array(
							'type'    => 'image',
							'image'   => array( 'url' => $chapter_image ),
							'overlay' => array(
								'color'   => '#000000',
								'opacity' => 0.1,
							),
						),
					),
				),
			),
		),
		'chapters' => array(
			$chapter( 'Content box', 'page_blank' ),
			$chapter( 'Full bleed', 'image_full_page' ),
			$chapter( 'Ajustable', 'image_inner', 50 ),
			array(
				'title'                  => 'Placeholder sin imagen',
				'content'                => 'Contenido de prueba.',
				'chapter_image_override' => '1',
				'chapter_image_mode'     => 'image_full_page',
				'chapter_image_url'      => '',
			),
			array(
				'title'                  => 'Imagen legacy desactivada',
				'content'                => 'Contenido de prueba sin pagina de imagen previa.',
				'chapter_image_override' => '0',
				'chapter_image_enabled'  => '0',
				'parity_image'           => $chapter_image,
			),
		),
	)
);

$source = $document['source'];
$expected_fragments = array(
	'background: {',
	'almaden-page-background()',
	'#let almaden-page-style-image-pages = (2,)',
	'if almaden-page-style-image-pages.contains(current) { almaden-page-background() } else {',
	'box(width: 100%, height: 100%)[#place(top + left)[#image(',
	'width: 100%, height: 100%, fit: "cover")]',
	'<almaden-chapter-image-page>',
	'<almaden-hide-header-page>',
	'<almaden-hide-footer-page>',
	'#set page(background: almaden-page-background())',
	'#set page(width: 21.6cm, height: 30.3cm,',
	'binding: left, bleed: 0pt,',
);
foreach ( $expected_fragments as $fragment ) {
	if ( false === strpos( $source, $fragment ) ) {
		fwrite( STDERR, "Falta geometria Typst para imagen de capitulo: {$fragment}\n" );
		exit( 1 );
	}
}
if ( false === strpos( $source, '#metadata("chapter-image-placeholder") <almaden-chapter-image-page>' ) ) {
	fwrite( STDERR, "La politica de imagen no reserva una pagina cuando falta la imagen del capitulo.\n" );
	exit( 1 );
}
$disabled_legacy_position = strpos( $source, 'Imagen legacy desactivada' );
if ( false === $disabled_legacy_position ) {
	fwrite( STDERR, "No se encontro el capitulo de control con imagen legacy desactivada.\n" );
	exit( 1 );
}
$disabled_legacy_source = substr( $source, max( 0, $disabled_legacy_position - 600 ), 600 );
if ( false !== strpos( $disabled_legacy_source, '<almaden-chapter-image-page>' ) ) {
	fwrite( STDERR, "Un capitulo con Iniciar con Imagen = No sigue emitiendo una pagina de imagen legacy.\n" );
	exit( 1 );
}
if ( false !== strpos( $source, "background: {\n#" ) ) {
	fwrite( STDERR, "El fondo de imagen reintrodujo markup (#) dentro de un bloque de codigo Typst.\n" );
	exit( 1 );
}
if ( preg_match( '/#metadata\("[^"]*"\) <almaden-hide-header>/', $source ) || preg_match( '/#metadata\("[^"]*"\) <almaden-hide-footer>/', $source ) ) {
	fwrite( STDERR, "Las banderas de ocultacion siguen afectando todo el capitulo en vez de solo la primera pagina de texto.\n" );
	exit( 1 );
}
if ( false !== strpos( $source, 'dx: -almaden-bleed' ) || false !== strpos( $source, 'outset: almaden-bleed' ) ) {
	fwrite( STDERR, "El fondo todavía desplaza o expande contenido dentro del recorte nativo de Typst.\n" );
	exit( 1 );
}
$full_bleed_source = almaden_bookster_typst_chapter_image_background_source( 'assets/chapter.png', 'image_full_page', 10.4, 20.4, 0.2, 'cm', 50 );
if ( false === strpos( $full_bleed_source, 'width: 100%, height: 100%, fit: "cover"' ) || false === strpos( $full_bleed_source, 'almaden-page-style-image-pages.contains(current)' ) ) {
	fwrite( STDERR, "El modo full bleed no prioriza la superficie única del estilo por página.\n" );
	exit( 1 );
}
if ( false !== strpos( $full_bleed_source, '#almaden-page-image-overlay()' ) ) {
	fwrite( STDERR, "El modo full bleed todavía duplica el overlay del fondo por página.\n" );
	exit( 1 );
}
$adjustable_source = almaden_bookster_typst_chapter_image_background_source( 'assets/chapter.png', 'image_inner', 10.4, 20.4, 0.2, 'cm', 50 );
if ( false === strpos( $adjustable_source, 'width: 5.2cm' ) || false !== strpos( $adjustable_source, 'fit: "cover"' ) ) {
	fwrite( STDERR, "El modo ajustable no usa el ancho total incluyendo bleed.\n" );
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
$print_boxes = almaden_bookster_typst_apply_print_boxes( $compile_dir . '/book.pdf', $document['geometry'] );
if ( $print_boxes instanceof WP_Error ) {
	fwrite( STDERR, "No se pudieron declarar las cajas de imprenta: {$print_boxes->code}: {$print_boxes->message}\n" );
	exit( 1 );
}
$box_output = array();
$box_status = 0;
exec( 'pdfinfo -f 2 -l 2 -box ' . escapeshellarg( $compile_dir . '/book.pdf' ) . ' 2>&1', $box_output, $box_status );
$box_text = implode( "\n", $box_output );
if ( 0 !== $box_status || false === strpos( $box_text, 'Page    2 TrimBox:' ) || false === strpos( $box_text, 'Page    2 BleedBox:' ) ) {
	fwrite( STDERR, "El PDF final no conserva TrimBox y BleedBox verificables.\n{$box_text}\n" );
	exit( 1 );
}

if ( '1' === getenv( 'ALMADEN_KEEP_PDF_TEST' ) ) {
	echo "PDF de regresion: {$compile_dir}/book.pdf\n";
	exit( 0 );
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
