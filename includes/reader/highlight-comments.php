<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function almaden_bookster_get_user_book_highlight_comments( $highlight_id, $book_id, $chapter_id = null ) {
	global $wpdb;

	$highlight_id = absint( $highlight_id );
	$book_id = absint( $book_id );
	$chapter_id = null === $chapter_id ? null : absint( $chapter_id );

	if ( ! $highlight_id || ! $book_id ) {
		return array();
	}

	$table_name = almaden_bookster_get_highlight_comments_table_name();
	$table_exists = almaden_bookster_table_exists( $table_name );
	if ( ! $table_exists ) {
		return array();
	}

	if ( null !== $chapter_id && $chapter_id > 0 ) {
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table_name WHERE highlight_id = %d AND book_id = %d AND chapter_id = %d AND status = %s ORDER BY created_at ASC, id ASC",
				$highlight_id,
				$book_id,
				$chapter_id,
				'active'
			),
			ARRAY_A
		);
	} else {
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table_name WHERE highlight_id = %d AND book_id = %d AND status = %s ORDER BY created_at ASC, id ASC",
				$highlight_id,
				$book_id,
				'active'
			),
			ARRAY_A
		);
	}

	return is_array( $rows ) ? $rows : array();
}

function almaden_bookster_highlight_comment_row( $row ) {
	if ( ! is_array( $row ) ) {
		return array();
	}

	$user = ! empty( $row['user_id'] ) ? get_user_by( 'id', (int) $row['user_id'] ) : false;

	return array(
		'id'           => isset( $row['id'] ) ? (int) $row['id'] : 0,
		'highlight_id'  => isset( $row['highlight_id'] ) ? (int) $row['highlight_id'] : 0,
		'book_id'      => isset( $row['book_id'] ) ? (int) $row['book_id'] : 0,
		'chapter_id'   => isset( $row['chapter_id'] ) ? (int) $row['chapter_id'] : 0,
		'user_id'      => isset( $row['user_id'] ) ? (int) $row['user_id'] : 0,
		'user_name'    => $user ? $user->display_name : __( 'Usuario', 'almaden-bookster' ),
		'comment_text' => isset( $row['comment_text'] ) ? $row['comment_text'] : '',
		'status'       => isset( $row['status'] ) ? $row['status'] : 'active',
		'created_at'   => isset( $row['created_at'] ) ? $row['created_at'] : '',
		'updated_at'   => isset( $row['updated_at'] ) ? $row['updated_at'] : '',
		'can_delete'   => isset( $row['user_id'] ) ? ( get_current_user_id() === (int) $row['user_id'] || current_user_can( 'manage_options' ) ) : false,
		'can_edit'     => isset( $row['user_id'] ) ? ( get_current_user_id() === (int) $row['user_id'] || current_user_can( 'manage_options' ) ) : false,
	);
}

function almaden_bookster_save_highlight_comment_ajax() {
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( 'Debes iniciar sesión para comentar.', 403 );
	}

	$highlight_id = isset( $_POST['highlight_id'] ) ? absint( $_POST['highlight_id'] ) : 0;
	$book_id = isset( $_POST['book_id'] ) ? absint( $_POST['book_id'] ) : 0;
	$chapter_id = isset( $_POST['chapter_id'] ) ? absint( $_POST['chapter_id'] ) : 0;

	if ( ! $highlight_id || ! $book_id || ! $chapter_id ) {
		wp_send_json_error( 'Faltan datos del comentario.', 400 );
	}

	$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'almaden_book_highlight_' . $book_id ) ) {
		wp_send_json_error( 'Validación de seguridad fallida.', 403 );
	}

	if ( ! almaden_bookster_user_can_access_book( $book_id ) ) {
		wp_send_json_error( 'No tienes acceso a este libro.', 403 );
	}

	global $wpdb;
	$highlights_table = almaden_bookster_get_highlights_table_name();
	$highlight = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $highlights_table WHERE id = %d AND book_id = %d", $highlight_id, $book_id ), ARRAY_A );
	if ( ! $highlight ) {
		wp_send_json_error( 'Highlight no encontrado.', 404 );
	}

	$comment_text = isset( $_POST['comment_text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['comment_text'] ) ) : '';
	if ( '' === trim( $comment_text ) ) {
		wp_send_json_error( 'Escribe un comentario.', 400 );
	}

	$table_name = almaden_bookster_get_highlight_comments_table_name();
	$now = current_time( 'mysql' );
	$comment_id = isset( $_POST['comment_id'] ) ? absint( $_POST['comment_id'] ) : 0;

	if ( $comment_id ) {
		$existing_comment = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d AND book_id = %d", $comment_id, $book_id ), ARRAY_A );
		if ( ! $existing_comment ) {
			wp_send_json_error( 'Comentario no encontrado.', 404 );
		}

		if ( (int) $existing_comment['user_id'] !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'No puedes editar este comentario.', 403 );
		}

		$result = $wpdb->update(
			$table_name,
			array(
				'comment_text' => $comment_text,
				'updated_at'   => $now,
			),
			array(
				'id' => $comment_id,
			),
			array( '%s', '%s' ),
			array( '%d' )
		);

		if ( false === $result ) {
			wp_send_json_error( 'No se pudo guardar el comentario.', 500 );
		}
	} else {
		$result = $wpdb->insert(
			$table_name,
			array(
				'highlight_id' => $highlight_id,
				'book_id'      => $book_id,
				'chapter_id'   => $chapter_id,
				'user_id'      => get_current_user_id(),
				'comment_text' => $comment_text,
				'status'       => 'active',
				'created_at'   => $now,
				'updated_at'   => $now,
			),
			array( '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s' )
		);

		if ( false === $result ) {
			wp_send_json_error( 'No se pudo guardar el comentario.', 500 );
		}
		$comment_id = (int) $wpdb->insert_id;
	}

	$comment = almaden_bookster_highlight_comment_row( $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $comment_id ), ARRAY_A ) );
	wp_send_json_success( array( 'comment' => $comment ) );
}
add_action( 'wp_ajax_almaden_save_book_highlight_comment', 'almaden_bookster_save_highlight_comment_ajax' );

function almaden_bookster_list_highlight_comments_ajax() {
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( 'Debes iniciar sesión para ver comentarios.', 403 );
	}

	$highlight_id = isset( $_GET['highlight_id'] ) ? absint( $_GET['highlight_id'] ) : 0;
	$book_id = isset( $_GET['book_id'] ) ? absint( $_GET['book_id'] ) : 0;
	$chapter_id = isset( $_GET['chapter_id'] ) ? absint( $_GET['chapter_id'] ) : 0;
	$nonce = isset( $_GET['nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['nonce'] ) ) : '';

	if ( ! $highlight_id || ! $book_id || ! wp_verify_nonce( $nonce, 'almaden_book_highlight_' . $book_id ) ) {
		wp_send_json_error( 'Validación de seguridad fallida.', 403 );
	}

	if ( ! almaden_bookster_user_can_access_book( $book_id ) ) {
		wp_send_json_error( 'No tienes acceso a este libro.', 403 );
	}

	$rows = almaden_bookster_get_user_book_highlight_comments( $highlight_id, $book_id, $chapter_id > 0 ? $chapter_id : null );
	$comments = array_map( 'almaden_bookster_highlight_comment_row', $rows );

	wp_send_json_success( array( 'comments' => $comments ) );
}
add_action( 'wp_ajax_almaden_list_book_highlight_comments', 'almaden_bookster_list_highlight_comments_ajax' );

/**
 * Return every active highlight in a book together with its comments.
 *
 * The expanded highlights view needs the complete stream, so loading comments
 * one highlight at a time would create an avoidable N+1 request pattern.
 */
function almaden_bookster_list_highlights_feed_ajax() {
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( 'Debes iniciar sesión para ver highlights.', 403 );
	}

	$book_id = isset( $_GET['book_id'] ) ? absint( $_GET['book_id'] ) : 0;
	$nonce = isset( $_GET['nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['nonce'] ) ) : '';

	if ( ! $book_id || ! wp_verify_nonce( $nonce, 'almaden_book_highlight_' . $book_id ) ) {
		wp_send_json_error( 'Validación de seguridad fallida.', 403 );
	}

	if ( ! almaden_bookster_user_can_access_book( $book_id ) ) {
		wp_send_json_error( 'No tienes acceso a este libro.', 403 );
	}

	$highlight_rows = almaden_bookster_get_user_book_highlights( $book_id, get_current_user_id() );
	$highlights = array_map( 'almaden_bookster_highlight_row', $highlight_rows );
	$comments_by_highlight = array();

	foreach ( $highlights as $highlight ) {
		$comments_by_highlight[ (string) $highlight['id'] ] = array();
	}

	$highlight_ids = array_values( array_filter( array_map( 'absint', wp_list_pluck( $highlights, 'id' ) ) ) );
	if ( ! empty( $highlight_ids ) ) {
		global $wpdb;
		$comments_table = almaden_bookster_get_highlight_comments_table_name();

		if ( almaden_bookster_table_exists( $comments_table ) ) {
			$id_placeholders = implode( ', ', array_fill( 0, count( $highlight_ids ), '%d' ) );
			$query_args = array_merge( array( $book_id ), $highlight_ids, array( 'active' ) );
			$query = "SELECT * FROM $comments_table WHERE book_id = %d AND highlight_id IN ($id_placeholders) AND status = %s ORDER BY created_at ASC, id ASC";
			$comment_rows = $wpdb->get_results( $wpdb->prepare( $query, $query_args ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			foreach ( is_array( $comment_rows ) ? $comment_rows : array() as $comment_row ) {
				$comment = almaden_bookster_highlight_comment_row( $comment_row );
				$key = (string) $comment['highlight_id'];
				if ( isset( $comments_by_highlight[ $key ] ) ) {
					$comments_by_highlight[ $key ][] = $comment;
				}
			}
		}
	}

	wp_send_json_success(
		array(
			'highlights'            => $highlights,
			'comments_by_highlight' => (object) $comments_by_highlight,
		)
	);
}
add_action( 'wp_ajax_almaden_list_book_highlights_feed', 'almaden_bookster_list_highlights_feed_ajax' );

function almaden_bookster_delete_highlight_comment_ajax() {
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( 'Debes iniciar sesión para borrar comentarios.', 403 );
	}

	$comment_id = isset( $_POST['comment_id'] ) ? absint( $_POST['comment_id'] ) : 0;
	$book_id = isset( $_POST['book_id'] ) ? absint( $_POST['book_id'] ) : 0;
	$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

	if ( ! $comment_id || ! $book_id || ! wp_verify_nonce( $nonce, 'almaden_book_highlight_' . $book_id ) ) {
		wp_send_json_error( 'Validación de seguridad fallida.', 403 );
	}

	if ( ! almaden_bookster_user_can_access_book( $book_id ) ) {
		wp_send_json_error( 'No tienes acceso a este libro.', 403 );
	}

	global $wpdb;
	$table_name = almaden_bookster_get_highlight_comments_table_name();
	$comment = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $comment_id ), ARRAY_A );
	if ( ! $comment ) {
		wp_send_json_error( 'Comentario no encontrado.', 404 );
	}

	if ( (int) $comment['user_id'] !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'No puedes borrar este comentario.', 403 );
	}

	$deleted = $wpdb->delete( $table_name, array( 'id' => $comment_id ), array( '%d' ) );
	if ( false === $deleted ) {
		wp_send_json_error( 'No se pudo borrar el comentario.', 500 );
	}

	wp_send_json_success( array( 'comment_id' => $comment_id ) );
}
add_action( 'wp_ajax_almaden_delete_book_highlight_comment', 'almaden_bookster_delete_highlight_comment_ajax' );
