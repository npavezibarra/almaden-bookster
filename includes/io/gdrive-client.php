<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helper: Base64 URL Encode
 */
function almaden_base64url_encode( $data ) {
	return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
}

/**
 * Get Google Drive Access Token using Service Account credentials.
 */
function almaden_get_gdrive_access_token() {
	$client_email = get_option( 'bookcraft_gdrive_client_email', '' );
	$encrypted_key = get_option( 'bookcraft_gdrive_private_key', '' );
	
	if ( empty( $client_email ) || empty( $encrypted_key ) ) {
		return new WP_Error( 'missing_credentials', 'Faltan credenciales de Google Drive.' );
	}
	
	$private_key = almaden_decrypt_key( $encrypted_key );
	if ( ! $private_key ) {
		return new WP_Error( 'invalid_key', 'No se pudo descifrar la clave privada.' );
	}

	// Fix: Clean up the private key
	// Remove surrounding quotes if the user copied them from the JSON file
	$private_key = trim( $private_key, "\"'" );
	// Convert literal \n strings (often pasted directly from JSON) into actual newlines
	// We handle multiple possible escaping layers from WordPress POST handling
	$private_key = str_replace( array( "\\\\n", "\\n", '\n' ), "\n", $private_key );
	// Create JWT header
	$header = json_encode( [
		'alg' => 'RS256',
		'typ' => 'JWT'
	] );

	// Create JWT payload
	$now = time();
	$payload = json_encode( [
		'iss'   => $client_email,
		'scope' => 'https://www.googleapis.com/auth/drive.file https://www.googleapis.com/auth/drive',
		'aud'   => 'https://oauth2.googleapis.com/token',
		'exp'   => $now + 3600,
		'iat'   => $now
	] );

	$base64_header = almaden_base64url_encode( $header );
	$base64_payload = almaden_base64url_encode( $payload );
	$signature_input = $base64_header . '.' . $base64_payload;

	$signature = '';
	if ( ! openssl_sign( $signature_input, $signature, $private_key, 'SHA256' ) ) {
		return new WP_Error( 'signing_failed', 'Falló la firma del JWT.' );
	}

	$jwt = $signature_input . '.' . almaden_base64url_encode( $signature );

	// Request access token
	$response = wp_remote_post( 'https://oauth2.googleapis.com/token', array(
		'body' => array(
			'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
			'assertion'  => $jwt
		)
	) );

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$body = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( isset( $body['access_token'] ) ) {
		return $body['access_token'];
	}

	return new WP_Error( 'token_error', isset( $body['error_description'] ) ? $body['error_description'] : 'Error desconocido al obtener token.' );
}

/**
 * Handle AJAX test connection
 */
function almaden_test_gdrive_connection() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Permisos insuficientes.' );
	}

	if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'almaden_gdrive_settings' ) ) {
		wp_send_json_error( 'Validación de seguridad fallida.' );
	}

	$token = almaden_get_gdrive_access_token();
	if ( is_wp_error( $token ) ) {
		wp_send_json_error( $token->get_error_message() );
	}

	$folder_id = get_option( 'bookcraft_gdrive_folder_id', '' );
	if ( empty( $folder_id ) ) {
		wp_send_json_success( 'Autenticación exitosa (Token generado correctamente). Sin embargo, no has especificado un Folder ID para verificar permisos.' );
	}

	// Verify folder
	$url = 'https://www.googleapis.com/drive/v3/files/' . $folder_id . '?fields=id,name,capabilities';
	$response = wp_remote_get( $url, array(
		'headers' => array(
			'Authorization' => 'Bearer ' . $token
		)
	) );

	if ( is_wp_error( $response ) ) {
		wp_send_json_error( 'Autenticación exitosa, pero hubo un error de red al consultar la carpeta: ' . $response->get_error_message() );
	}

	$code = wp_remote_retrieve_response_code( $response );
	$body = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( $code === 200 && isset( $body['id'] ) ) {
		if ( isset( $body['capabilities']['canAddChildren'] ) && $body['capabilities']['canAddChildren'] ) {
			wp_send_json_success( 'Autenticación exitosa y la carpeta "' . $body['name'] . '" fue encontrada con permisos de escritura.' );
		} else {
			wp_send_json_error( 'Autenticación exitosa y carpeta encontrada ("' . $body['name'] . '"), pero la Service Account NO tiene permisos para escribir en ella. Asegúrate de darle permisos de Editor.' );
		}
	} else {
		$error = isset( $body['error']['message'] ) ? $body['error']['message'] : 'Desconocido';
		wp_send_json_error( 'Autenticación exitosa, pero no se pudo acceder al Folder ID. ¿Estás seguro de que lo compartiste con el Client Email? Error de Google: ' . $error );
	}
}
add_action( 'wp_ajax_almaden_test_gdrive_connection', 'almaden_test_gdrive_connection' );

/**
 * Handle AJAX Export Book to Drive (JSON Backup)
 */
function almaden_export_book_to_drive() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Permisos insuficientes.' );
	}

	$book_id = isset( $_POST['book_id'] ) ? intval( $_POST['book_id'] ) : 0;
	if ( ! $book_id ) {
		wp_send_json_error( 'ID de libro inválido.' );
	}

	$book = get_post( $book_id );
	if ( ! $book || $book->post_type !== 'almaden-books' ) {
		wp_send_json_error( 'Libro no encontrado.' );
	}

	$token = almaden_get_gdrive_access_token();
	if ( is_wp_error( $token ) ) {
		wp_send_json_error( 'Error de autenticación Drive: ' . $token->get_error_message() );
	}

	$folder_id = get_option( 'bookcraft_gdrive_folder_id', '' );
	if ( empty( $folder_id ) ) {
		wp_send_json_error( 'Falta configurar el Folder ID en los ajustes de Google APIs.' );
	}

	// Recopilar datos del libro
	$source_book_id = get_post_meta( $book_id, '_almaden_source_book_id', true );
	if ( empty( $source_book_id ) ) {
		$source_book_id = $book_id;
	}

	$chapters = get_posts( array(
		'post_type'      => 'book_chapter',
		'post_parent'    => $source_book_id,
		'posts_per_page' => -1,
		'orderby'        => 'menu_order',
		'order'          => 'ASC',
	) );

	global $wpdb;
	$settings_table = $wpdb->prefix . 'almaden_book_pdf_settings';
	$settings = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $settings_table WHERE book_id = %d", $book_id ), ARRAY_A );

	$export_data = array(
		'book_id'    => $book_id,
		'title'      => $book->post_title,
		'created_at' => current_time('mysql'),
		'settings'   => $settings ? json_decode( $settings['settings_json'], true ) : array(),
		'chapters'   => array(),
	);

	foreach ( $chapters as $chapter ) {
		$export_data['chapters'][] = array(
			'title'   => $chapter->post_title,
			'content' => $chapter->post_content,
			'order'   => $chapter->menu_order,
		);
	}

	$json_content = wp_json_encode( $export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
	$filename = sanitize_file_name( $book->post_title ) . '_Backup_' . date('Ymd_His') . '.json';

	// Subir archivo a Google Drive (Multipart)
	$boundary = wp_generate_password( 24, false );
	
	$metadata = array(
		'name'     => $filename,
		'parents'  => array( $folder_id ),
		'mimeType' => 'application/json',
	);

	$multipart_body  = "--$boundary\r\n";
	$multipart_body .= "Content-Type: application/json; charset=UTF-8\r\n\r\n";
	$multipart_body .= wp_json_encode( $metadata ) . "\r\n";
	$multipart_body .= "--$boundary\r\n";
	$multipart_body .= "Content-Type: application/json\r\n\r\n";
	$multipart_body .= $json_content . "\r\n";
	$multipart_body .= "--$boundary--";

	$url = 'https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart';
	$response = wp_remote_post( $url, array(
		'headers' => array(
			'Authorization' => 'Bearer ' . $token,
			'Content-Type'  => 'multipart/related; boundary=' . $boundary,
			'Content-Length' => strlen( $multipart_body )
		),
		'body'    => $multipart_body,
		'timeout' => 30
	) );

	if ( is_wp_error( $response ) ) {
		wp_send_json_error( 'Error de red al subir archivo: ' . $response->get_error_message() );
	}

	$code = wp_remote_retrieve_response_code( $response );
	$body = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( $code === 200 && isset( $body['id'] ) ) {
		wp_send_json_success( 'Backup subido exitosamente con el nombre: ' . $filename );
	} else {
		$error_msg = isset( $body['error']['message'] ) ? $body['error']['message'] : 'Desconocido';
		wp_send_json_error( 'Error de la API de Drive: ' . $error_msg );
	}
}
add_action( 'wp_ajax_almaden_export_book_to_drive', 'almaden_export_book_to_drive' );
