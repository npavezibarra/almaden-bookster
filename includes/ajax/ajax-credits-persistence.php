<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Persiste la configuracion estructurada de creditos y sus metadatos legacy.
 * Ambos endpoints de guardado usan esta funcion para mantener un solo contrato.
 */
function almaden_bookster_credits_debug_log( $book_id, $event, $context = array() ) {
	$entry = array(
		'timestamp' => gmdate( 'c' ),
		'event'     => sanitize_key( $event ),
		'action'    => isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : current_filter(),
		'trace_id'  => isset( $_REQUEST['credits_debug_trace_id'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['credits_debug_trace_id'] ) ) : '',
		'context'   => is_array( $context ) ? $context : array(),
	);

	$stored_log = get_post_meta( $book_id, '_almaden_credits_debug_log', true );
	if ( is_string( $stored_log ) && '' !== trim( $stored_log ) ) {
		$stored_log = json_decode( $stored_log, true );
	}
	if ( ! is_array( $stored_log ) ) {
		$stored_log = array();
	}

	$stored_log[] = $entry;
	$stored_log = array_slice( $stored_log, -50 );
	update_post_meta( $book_id, '_almaden_credits_debug_log', wp_slash( wp_json_encode( $stored_log, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ) );

	error_log( '[Almaden Credits] ' . wp_json_encode( array( 'book_id' => $book_id ) + $entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
	return $entry;
}

function almaden_bookster_credits_debug_summary( $config ) {
	$config = is_array( $config ) ? $config : array();
	$logos = isset( $config['logos'] ) && is_array( $config['logos'] ) ? $config['logos'] : array();
	$first_logo = ! empty( $logos ) && isset( $logos[0] ) && is_array( $logos[0] ) ? $logos[0] : array();

	return array(
		'people_count'        => isset( $config['people'] ) && is_array( $config['people'] ) ? count( $config['people'] ) : 0,
		'collaborators_count' => isset( $config['collaborators'] ) && is_array( $config['collaborators'] ) ? count( $config['collaborators'] ) : 0,
		'logo_count'          => count( $logos ),
		'vertical_align'      => isset( $config['vertical_align'] ) ? sanitize_text_field( $config['vertical_align'] ) : 'bottom',
		'logo_source'         => isset( $first_logo['logo_source'] ) ? sanitize_text_field( $first_logo['logo_source'] ) : 'image',
		'logo_urls'           => array_values( array_map( static function( $logo ) {
			return isset( $logo['logo_url'] ) ? esc_url_raw( $logo['logo_url'] ) : '';
		}, $logos ) ),
		'logo_show_author_name' => ! empty( $first_logo ) && ! empty( $first_logo['show_author_name'] ) ? true : false,
		'logo_author_font_family' => isset( $first_logo['author_font_family'] ) ? sanitize_text_field( $first_logo['author_font_family'] ) : '',
		'logo_author_font_size' => isset( $first_logo['author_font_size'] ) ? (int) $first_logo['author_font_size'] : 0,
		'logo_author_font_weight' => isset( $first_logo['author_font_weight'] ) ? sanitize_text_field( $first_logo['author_font_weight'] ) : '',
		'logo_author_letter_spacing' => isset( $first_logo['author_letter_spacing'] ) ? (string) $first_logo['author_letter_spacing'] : '',
		'logo_author_gap_px' => isset( $first_logo['author_gap_px'] ) ? (int) $first_logo['author_gap_px'] : 10,
		'logo_author_text_transform' => isset( $first_logo['author_text_transform'] ) ? sanitize_text_field( $first_logo['author_text_transform'] ) : 'none',
		'collaborators_title'   => isset( $config['collaborators_title'] ) ? sanitize_text_field( $config['collaborators_title'] ) : '',
		'section_order'       => isset( $config['section_order'] ) && is_array( $config['section_order'] ) ? array_values( $config['section_order'] ) : array(),
		'section_styles'      => isset( $config['section_styles'] ) && is_array( $config['section_styles'] ) ? array_keys( $config['section_styles'] ) : array(),
		'checksum'            => md5( wp_json_encode( $config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ),
	);
}

function almaden_bookster_store_credits_config( $book_id, $config ) {
	$before_raw = get_post_meta( $book_id, '_almaden_credits_config', true );
	$before = almaden_bookster_normalize_credits_config( $before_raw );
	$normalized = almaden_bookster_normalize_credits_config( $config );
	$legacy = almaden_bookster_credits_config_to_legacy( $normalized );
	$encoded = wp_json_encode( $normalized, JSON_UNESCAPED_UNICODE );

	$write_result = update_post_meta( $book_id, '_almaden_credits_config', wp_slash( $encoded ) );
	update_post_meta( $book_id, '_almaden_credits_edition', $legacy['credits_edition'] );
	update_post_meta( $book_id, '_almaden_credits_date', $legacy['credits_date'] );
	update_post_meta( $book_id, '_almaden_credits_isbn', $legacy['credits_isbn'] );
	update_post_meta( $book_id, '_almaden_credits_copyright', $legacy['credits_copyright'] );
	update_post_meta( $book_id, '_almaden_credits_printer', $legacy['credits_printer'] );
	update_post_meta( $book_id, '_almaden_credits_blank_before', intval( $legacy['credits_blank_before'] ) );
	update_post_meta( $book_id, '_almaden_credits_blank_after', intval( $legacy['credits_blank_after'] ) );
	update_post_meta( $book_id, '_almaden_credits_license', $legacy['credits_license'] );
	update_post_meta( $book_id, '_almaden_credits_custom', wp_slash( $legacy['credits_custom'] ) );
	update_post_meta( $book_id, '_almaden_credits_vertical_align', $legacy['credits_vertical_align'] );

	$stored = almaden_bookster_normalize_credits_config( get_post_meta( $book_id, '_almaden_credits_config', true ) );
	almaden_bookster_credits_debug_log(
		$book_id,
		'store_completed',
		array(
			'before'       => almaden_bookster_credits_debug_summary( $before ),
			'received'     => almaden_bookster_credits_debug_summary( $config ),
			'normalized'   => almaden_bookster_credits_debug_summary( $normalized ),
			'stored'       => almaden_bookster_credits_debug_summary( $stored ),
			'write_result' => $write_result,
			'verified'     => almaden_bookster_credits_debug_summary( $normalized )['checksum'] === almaden_bookster_credits_debug_summary( $stored )['checksum'],
		)
	);

	return $stored;
}

function almaden_bookster_save_credits_from_request( $book_id, $request ) {
	$has_config = isset( $request['credits_config'] );
	$has_legacy = false;
	foreach ( array( 'credits_edition', 'credits_date', 'credits_isbn', 'credits_copyright', 'credits_printer', 'credits_blank_before', 'credits_blank_after', 'credits_license', 'credits_custom', 'credits_vertical_align', 'credits_logo_source', 'credits_logo_url', 'credits_logo_position', 'credits_logo_size_px', 'credits_logo_show_author_name', 'credits_logo_author_font_family', 'credits_logo_author_font_size', 'credits_logo_author_font_weight', 'credits_logo_author_letter_spacing', 'credits_logo_author_gap_px', 'credits_logo_author_text_transform' ) as $field ) {
		if ( isset( $request[ $field ] ) ) {
			$has_legacy = true;
			break;
		}
	}

	if ( ! $has_config && ! $has_legacy ) {
		return null;
	}

	$raw_config = array();
	if ( $has_config ) {
		$decoded_config = almaden_bookster_decode_json_array( $request['credits_config'] );
		if ( is_array( $decoded_config ) ) {
			$raw_config = $decoded_config;
		} elseif ( ! $has_legacy ) {
			almaden_bookster_credits_debug_log( $book_id, 'request_decode_failed', array( 'json_error' => json_last_error_msg() ) );
			return null;
		}
	}

	$legacy_input = array(
		'credits_edition'      => isset( $request['credits_edition'] ) ? sanitize_text_field( wp_unslash( $request['credits_edition'] ) ) : '',
		'credits_date'         => isset( $request['credits_date'] ) ? sanitize_text_field( wp_unslash( $request['credits_date'] ) ) : '',
		'credits_isbn'         => isset( $request['credits_isbn'] ) ? sanitize_text_field( wp_unslash( $request['credits_isbn'] ) ) : '',
		'credits_copyright'    => isset( $request['credits_copyright'] ) ? sanitize_textarea_field( wp_unslash( $request['credits_copyright'] ) ) : '',
		'credits_printer'      => isset( $request['credits_printer'] ) ? sanitize_text_field( wp_unslash( $request['credits_printer'] ) ) : '',
		'credits_blank_before' => isset( $request['credits_blank_before'] ) ? intval( $request['credits_blank_before'] ) : 0,
		'credits_blank_after'  => isset( $request['credits_blank_after'] ) ? intval( $request['credits_blank_after'] ) : 0,
		'credits_license'      => isset( $request['credits_license'] ) ? sanitize_text_field( wp_unslash( $request['credits_license'] ) ) : 'all_rights_reserved',
		'credits_custom'       => isset( $request['credits_custom'] ) ? wp_unslash( $request['credits_custom'] ) : '[]',
		'credits_vertical_align' => isset( $request['credits_vertical_align'] ) ? sanitize_text_field( wp_unslash( $request['credits_vertical_align'] ) ) : '',
		'credits_logo_source'   => isset( $request['credits_logo_source'] ) ? sanitize_text_field( wp_unslash( $request['credits_logo_source'] ) ) : '',
		'credits_logo_url'      => isset( $request['credits_logo_url'] ) ? esc_url_raw( wp_unslash( $request['credits_logo_url'] ) ) : '',
		'credits_logo_position' => isset( $request['credits_logo_position'] ) ? sanitize_text_field( wp_unslash( $request['credits_logo_position'] ) ) : '',
		'credits_logo_size_px'  => isset( $request['credits_logo_size_px'] ) ? intval( $request['credits_logo_size_px'] ) : 0,
		'credits_logo_show_author_name' => isset( $request['credits_logo_show_author_name'] ) ? intval( $request['credits_logo_show_author_name'] ) : 0,
		'credits_logo_author_font_family' => isset( $request['credits_logo_author_font_family'] ) ? sanitize_text_field( wp_unslash( $request['credits_logo_author_font_family'] ) ) : '',
		'credits_logo_author_font_size' => isset( $request['credits_logo_author_font_size'] ) ? intval( $request['credits_logo_author_font_size'] ) : 0,
		'credits_logo_author_font_weight' => isset( $request['credits_logo_author_font_weight'] ) ? sanitize_text_field( wp_unslash( $request['credits_logo_author_font_weight'] ) ) : '',
		'credits_logo_author_letter_spacing' => isset( $request['credits_logo_author_letter_spacing'] ) ? sanitize_text_field( wp_unslash( $request['credits_logo_author_letter_spacing'] ) ) : '',
		'credits_logo_author_gap_px' => isset( $request['credits_logo_author_gap_px'] ) ? intval( $request['credits_logo_author_gap_px'] ) : 10,
		'credits_logo_author_text_transform' => isset( $request['credits_logo_author_text_transform'] ) ? sanitize_text_field( wp_unslash( $request['credits_logo_author_text_transform'] ) ) : '',
		'credits_logo_title_font_family' => isset( $request['credits_logo_title_font_family'] ) ? sanitize_text_field( wp_unslash( $request['credits_logo_title_font_family'] ) ) : '',
		'credits_logo_title_font_size' => isset( $request['credits_logo_title_font_size'] ) ? intval( $request['credits_logo_title_font_size'] ) : 0,
		'credits_logo_title_font_weight' => isset( $request['credits_logo_title_font_weight'] ) ? sanitize_text_field( wp_unslash( $request['credits_logo_title_font_weight'] ) ) : '',
		'credits_logo_title_letter_spacing' => isset( $request['credits_logo_title_letter_spacing'] ) ? sanitize_text_field( wp_unslash( $request['credits_logo_title_letter_spacing'] ) ) : '',
		'credits_logo_title_line_height' => isset( $request['credits_logo_title_line_height'] ) ? sanitize_text_field( wp_unslash( $request['credits_logo_title_line_height'] ) ) : '',
		'credits_logo_title_text_transform' => isset( $request['credits_logo_title_text_transform'] ) ? sanitize_text_field( wp_unslash( $request['credits_logo_title_text_transform'] ) ) : '',
	);

	$config = almaden_bookster_normalize_credits_config( $raw_config, $legacy_input );
	return almaden_bookster_store_credits_config( $book_id, $config );
}

function almaden_bookster_save_credits_config_ajax() {
	$book_id = isset( $_POST['book_id'] ) ? intval( $_POST['book_id'] ) : 0;

	if ( ! $book_id ) {
		wp_send_json_error( 'Falta el identificador del libro.' );
	}

	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'almaden_save_book_nonce_' . $book_id ) ) {
		wp_send_json_error( 'Validación de seguridad fallida.' );
	}

	if ( ! current_user_can( 'edit_post', $book_id ) ) {
		wp_send_json_error( 'No tienes permisos para editar este libro.' );
	}

	if ( ! isset( $_POST['credits_config'] ) ) {
		almaden_bookster_credits_debug_log( $book_id, 'ajax_missing_config' );
		wp_send_json_error( 'No se recibió la configuración estructurada de créditos.' );
	}

	$decoded_config = almaden_bookster_decode_json_array( $_POST['credits_config'] );
	if ( ! is_array( $decoded_config ) ) {
		almaden_bookster_credits_debug_log( $book_id, 'ajax_decode_failed', array( 'json_error' => json_last_error_msg() ) );
		wp_send_json_error( 'La configuración de créditos no es válida.' );
	}

	almaden_bookster_credits_debug_log( $book_id, 'ajax_received', almaden_bookster_credits_debug_summary( $decoded_config ) );
	$config = almaden_bookster_store_credits_config( $book_id, $decoded_config );

	wp_send_json_success(
		array(
			'message'       => 'Configuración de créditos guardada.',
			'credits_config' => $config,
			'credits_legacy' => almaden_bookster_credits_config_to_legacy( $config ),
			'debug'          => array(
				'trace_id' => isset( $_POST['credits_debug_trace_id'] ) ? sanitize_text_field( wp_unslash( $_POST['credits_debug_trace_id'] ) ) : '',
				'stored'   => almaden_bookster_credits_debug_summary( $config ),
			),
		)
	);
}

add_action( 'wp_ajax_almaden_save_credits_config', 'almaden_bookster_save_credits_config_ajax' );
