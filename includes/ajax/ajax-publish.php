<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// 7. PUBLICAR/DESPUBLICAR LIBRO PARA EL BOOKSHELF
// ==============================================================================
function almaden_toggle_publish_book() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( 'Permisos insuficientes.' );
	}

	$book_id = isset( $_POST['book_id'] ) ? intval( $_POST['book_id'] ) : 0;
	$action = isset( $_POST['publish_action'] ) ? sanitize_text_field( $_POST['publish_action'] ) : 'publish';

	if ( ! $book_id ) {
		wp_send_json_error( 'ID de libro inválido.' );
	}

	if ( $action === 'publish' ) {
		update_post_meta( $book_id, '_almaden_is_published', '1' );
	} else {
		delete_post_meta( $book_id, '_almaden_is_published' );
	}

	wp_send_json_success( 'Estado actualizado correctamente.' );
}
add_action( 'wp_ajax_almaden_toggle_publish_book', 'almaden_toggle_publish_book' );

