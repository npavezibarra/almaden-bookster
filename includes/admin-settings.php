<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}



// Handle AJAX save settings
function almaden_bookster_save_gdrive_settings() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Permisos insuficientes.' );
	}

	if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'almaden_gdrive_settings' ) ) {
		wp_send_json_error( 'Validación de seguridad fallida.' );
	}

	$client_email = isset( $_POST['gdrive_client_email'] ) ? sanitize_email( $_POST['gdrive_client_email'] ) : '';
	$private_key  = isset( $_POST['gdrive_private_key'] ) ? trim( $_POST['gdrive_private_key'] ) : '';
	$folder_id    = isset( $_POST['gdrive_folder_id'] ) ? sanitize_text_field( $_POST['gdrive_folder_id'] ) : '';

	update_option( 'bookcraft_gdrive_client_email', $client_email );
	update_option( 'bookcraft_gdrive_folder_id', $folder_id );

	if ( ! empty( $private_key ) && strpos( $private_key, '-----BEGIN PRIVATE KEY-----' ) !== false ) {
		$encrypted_key = almaden_encrypt_key( $private_key );
		if ( $encrypted_key ) {
			update_option( 'bookcraft_gdrive_private_key', $encrypted_key );
		}
	} else if ( empty( $private_key ) ) {
		// If empty, user might be clearing it. Wait, let's only clear if explicitly asked or left blank on purpose.
		// Actually, if it's empty, we just leave it alone so they don't have to re-enter it every time.
		// But if they want to clear it, we'd need a different mechanism. For now, empty means "no change".
	} else if ( $private_key === '***PROTECTED***' ) {
		// Do nothing, it was just the placeholder
	}

	wp_send_json_success( 'Configuración guardada correctamente.' );
}
add_action( 'wp_ajax_almaden_save_gdrive_settings', 'almaden_bookster_save_gdrive_settings' );
