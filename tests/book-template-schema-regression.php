<?php
define( 'ABSPATH', __DIR__ . '/' );

function add_action() {}
function sanitize_key( $value ) {
	return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) );
}
function sanitize_text_field( $value ) {
	return trim( strip_tags( (string) $value ) );
}
function sanitize_title( $value ) {
	return trim( preg_replace( '/[^a-z0-9]+/', '-', strtolower( sanitize_text_field( $value ) ) ), '-' );
}
function absint( $value ) {
	return abs( (int) $value );
}

require_once dirname( __DIR__ ) . '/includes/book-templates/repository.php';

$legacy = almaden_bookster_normalize_book_template_payload(
	array(
		'name'     => 'Plantilla anterior',
		'settings' => array(
			'page_width'      => 14,
			'page_templates'  => array( array( 'id' => 'opening' ) ),
			'ebook_bg_color'  => '#f4efe5',
			'book_language'   => 'es',
			'book_authors'    => 'persona@example.com',
			'credits_config'  => array( 'editorial' => array( 'isbn' => '00123' ) ),
		),
	)
);

if ( ! $legacy || 2 !== $legacy['schema_version'] ) {
	fwrite( STDERR, "La plantilla anterior no se convirtió al esquema v2.\n" );
	exit( 1 );
}
if ( 14 !== $legacy['settings']['pdf']['page_width'] || '#f4efe5' !== $legacy['settings']['ebook']['ebook_bg_color'] || 'es' !== $legacy['settings']['global']['book_language'] ) {
	fwrite( STDERR, "Los ámbitos PDF, EBOOK y GLOBAL no se clasificaron correctamente.\n" );
	exit( 1 );
}
if ( isset( $legacy['settings']['global']['book_authors'] ) || isset( $legacy['settings']['pdf']['book_authors'] ) ) {
	fwrite( STDERR, "La plantilla incluyó autores propios del libro.\n" );
	exit( 1 );
}
if ( '00123' !== $legacy['settings']['pdf']['credits_config']['editorial']['isbn'] ) {
	fwrite( STDERR, "La normalización perdió valores anidados.\n" );
	exit( 1 );
}

$flat = almaden_bookster_flatten_book_template_settings( $legacy['settings'] );
if ( 14 !== $flat['page_width'] || '#f4efe5' !== $flat['ebook_bg_color'] || 'es' !== $flat['content_language'] ) {
	fwrite( STDERR, "La compatibilidad con consumidores planos no conserva todos los ámbitos.\n" );
	exit( 1 );
}

$complete = almaden_bookster_normalize_book_template_payload(
	array(
		'name'           => 'Plantilla completa',
		'schema_version' => 2,
		'settings'       => array(
			'pdf'    => array( 'page_width' => 15 ),
			'ebook'  => array( 'ebook_bg_color' => '#ffffff' ),
			'global' => array( 'book_language' => 'en' ),
		),
	)
);
if ( ! $complete || ! empty( $complete['missing_scopes'] ) ) {
	fwrite( STDERR, "Una plantilla v2 completa aparece como incompleta.\n" );
	exit( 1 );
}

$partial = almaden_bookster_normalize_book_template_payload(
	array(
		'name'     => 'Plantilla solo PDF',
		'settings' => array( 'page_width' => 13 ),
	)
);
$partial_round_trip = almaden_bookster_normalize_book_template_payload(
	array(
		'name'                  => $partial['name'],
		'schema_version'        => 2,
		'source_schema_version' => $partial['source_schema_version'],
		'missing_scopes'        => $partial['missing_scopes'],
		'settings'              => $partial['settings'],
	)
);
if ( array( 'ebook', 'global' ) !== $partial_round_trip['missing_scopes'] ) {
	fwrite( STDERR, "La migración perdió los ámbitos faltantes de una plantilla parcial.\n" );
	exit( 1 );
}

$custom = almaden_bookster_normalize_book_template_payload(
	array(
		'name'   => 'Plantilla promovida',
		'source' => 'custom',
		'settings' => array(
			'pdf'    => array( 'page_width' => 16 ),
			'ebook'  => array( 'ebook_bg_color' => '#fefefe' ),
			'global' => array( 'book_language' => 'pt' ),
		),
	),
	'custom',
	'custom-123'
);
if ( ! $custom || 'system' !== $custom['origin'] || 'custom' !== $custom['source'] ) {
	fwrite( STDERR, "Una plantilla promovida no se clasifica como estándar.\n" );
	exit( 1 );
}

echo "Book template schema regression: OK\n";
