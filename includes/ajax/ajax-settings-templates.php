<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function almaden_bookster_book_template_directories() {
	return array(
		'builtin' => plugin_dir_path( dirname( __FILE__ ) ) . 'templates/book-templates/',
		'custom'  => plugin_dir_path( dirname( __FILE__ ) ) . 'templates/book-templates/custom/',
		'legacy'  => plugin_dir_path( dirname( __FILE__ ) ) . 'templates/settings/',
	);
}

function almaden_bookster_normalize_book_template_payload( $json, $source = 'builtin', $fallback_id = '' ) {
	if ( ! is_array( $json ) ) {
		return null;
	}

	$name       = isset( $json['name'] ) ? sanitize_text_field( $json['name'] ) : '';
	$settings   = isset( $json['settings'] ) && is_array( $json['settings'] ) ? $json['settings'] : array();
	$visibility = isset( $json['visibility'] ) ? sanitize_key( $json['visibility'] ) : ( 'custom' === $source ? 'private' : 'public' );

	if ( '' === $name || empty( $settings ) ) {
		return null;
	}

	$template_id = isset( $json['id'] ) ? sanitize_title( $json['id'] ) : sanitize_title( $fallback_id );
	if ( '' === $template_id ) {
		$template_id = sanitize_title( $name );
	}

	$sample_chapters = array();
	if ( isset( $json['sample_chapters'] ) && is_array( $json['sample_chapters'] ) ) {
		$sample_chapters = $json['sample_chapters'];
	}

	$preview = array();
	if ( isset( $json['preview'] ) && is_array( $json['preview'] ) ) {
		$preview = $json['preview'];
	}

	return array(
		'id'              => $template_id,
		'kind'            => 'book-template',
		'name'            => $name,
		'description'     => isset( $json['description'] ) ? sanitize_text_field( $json['description'] ) : '',
		'visibility'      => $visibility,
		'source'          => $source,
		'settings'        => $settings,
		'preview'         => $preview,
		'sample_chapters' => $sample_chapters,
	);
}

function almaden_bookster_collect_book_template_files() {
	$dirs = almaden_bookster_book_template_directories();
	$files = array();

	foreach ( array( 'legacy', 'builtin', 'custom' ) as $key ) {
		$dir = $dirs[ $key ] ?? '';
		if ( is_dir( $dir ) ) {
			$matches = glob( trailingslashit( $dir ) . '*.json' );
			if ( is_array( $matches ) ) {
				foreach ( $matches as $match ) {
					$files[] = array(
						'path'    => $match,
						'source'  => $key,
						'priority'=> 'custom' === $key ? 3 : ( 'builtin' === $key ? 2 : 1 ),
					);
				}
			}
		}
	}

	usort(
		$files,
		static function( $a, $b ) {
			if ( $a['priority'] === $b['priority'] ) {
				return strcmp( $a['path'], $b['path'] );
			}
			return $a['priority'] <=> $b['priority'];
		}
	);

	return $files;
}

// --- AJAX Obtener Book Templates ---
function almaden_get_book_templates_ajax() {
	$book_id = isset( $_POST['book_id'] ) ? intval( $_POST['book_id'] ) : 0;
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'almaden_save_settings_nonce_' . $book_id ) ) {
		wp_send_json_error( 'Validación de seguridad fallida.' );
	}

	$templates = array();
	foreach ( almaden_bookster_collect_book_template_files() as $entry ) {
		$content = file_get_contents( $entry['path'] );
		if ( ! $content ) {
			continue;
		}

		$json = json_decode( $content, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			continue;
		}

		$normalized = almaden_bookster_normalize_book_template_payload(
			$json,
			$entry['source'],
			basename( $entry['path'], '.json' )
		);
		if ( $normalized ) {
			$templates[ $normalized['id'] ] = $normalized;
		}
	}

	wp_send_json_success( array( 'templates' => array_values( $templates ) ) );
}
add_action( 'wp_ajax_almaden_get_book_templates', 'almaden_get_book_templates_ajax' );
add_action( 'wp_ajax_nopriv_almaden_get_book_templates', 'almaden_get_book_templates_ajax' );
add_action( 'wp_ajax_almaden_get_settings_templates', 'almaden_get_book_templates_ajax' );
add_action( 'wp_ajax_nopriv_almaden_get_settings_templates', 'almaden_get_book_templates_ajax' );

// --- AJAX Guardar Nuevo Book Template ---
function almaden_save_book_template_ajax() {
	$book_id = isset( $_POST['book_id'] ) ? intval( $_POST['book_id'] ) : 0;
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'almaden_save_settings_nonce_' . $book_id ) ) {
		wp_send_json_error( 'Validación de seguridad fallida.' );
	}

	$template_name = sanitize_text_field( $_POST['template_name'] ?? '' );
	if ( empty( $template_name ) ) {
		wp_send_json_error( 'El nombre del book template es obligatorio.' );
	}

	$slug = sanitize_title( $template_name ) . '-' . time();
	$dirs = almaden_bookster_book_template_directories();
	$templates_dir = $dirs['custom'];

	if ( ! file_exists( $templates_dir ) ) {
		wp_mkdir_p( $templates_dir );
	}

	$file_path = trailingslashit( $templates_dir ) . $slug . '.json';

	$settings_data = array();
	$exclude_keys = array( 'action', 'nonce', 'book_id', 'template_name', 'template_description', 'template_visibility', 'template_preview', 'sample_chapters' );

	foreach ( $_POST as $key => $value ) {
		if ( ! in_array( $key, $exclude_keys, true ) && strpos( $key, 'credits_' ) !== 0 ) {
			if ( is_array( $value ) ) {
				$settings_data[ $key ] = array_map( 'sanitize_text_field', wp_unslash( $value ) );
				continue;
			}
			$raw_value = wp_unslash( $value );
			if ( is_numeric( str_replace( ',', '.', $raw_value ) ) && strpos( (string) $raw_value, ',' ) !== false ) {
				$settings_data[ $key ] = floatval( str_replace( ',', '.', $raw_value ) );
			} elseif ( is_numeric( $raw_value ) ) {
				$settings_data[ $key ] = strpos( (string) $raw_value, '.' ) !== false ? floatval( $raw_value ) : intval( $raw_value );
			} else {
				$settings_data[ $key ] = sanitize_text_field( $raw_value );
			}
		}
	}

	$template_data = array(
		'id'              => $slug,
		'kind'            => 'book-template',
		'name'            => $template_name,
		'description'     => sanitize_text_field( $_POST['template_description'] ?? 'Book template personalizado guardado desde el editor.' ),
		'visibility'      => sanitize_key( $_POST['template_visibility'] ?? 'private' ),
		'source'          => 'custom',
		'settings'        => $settings_data,
		'preview'         => array(),
		'sample_chapters' => array(),
	);

	$saved = file_put_contents( $file_path, wp_json_encode( $template_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );

	if ( $saved !== false ) {
		wp_send_json_success(
			array(
				'message'  => 'Book template guardado con éxito.',
				'template' => $template_data,
				'template_id' => $slug,
			)
		);
	} else {
		wp_send_json_error( 'No se pudo escribir el archivo del book template en el servidor.' );
	}
}
add_action( 'wp_ajax_almaden_save_book_template', 'almaden_save_book_template_ajax' );
add_action( 'wp_ajax_almaden_save_settings_template', 'almaden_save_book_template_ajax' );

// --- AJAX Eliminar Book Template ---
function almaden_delete_book_template_ajax() {
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

	$dirs = almaden_bookster_book_template_directories();
	$file_path = trailingslashit( $dirs['custom'] ) . $template_id . '.json';

	if ( file_exists( $file_path ) ) {
		if ( unlink( $file_path ) ) {
			wp_send_json_success( array( 'message' => 'Book template eliminado.' ) );
		} else {
			wp_send_json_error( 'No se pudo eliminar el archivo.' );
		}
	} else {
		wp_send_json_error( 'El book template no existe.' );
	}
}
add_action( 'wp_ajax_almaden_delete_book_template', 'almaden_delete_book_template_ajax' );
add_action( 'wp_ajax_almaden_delete_settings_template', 'almaden_delete_book_template_ajax' );
