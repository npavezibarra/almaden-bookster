<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

add_action( 'wp_ajax_almaden_analyze_document_import', 'almaden_bookster_ajax_analyze_document_import' );
add_action( 'wp_ajax_almaden_import_document', 'almaden_bookster_ajax_import_document' );

function almaden_bookster_ajax_analyze_document_import() {
	$book_id = isset( $_POST['book_id'] ) ? intval( $_POST['book_id'] ) : 0;
	if ( ! current_user_can( 'edit_post', $book_id ) ) {
		wp_send_json_error( 'No tienes permisos para importar documentos en este libro.' );
	}

	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'almaden_document_import_nonce_' . $book_id ) ) {
		wp_send_json_error( 'Validación de seguridad fallida.' );
	}

	$result = almaden_bookster_parse_uploaded_document_file( $book_id, 'document_file' );
	if ( is_wp_error( $result ) ) {
		wp_send_json_error( $result->get_error_message() );
	}

	wp_send_json_success( $result );
}

function almaden_bookster_ajax_import_document() {
	$book_id = isset( $_POST['book_id'] ) ? intval( $_POST['book_id'] ) : 0;
	if ( ! current_user_can( 'edit_post', $book_id ) ) {
		wp_send_json_error( 'No tienes permisos para importar documentos en este libro.' );
	}

	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'almaden_document_import_nonce_' . $book_id ) ) {
		wp_send_json_error( 'Validación de seguridad fallida.' );
	}

	$separator = isset( $_POST['chapter_separator'] ) ? sanitize_text_field( wp_unslash( $_POST['chapter_separator'] ) ) : '';
	if ( empty( $separator ) ) {
		wp_send_json_error( 'Debes elegir un separador de capítulo.' );
	}

	$parsed = almaden_bookster_parse_uploaded_document_file( $book_id, 'document_file' );
	if ( is_wp_error( $parsed ) ) {
		wp_send_json_error( $parsed->get_error_message() );
	}

	$mapping = isset( $_POST['import_mapping'] ) ? json_decode( wp_unslash( $_POST['import_mapping'] ), true ) : array();
	if ( ! is_array( $mapping ) ) {
		$mapping = array();
	}
	$mapping['chapter_separator'] = $separator;
	$available_styles = isset( $parsed['separator_options'] ) && is_array( $parsed['separator_options'] ) ? $parsed['separator_options'] : almaden_bookster_build_separator_candidates( $parsed['blocks'] );
	$normalized_mapping = almaden_bookster_normalize_import_mapping( $mapping, $available_styles );
	$validation = almaden_bookster_validate_import_mapping( $normalized_mapping, $available_styles );
	if ( ! empty( $validation['errors'] ) ) {
		wp_send_json_error( implode( ' ', $validation['errors'] ) );
	}

	$import = almaden_bookster_build_chapters_from_parsed_document( $book_id, $parsed, $normalized_mapping );
	if ( is_wp_error( $import ) ) {
		wp_send_json_error( $import->get_error_message() );
	}

	wp_send_json_success( array(
		'message'  => sprintf( '%d capítulos importados con éxito.', intval( $import['chapter_count'] ) ),
		'chapters' => $import['chapters'],
		'warnings' => isset( $import['warnings'] ) ? $import['warnings'] : array(),
	) );
}
