<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function almaden_bookster_get_book_media_picker_attachment_payload( $attachment_id ) {
	$attachment_id = absint( $attachment_id );
	if ( $attachment_id <= 0 ) {
		return array();
	}

	$attachment = get_post( $attachment_id );
	if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
		return array();
	}

	$original_url = function_exists( 'almaden_bookster_get_book_image_original_url_from_attachment' )
		? almaden_bookster_get_book_image_original_url_from_attachment( $attachment_id )
		: wp_get_attachment_url( $attachment_id );
	$preview_url = '';
	if ( function_exists( 'almaden_bookster_get_book_image_preview_path' ) && function_exists( 'almaden_bookster_get_book_image_preview_url_from_path' ) ) {
		$preview_path = almaden_bookster_get_book_image_preview_path( $attachment_id );
		if ( $preview_path && file_exists( $preview_path ) ) {
			$preview_url = almaden_bookster_get_book_image_preview_url_from_path( $preview_path );
		}
	}

	$metadata = wp_get_attachment_metadata( $attachment_id );
	$file_path = get_attached_file( $attachment_id );

	// The picker only needs a compact payload. wp_prepare_attachment_for_js()
	// loads every generated size and can exhaust memory for image-heavy books.
	return array(
		'id'           => $attachment_id,
		'attachmentId' => $attachment_id,
		'title'        => get_the_title( $attachment_id ),
		'alt'          => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
		'filename'     => $file_path ? wp_basename( $file_path ) : '',
		'url'          => wp_get_attachment_url( $attachment_id ),
		'originalUrl'  => $original_url,
		'previewUrl'   => $preview_url,
		'previewSafe'  => ! empty( $preview_url ),
		'width'        => is_array( $metadata ) ? absint( $metadata['width'] ?? 0 ) : 0,
		'height'       => is_array( $metadata ) ? absint( $metadata['height'] ?? 0 ) : 0,
		'bookMedia'    => true,
	);
}

function almaden_bookster_get_book_media_picker_attachment_ids( $book_id ) {
	global $wpdb;

	$book_id = absint( $book_id );
	if ( $book_id <= 0 ) {
		return array();
	}

	$query = $wpdb->prepare(
		"SELECT DISTINCT attachment.ID
		FROM {$wpdb->posts} AS attachment
		INNER JOIN {$wpdb->postmeta} AS media_scope
			ON media_scope.post_id = attachment.ID
			AND media_scope.meta_key = %s
		LEFT JOIN {$wpdb->posts} AS chapter
			ON chapter.ID = attachment.post_parent
			AND chapter.post_type = %s
		WHERE attachment.post_type = %s
			AND attachment.post_status = %s
			AND attachment.post_mime_type LIKE %s
			AND (attachment.post_parent = %d OR chapter.post_parent = %d)
		ORDER BY attachment.ID DESC",
		'_almaden_book_media_subdir',
		'book_chapter',
		'attachment',
		'inherit',
		'image/%',
		$book_id,
		$book_id
	);
	$attachment_ids = $wpdb->get_col( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	$attachment_ids = array_values( array_unique( array_filter( array_map( 'absint', $attachment_ids ) ) ) );
	return $attachment_ids;
}

function almaden_bookster_handle_list_book_media() {
	$book_id = isset( $_POST['book_id'] ) ? absint( $_POST['book_id'] ) : 0;
	if ( $book_id <= 0 || 'almaden-books' !== get_post_type( $book_id ) ) {
		wp_send_json_error( array( 'message' => 'Libro no válido.' ), 400 );
	}

	if ( ! function_exists( 'almaden_bookster_user_can_manage_book' ) || ! almaden_bookster_user_can_manage_book( $book_id ) ) {
		wp_send_json_error( array( 'message' => 'Permisos insuficientes.' ), 403 );
	}

	$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
	$valid_nonce = wp_verify_nonce( $nonce, 'almaden_bookster_media_picker_' . $book_id );
	if ( ! $valid_nonce ) {
		wp_send_json_error( array( 'message' => 'Validación de seguridad fallida.' ), 403 );
	}

	$attachments = array();
	foreach ( almaden_bookster_get_book_media_picker_attachment_ids( $book_id ) as $attachment_id ) {
		$payload = almaden_bookster_get_book_media_picker_attachment_payload( $attachment_id );
		if ( ! empty( $payload ) ) {
			$attachments[] = $payload;
		}
	}

	wp_send_json_success(
		array(
			'bookId'      => $book_id,
			'folder'      => function_exists( 'almaden_bookster_get_book_media_subdir' ) ? almaden_bookster_get_book_media_subdir( $book_id ) : '',
			'attachments' => $attachments,
		)
	);
}
add_action( 'wp_ajax_almaden_bookster_book_media_list', 'almaden_bookster_handle_list_book_media' );

function almaden_bookster_handle_upload_book_media() {
	$book_id = isset( $_POST['book_id'] ) ? absint( $_POST['book_id'] ) : 0;
	if ( $book_id <= 0 || 'almaden-books' !== get_post_type( $book_id ) ) {
		wp_send_json_error( array( 'message' => 'Libro no válido.' ), 400 );
	}

	if ( ! function_exists( 'almaden_bookster_user_can_manage_book' ) || ! almaden_bookster_user_can_manage_book( $book_id ) ) {
		wp_send_json_error( array( 'message' => 'Permisos insuficientes.' ), 403 );
	}

	$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'almaden_bookster_media_picker_' . $book_id ) ) {
		wp_send_json_error( array( 'message' => 'Validación de seguridad fallida.' ), 403 );
	}

	if ( empty( $_FILES['file'] ) ) {
		wp_send_json_error( array( 'message' => 'No se recibió archivo.' ), 400 );
	}

	if ( ! function_exists( 'media_handle_upload' ) ) {
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
	}

	$_REQUEST['book_id'] = $book_id;
	$_REQUEST['post_id'] = $book_id;

	$attachment_id = media_handle_upload( 'file', $book_id );
	if ( is_wp_error( $attachment_id ) ) {
		wp_send_json_error( array( 'message' => $attachment_id->get_error_message() ), 400 );
	}

	$subdir = function_exists( 'almaden_bookster_get_book_media_subdir' ) ? almaden_bookster_get_book_media_subdir( $book_id ) : '';
	if ( '' !== $subdir ) {
		update_post_meta( $attachment_id, '_almaden_book_media_subdir', $subdir );
	}
	wp_update_post(
		array(
			'ID'          => $attachment_id,
			'post_parent' => $book_id,
		)
	);

	$payload = almaden_bookster_get_book_media_picker_attachment_payload( $attachment_id );
	wp_send_json_success( $payload );
}
add_action( 'wp_ajax_almaden_bookster_book_media_upload', 'almaden_bookster_handle_upload_book_media' );
