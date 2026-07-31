<?php
/**
 * Authenticated binary PDF endpoint for the Typst editor preview.
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once dirname( __DIR__ ) . '/pdf-typst/typst-document.php';
require_once dirname( __DIR__ ) . '/pdf-typst/typst-compiler.php';

add_action( 'wp_ajax_almaden_compile_typst_pdf', 'almaden_bookster_ajax_compile_typst_pdf' );

function almaden_bookster_ajax_compile_typst_pdf() {
	$book_id = isset( $_POST['book_id'] ) ? absint( $_POST['book_id'] ) : 0;
	if ( ! $book_id || ! check_ajax_referer( 'almaden_save_book_nonce_' . $book_id, 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => 'La sesión de edición expiró.' ), 403 );
	}
	if ( ! current_user_can( 'edit_post', $book_id ) ) {
		wp_send_json_error( array( 'message' => 'No tienes permisos para compilar este libro.' ), 403 );
	}

	$json = isset( $_POST['payload'] ) ? wp_unslash( $_POST['payload'] ) : '';
	if ( strlen( $json ) > 8 * MB_IN_BYTES ) {
		wp_send_json_error( array( 'message' => 'El manuscrito excede el límite de compilación.' ), 413 );
	}
	$payload = json_decode( $json, true );
	if ( ! is_array( $payload ) || empty( $payload['chapters'] ) || ! is_array( $payload['chapters'] ) ) {
		wp_send_json_error( array( 'message' => 'El manuscrito enviado no es válido.' ), 400 );
	}

	$cover_settings = get_post_meta( $book_id, '_almaden_cover_settings', true );
	if ( ! is_array( $cover_settings ) && '' === trim( (string) $cover_settings ) ) {
		$cover_settings = array();
	}
	$payload['coverSettings'] = $cover_settings;
	$payload['cover_settings'] = $cover_settings;

	$payload['title'] = isset( $payload['title'] ) ? sanitize_text_field( $payload['title'] ) : '';
	$payload['chapters'] = array_slice( $payload['chapters'], 0, 500 );
	foreach ( $payload['chapters'] as &$chapter ) {
		if ( ! is_array( $chapter ) ) {
			$chapter = array();
			continue;
		}
		$chapter['title']   = isset( $chapter['title'] ) ? sanitize_text_field( $chapter['title'] ) : '';
		$chapter['content'] = isset( $chapter['content'] )
			? str_replace( "\0", '', substr( (string) $chapter['content'], 0, 2 * MB_IN_BYTES ) )
			: '';
	}
	unset( $chapter );

	$document = almaden_bookster_build_typst_document( $payload );
	$pdf      = almaden_bookster_compile_typst_pdf( $document );
	if ( is_wp_error( $pdf ) ) {
		wp_send_json_error(
			array(
				'message' => $pdf->get_error_message(),
				'code'    => $pdf->get_error_code(),
			),
			500
		);
	}

	nocache_headers();
	header( 'Content-Type: application/pdf' );
	header( 'Content-Disposition: inline; filename="almaden-book-' . $book_id . '.pdf"' );
	header( 'Content-Length: ' . strlen( $pdf ) );
	header( 'X-Almaden-Source-Hash: ' . $document['source_hash'] );
	header( 'X-Almaden-PDF-Geometry: ' . rawurlencode( wp_json_encode( $document['geometry'] ) ) );
	header( 'X-Almaden-PDF-Typography: ' . rawurlencode( wp_json_encode( $document['typography'] ) ) );
	if ( ! empty( $GLOBALS['almaden_bookster_typst_integrity_warning'] ) ) {
		header( 'X-Almaden-PDF-Integrity: ' . rawurlencode( wp_json_encode( array(
			'status'  => 'warning',
			'message' => (string) $GLOBALS['almaden_bookster_typst_integrity_warning'],
		) ) ) );
	}
	echo $pdf;
	exit;
}
