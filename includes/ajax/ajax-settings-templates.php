<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function almaden_bookster_validate_book_template_library_nonce( $nonce, $book_id = 0 ) {
	$nonce = sanitize_text_field( (string) $nonce );
	if ( '' === $nonce ) {
		return false;
	}

	if ( wp_verify_nonce( $nonce, 'almaden_book_templates_library' ) ) {
		return true;
	}

	return $book_id > 0 && wp_verify_nonce( $nonce, 'almaden_save_settings_nonce_' . (int) $book_id );
}

function almaden_bookster_require_book_template_access( $book_id ) {
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( 'Debes iniciar sesión para gestionar plantillas.', 401 );
	}

	$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
	if ( ! almaden_bookster_validate_book_template_library_nonce( $nonce, $book_id ) ) {
		wp_send_json_error( 'Validación de seguridad fallida.', 403 );
	}

	if ( $book_id > 0 ) {
		$can_manage = function_exists( 'almaden_bookster_user_can_manage_book' )
			? almaden_bookster_user_can_manage_book( $book_id )
			: current_user_can( 'edit_post', $book_id );
		if ( ! $can_manage ) {
			wp_send_json_error( 'No tienes permisos para gestionar las plantillas de este libro.', 403 );
		}
		return;
	}

	$can_manage_books = function_exists( 'almaden_bookster_user_can_manage_books' )
		? almaden_bookster_user_can_manage_books()
		: current_user_can( 'edit_posts' );
	if ( ! $can_manage_books ) {
		wp_send_json_error( 'No tienes permisos para gestionar plantillas de libro.', 403 );
	}
}

function almaden_bookster_get_template_payload_from_request() {
	$settings = array();
	if ( isset( $_POST['settings'] ) ) {
		$decoded = json_decode( wp_unslash( $_POST['settings'] ), true );
		if ( is_array( $decoded ) ) {
			$settings = $decoded;
		}
	}

	return array(
		'id'              => isset( $_POST['template_id'] ) ? sanitize_text_field( wp_unslash( $_POST['template_id'] ) ) : '',
		'kind'            => 'book-template',
		'name'            => isset( $_POST['template_name'] ) ? sanitize_text_field( wp_unslash( $_POST['template_name'] ) ) : '',
		'description'     => isset( $_POST['template_description'] ) ? sanitize_text_field( wp_unslash( $_POST['template_description'] ) ) : 'Plantilla personal guardada desde el editor.',
		'schema_version'  => ALMADEN_BOOK_TEMPLATE_SCHEMA_VERSION,
		'settings'        => $settings,
		'preview'         => array(),
		'sample_chapters' => array(),
	);
}

function almaden_get_book_templates_ajax() {
	$book_id = isset( $_POST['book_id'] ) ? absint( $_POST['book_id'] ) : 0;
	almaden_bookster_require_book_template_access( $book_id );

	$system_templates = almaden_bookster_read_system_book_templates();
	$personal_templates = almaden_bookster_get_personal_book_templates( get_current_user_id() );
	foreach ( $system_templates as &$template ) {
		unset( $template['_path'] );
	}
	unset( $template );

	wp_send_json_success(
		array(
			'templates' => array_merge( $system_templates, $personal_templates ),
			'groups'    => array(
				'system'   => count( $system_templates ),
				'personal' => count( $personal_templates ),
			),
		)
	);
}
add_action( 'wp_ajax_almaden_get_book_templates', 'almaden_get_book_templates_ajax' );
add_action( 'wp_ajax_almaden_get_settings_templates', 'almaden_get_book_templates_ajax' );

function almaden_create_book_template_ajax() {
	$book_id = isset( $_POST['book_id'] ) ? absint( $_POST['book_id'] ) : 0;
	almaden_bookster_require_book_template_access( $book_id );

	$payload = almaden_bookster_get_template_payload_from_request();
	$template = almaden_bookster_save_personal_book_template( $payload, get_current_user_id() );
	if ( is_wp_error( $template ) ) {
		wp_send_json_error( $template->get_error_message(), 400 );
	}

	wp_send_json_success(
		array(
			'message'  => 'Plantilla creada con éxito.',
			'template' => $template,
		),
		201
	);
}
add_action( 'wp_ajax_almaden_create_book_template', 'almaden_create_book_template_ajax' );
add_action( 'wp_ajax_almaden_save_book_template', 'almaden_create_book_template_ajax' );
add_action( 'wp_ajax_almaden_save_settings_template', 'almaden_create_book_template_ajax' );

function almaden_update_book_template_ajax() {
	$book_id = isset( $_POST['book_id'] ) ? absint( $_POST['book_id'] ) : 0;
	almaden_bookster_require_book_template_access( $book_id );

	$template_id = isset( $_POST['template_id'] ) ? sanitize_text_field( wp_unslash( $_POST['template_id'] ) ) : '';
	if ( ! almaden_bookster_user_can_mutate_personal_book_template( $template_id ) ) {
		wp_send_json_error( 'No puedes actualizar esta plantilla.', 403 );
	}

	$existing = almaden_bookster_get_personal_book_template( $template_id );
	$payload = almaden_bookster_get_template_payload_from_request();
	if ( empty( $payload['name'] ) && $existing ) {
		$payload['name'] = $existing['name'];
	}
	if ( empty( $payload['description'] ) && $existing ) {
		$payload['description'] = $existing['description'];
	}

	$template = almaden_bookster_save_personal_book_template( $payload, (int) $existing['owner_id'], $template_id );
	if ( is_wp_error( $template ) ) {
		wp_send_json_error( $template->get_error_message(), 400 );
	}

	wp_send_json_success(
		array(
			'message'  => 'Plantilla actualizada con éxito.',
			'template' => $template,
		)
	);
}
add_action( 'wp_ajax_almaden_update_book_template', 'almaden_update_book_template_ajax' );

function almaden_promote_book_template_to_standard_ajax() {
	$book_id = isset( $_POST['book_id'] ) ? absint( $_POST['book_id'] ) : 0;
	almaden_bookster_require_book_template_access( $book_id );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Solo un administrador puede convertir una plantilla en estándar.', 403 );
	}

	$template_id = isset( $_POST['template_id'] ) ? sanitize_text_field( wp_unslash( $_POST['template_id'] ) ) : '';
	$template = almaden_bookster_get_personal_book_template( $template_id );
	if ( ! $template ) {
		wp_send_json_error( 'No se encontró la plantilla personal solicitada.', 404 );
	}

	$template_key = 'promoted-' . absint( almaden_bookster_parse_personal_book_template_id( $template_id ) );
	$system_template = almaden_bookster_save_system_book_template( $template, $template_key );
	if ( is_wp_error( $system_template ) ) {
		wp_send_json_error( $system_template->get_error_message(), 400 );
	}

	wp_send_json_success(
		array(
			'message'  => 'Plantilla convertida en estándar con éxito.',
			'template' => $system_template,
		)
	);
}
add_action( 'wp_ajax_almaden_promote_book_template_to_standard', 'almaden_promote_book_template_to_standard_ajax' );

function almaden_delete_book_template_ajax() {
	$book_id = isset( $_POST['book_id'] ) ? absint( $_POST['book_id'] ) : 0;
	almaden_bookster_require_book_template_access( $book_id );

	$template_id = isset( $_POST['template_id'] ) ? sanitize_text_field( wp_unslash( $_POST['template_id'] ) ) : '';
	if ( ! almaden_bookster_user_can_mutate_personal_book_template( $template_id ) ) {
		wp_send_json_error( 'No puedes eliminar esta plantilla.', 403 );
	}

	$post_id = almaden_bookster_parse_personal_book_template_id( $template_id );
	if ( ! wp_delete_post( $post_id, true ) ) {
		wp_send_json_error( 'No se pudo eliminar la plantilla.', 500 );
	}

	wp_send_json_success( array( 'message' => 'Plantilla eliminada.' ) );
}
add_action( 'wp_ajax_almaden_delete_book_template', 'almaden_delete_book_template_ajax' );
add_action( 'wp_ajax_almaden_delete_settings_template', 'almaden_delete_book_template_ajax' );

function almaden_upload_book_template_ajax() {
	$book_id = isset( $_POST['book_id'] ) ? absint( $_POST['book_id'] ) : 0;
	almaden_bookster_require_book_template_access( $book_id );

	if ( empty( $_FILES['template_file']['tmp_name'] ) || ! is_uploaded_file( $_FILES['template_file']['tmp_name'] ) ) {
		wp_send_json_error( 'No se recibió ningún archivo JSON.', 400 );
	}

	if ( ! empty( $_FILES['template_file']['size'] ) && (int) $_FILES['template_file']['size'] > 2 * MB_IN_BYTES ) {
		wp_send_json_error( 'La plantilla supera el tamaño máximo de 2 MB.', 400 );
	}

	$content = file_get_contents( $_FILES['template_file']['tmp_name'] );
	$json = false !== $content ? json_decode( $content, true ) : null;
	if ( ! is_array( $json ) ) {
		wp_send_json_error( 'El archivo no contiene un JSON válido.', 400 );
	}

	$template = almaden_bookster_save_personal_book_template( $json, get_current_user_id() );
	if ( is_wp_error( $template ) ) {
		wp_send_json_error( $template->get_error_message(), 400 );
	}

	wp_send_json_success(
		array(
			'message'  => 'Plantilla importada con éxito.',
			'template' => $template,
		),
		201
	);
}
add_action( 'wp_ajax_almaden_upload_book_template', 'almaden_upload_book_template_ajax' );

function almaden_bookster_prepare_template_for_export( $template ) {
	return array(
		'id'              => sanitize_title( $template['template_key'] ?? $template['name'] ?? 'book-template' ),
		'kind'            => 'book-template',
		'name'            => $template['name'],
		'description'     => $template['description'] ?? '',
		'visibility'      => 'private',
		'source'          => 'custom',
		'schema_version'  => $template['schema_version'] ?? ALMADEN_BOOK_TEMPLATE_SCHEMA_VERSION,
		'source_schema_version' => $template['source_schema_version'] ?? ( $template['schema_version'] ?? ALMADEN_BOOK_TEMPLATE_SCHEMA_VERSION ),
		'missing_scopes'  => $template['missing_scopes'] ?? array(),
		'settings'        => $template['settings'],
		'preview'         => $template['preview'] ?? array(),
		'sample_chapters' => $template['sample_chapters'] ?? array(),
	);
}

function almaden_download_book_template_ajax() {
	$book_id = isset( $_POST['book_id'] ) ? absint( $_POST['book_id'] ) : 0;
	almaden_bookster_require_book_template_access( $book_id );

	$template_id = isset( $_POST['template_id'] ) ? sanitize_text_field( wp_unslash( $_POST['template_id'] ) ) : '';
	if ( 0 === strpos( $template_id, 'system:' ) ) {
		$template = almaden_bookster_get_system_book_template( $template_id );
	} else {
		$template = almaden_bookster_get_personal_book_template( $template_id );
		if ( ! $template || ( (int) $template['owner_id'] !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) ) {
			wp_send_json_error( 'No puedes descargar esta plantilla.', 403 );
		}
	}

	if ( ! $template ) {
		wp_send_json_error( 'No se encontró la plantilla solicitada.', 404 );
	}

	$export = almaden_bookster_prepare_template_for_export( $template );
	$content = wp_json_encode( $export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	$filename = sanitize_file_name( sanitize_title( $template['name'] ) . '.json' );

	nocache_headers();
	header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
	header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
	header( 'Content-Length: ' . strlen( $content ) );
	echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit;
}
add_action( 'wp_ajax_almaden_download_book_template', 'almaden_download_book_template_ajax' );
