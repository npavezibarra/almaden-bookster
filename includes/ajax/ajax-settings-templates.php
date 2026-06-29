<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// --- AJAX Obtener Plantillas de Ajustes ---
function almaden_get_settings_templates_ajax() {
	$book_id = isset( $_POST['book_id'] ) ? intval( $_POST['book_id'] ) : 0;
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'almaden_save_settings_nonce_' . $book_id ) ) {
		wp_send_json_error( 'Validación de seguridad fallida.' );
	}

	$templates_dir = plugin_dir_path( dirname( __FILE__ ) ) . 'templates/settings/';
	$templates = array();

	if ( is_dir( $templates_dir ) ) {
		$files = glob( $templates_dir . '*.json' );
		foreach ( $files as $file ) {
			$content = file_get_contents( $file );
			if ( $content ) {
				$json = json_decode( $content, true );
				if ( json_last_error() === JSON_ERROR_NONE && isset( $json['name'], $json['settings'] ) ) {
					$json['id'] = basename( $file, '.json' );
					$templates[] = $json;
				}
			}
		}
	}

	wp_send_json_success( array( 'templates' => $templates ) );
}
add_action( 'wp_ajax_almaden_get_settings_templates', 'almaden_get_settings_templates_ajax' );
add_action( 'wp_ajax_nopriv_almaden_get_settings_templates', 'almaden_get_settings_templates_ajax' );

// --- AJAX Guardar Nueva Plantilla ---
function almaden_save_settings_template_ajax() {
	$book_id = isset( $_POST['book_id'] ) ? intval( $_POST['book_id'] ) : 0;
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'almaden_save_settings_nonce_' . $book_id ) ) {
		wp_send_json_error( 'Validación de seguridad fallida.' );
	}

	$template_name = sanitize_text_field( $_POST['template_name'] );
	if ( empty( $template_name ) ) {
		wp_send_json_error( 'El nombre de la plantilla es obligatorio.' );
	}

	$slug = sanitize_title( $template_name ) . '-' . time();
	$templates_dir = plugin_dir_path( dirname( __FILE__ ) ) . 'templates/settings/';
	
	if ( ! file_exists( $templates_dir ) ) {
		mkdir( $templates_dir, 0755, true );
	}

	$file_path = $templates_dir . $slug . '.json';

	// Recopilar configuración de las variables de POST, excluyendo cosas como nonce, action, book_id
	$settings_data = array();
	$exclude_keys = array('action', 'nonce', 'book_id', 'template_name');
	
	foreach ( $_POST as $key => $value ) {
		if ( ! in_array( $key, $exclude_keys ) && strpos( $key, 'credits_' ) !== 0 ) {
			// Convertir a float si parece numérico (por márgenes), o dejar como string
			if ( is_numeric( str_replace(',', '.', $value) ) && strpos( $value, ',' ) !== false ) {
				$settings_data[$key] = floatval( str_replace( ',', '.', $value ) );
			} else if ( is_numeric( $value ) ) {
				// No todos los numéricos son int/float, algunos pueden ser '0' vs 0, pero para JSON está bien
				$settings_data[$key] = strpos($value, '.') !== false ? floatval($value) : intval($value);
			} else {
				$settings_data[$key] = sanitize_text_field( wp_unslash( $value ) );
			}
		}
	}

	$template_data = array(
		'name' => $template_name,
		'description' => 'Plantilla personalizada guardada desde el editor.',
		'settings' => $settings_data
	);

	$saved = file_put_contents( $file_path, wp_json_encode( $template_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );

	if ( $saved !== false ) {
		wp_send_json_success( array( 'message' => 'Plantilla guardada con éxito.' ) );
	} else {
		wp_send_json_error( 'No se pudo escribir el archivo de la plantilla en el servidor.' );
	}
}
add_action( 'wp_ajax_almaden_save_settings_template', 'almaden_save_settings_template_ajax' );

// --- AJAX Eliminar Plantilla ---
function almaden_delete_settings_template_ajax() {
	$book_id = isset( $_POST['book_id'] ) ? intval( $_POST['book_id'] ) : 0;
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'almaden_save_settings_nonce_' . $book_id ) ) {
		wp_send_json_error( 'Validación de seguridad fallida.' );
	}

	$template_id = sanitize_text_field( $_POST['template_id'] );
	if ( empty( $template_id ) ) {
		wp_send_json_error( 'ID de plantilla inválido.' );
	}

	// Evitar directory traversal
	if ( strpos( $template_id, '/' ) !== false || strpos( $template_id, '\\' ) !== false || strpos( $template_id, '..' ) !== false ) {
		wp_send_json_error( 'ID de plantilla inválido.' );
	}

	$file_path = plugin_dir_path( dirname( __FILE__ ) ) . 'templates/settings/' . $template_id . '.json';

	if ( file_exists( $file_path ) ) {
		if ( unlink( $file_path ) ) {
			wp_send_json_success( array( 'message' => 'Plantilla eliminada.' ) );
		} else {
			wp_send_json_error( 'No se pudo eliminar el archivo.' );
		}
	} else {
		wp_send_json_error( 'La plantilla no existe.' );
	}
}
add_action( 'wp_ajax_almaden_delete_settings_template', 'almaden_delete_settings_template_ajax' );
