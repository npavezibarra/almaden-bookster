<?php
define( 'ALMADEN_TYPST_TESTING', true );

function almaden_bookster_typst_resolve_image_url_for_asset_mode( $slot, $asset_mode = 'original' ) {
	return (string) ( $slot['original_url'] ?? $slot['url'] ?? '' );
}

function almaden_bookster_typst_resolve_image_path_for_asset_mode( $slot, $asset_mode = 'original' ) {
	return (string) ( $slot['test_source_path'] ?? '' );
}

require_once dirname( __DIR__ ) . '/includes/pdf-typst/page-templates/bootstrap.php';

$source_path = tempnam( sys_get_temp_dir(), 'almaden-slot-diagnostic-' );
file_put_contents( $source_path, 'image-bytes' );

$template = array(
	'instance_id' => 'template-59',
	'resolved_page' => 59,
	'template_id' => 'inner-full-page',
);
$renderable = almaden_bookster_typst_page_template_slot_asset_diagnostic(
	$template,
	array(
		'id' => 'image-1',
		'attachment_id' => 42,
		'original_url' => 'http://almaden.local/wp-content/uploads/example.jpg',
		'test_source_path' => $source_path,
	)
);
if ( empty( $renderable['assigned'] ) || empty( $renderable['renderable'] ) || 'renderable' !== $renderable['reason'] || 59 !== $renderable['page_number'] ) {
	fwrite( STDERR, "El diagnóstico no reconoció un slot renderizable.\n" );
	@unlink( $source_path );
	exit( 1 );
}

$missing = almaden_bookster_typst_page_template_slot_asset_diagnostic(
	$template,
	array(
		'id' => 'image-1',
		'attachment_id' => 42,
		'original_url' => 'http://almaden.local/wp-content/uploads/missing.jpg',
		'test_source_path' => $source_path . '.missing',
	)
);
if ( empty( $missing['assigned'] ) || ! empty( $missing['renderable'] ) || 'source_file_unavailable' !== $missing['reason'] ) {
	fwrite( STDERR, "El diagnóstico no identificó un archivo de slot ausente.\n" );
	@unlink( $source_path );
	exit( 1 );
}

$unassigned = almaden_bookster_typst_page_template_slot_asset_diagnostic( $template, array( 'id' => 'image-1' ) );
if ( ! empty( $unassigned['assigned'] ) || ! empty( $unassigned['renderable'] ) || 'unassigned' !== $unassigned['reason'] ) {
	fwrite( STDERR, "El diagnóstico no identificó un slot sin asignación.\n" );
	@unlink( $source_path );
	exit( 1 );
}

$audit = almaden_bookster_typst_page_template_asset_audit(
	array(
		array_merge(
			$template,
			array(
				'slots' => array(
					array(
						'id' => 'image-1',
						'attachment_id' => 42,
						'original_url' => 'http://almaden.local/wp-content/uploads/example.jpg',
						'test_source_path' => $source_path,
					),
				),
			)
		),
		array(
			'instance_id' => 'template-60',
			'resolved_page' => 59,
			'template_id' => 'inner-full-page',
			'slots' => array(
				array(
					'id' => 'image-1',
					'attachment_id' => 42,
					'original_url' => 'http://almaden.local/wp-content/uploads/example.jpg',
					'test_source_path' => $source_path,
				),
			),
		),
	),
);
if ( 2 !== ( $audit['counts']['total'] ?? 0 ) || 2 !== ( $audit['counts']['assigned'] ?? 0 ) || 2 !== ( $audit['counts']['renderable'] ?? 0 ) || 1 !== count( $audit['duplicates'] ?? array() ) || 1 !== count( $audit['page_collisions'] ?? array() ) ) {
	fwrite( STDERR, "La auditoría global no resumió correctamente los slots duplicados y la colisión de página.\n" );
	@unlink( $source_path );
	exit( 1 );
}

@unlink( $source_path );
echo "Typst page-template asset diagnostics regression: OK\n";
