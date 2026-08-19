<?php
$wp_load = dirname( __DIR__, 4 ) . '/wp-load.php';
if ( ! file_exists( $wp_load ) ) {
	fwrite( STDERR, "No se encontró wp-load.php.\n" );
	exit( 1 );
}

require_once $wp_load;

function almaden_template_test_fail( $message ) {
	throw new RuntimeException( $message );
}

$admin_id = almaden_bookster_get_legacy_template_migration_owner_id();
$non_admin_user_ids = array();
foreach ( get_users( array( 'fields' => 'ids' ) ) as $candidate_id ) {
	wp_set_current_user( $candidate_id );
	if ( ! current_user_can( 'manage_options' ) ) {
		$non_admin_user_ids[] = (int) $candidate_id;
	}
}

if ( $admin_id <= 0 || count( $non_admin_user_ids ) < 2 ) {
	fwrite( STDERR, "La prueba requiere un administrador y dos usuarios sin permisos administrativos.\n" );
	exit( 1 );
}
$owner_id = $non_admin_user_ids[0];
$other_user_id = $non_admin_user_ids[1];

$created_id = 0;
try {
	wp_set_current_user( $owner_id );
	$system_templates = almaden_bookster_read_system_book_templates();
	if ( empty( $system_templates ) || 'system' !== $system_templates[0]['origin'] ) {
		almaden_template_test_fail( 'No se pudieron leer las plantillas estándar.' );
	}

	$created = almaden_bookster_save_personal_book_template(
		array(
			'name'        => 'Plantilla temporal de regresión',
			'description' => 'Se elimina al terminar la prueba.',
			'settings'    => array(
				'page_width'     => 14.5,
				'credits_config' => array(
					'editorial' => array( 'isbn' => '00123' ),
					'people'    => array( array( 'name' => 'Autora de prueba', 'role' => 'author' ) ),
				),
			),
		),
		$owner_id
	);
	if ( is_wp_error( $created ) ) {
		almaden_template_test_fail( $created->get_error_message() );
	}

	$created_id = almaden_bookster_parse_personal_book_template_id( $created['id'] );
	if ( $created_id <= 0 || '00123' !== $created['settings']['credits_config']['editorial']['isbn'] ) {
		almaden_template_test_fail( 'La plantilla no conservó sus ajustes anidados.' );
	}
	$owner_templates = almaden_bookster_get_personal_book_templates( $owner_id );
	if ( ! in_array( $created['id'], wp_list_pluck( $owner_templates, 'id' ), true ) ) {
		almaden_template_test_fail( 'El propietario no puede ver su propia plantilla privada.' );
	}

	wp_set_current_user( $other_user_id );
	$other_templates = almaden_bookster_get_personal_book_templates( $other_user_id );
	if ( in_array( $created['id'], wp_list_pluck( $other_templates, 'id' ), true ) ) {
		almaden_template_test_fail( 'La plantilla personal quedó visible para otro usuario.' );
	}
	if ( almaden_bookster_user_can_mutate_personal_book_template( $created['id'], $other_user_id ) ) {
		almaden_template_test_fail( 'Otro usuario puede modificar la plantilla personal.' );
	}

	wp_set_current_user( $owner_id );
	$updated = almaden_bookster_save_personal_book_template(
		array(
			'name'     => $created['name'],
			'settings' => array( 'page_width' => 15.25 ),
		),
		$owner_id,
		$created['id']
	);
	if ( is_wp_error( $updated ) || 15.25 !== $updated['settings']['page_width'] ) {
		almaden_template_test_fail( 'No se pudo actualizar la plantilla personal.' );
	}

	if ( almaden_bookster_user_can_mutate_personal_book_template( $system_templates[0]['id'], $owner_id ) ) {
		almaden_template_test_fail( 'Una plantilla estándar aparece como modificable.' );
	}
} catch ( Throwable $error ) {
	fwrite( STDERR, $error->getMessage() . "\n" );
	$exit_code = 1;
} finally {
	if ( $created_id > 0 ) {
		wp_delete_post( $created_id, true );
	}
}

if ( ! empty( $exit_code ) ) {
	exit( $exit_code );
}

echo "Book templates regression: OK\n";
