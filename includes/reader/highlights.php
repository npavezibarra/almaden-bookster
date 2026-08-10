<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once dirname( __FILE__ ) . '/../frontend/access-control.php';

function almaden_bookster_get_user_book_highlights( $book_id, $user_id = null, $chapter_id = null ) {
	global $wpdb;

	$book_id = absint( $book_id );
	$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
	$chapter_id = null === $chapter_id ? null : absint( $chapter_id );

	if ( ! $book_id || ! $user_id ) {
		return array();
	}

	$table_name = almaden_bookster_get_highlights_table_name();
	$table_exists = almaden_bookster_table_exists( $table_name );
	if ( ! $table_exists ) {
		return array();
	}

	if ( null !== $chapter_id && $chapter_id > 0 ) {
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table_name WHERE book_id = %d AND user_id = %d AND chapter_id = %d AND status = %s ORDER BY start_offset ASC, id ASC",
				$book_id,
				$user_id,
				$chapter_id,
				'active'
			),
			ARRAY_A
		);
	} else {
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table_name WHERE book_id = %d AND user_id = %d AND status = %s ORDER BY chapter_id ASC, start_offset ASC, id ASC",
				$book_id,
				$user_id,
				'active'
			),
			ARRAY_A
		);
	}

	return is_array( $rows ) ? $rows : array();
}

function almaden_bookster_highlight_row( $row ) {
	if ( ! is_array( $row ) ) {
		return array();
	}

	return array(
		'id'            => isset( $row['id'] ) ? (int) $row['id'] : 0,
		'book_id'       => isset( $row['book_id'] ) ? (int) $row['book_id'] : 0,
		'chapter_id'    => isset( $row['chapter_id'] ) ? (int) $row['chapter_id'] : 0,
		'user_id'       => isset( $row['user_id'] ) ? (int) $row['user_id'] : 0,
		'selected_text' => isset( $row['selected_text'] ) ? $row['selected_text'] : '',
		'start_offset'  => isset( $row['start_offset'] ) ? (int) $row['start_offset'] : 0,
		'end_offset'    => isset( $row['end_offset'] ) ? (int) $row['end_offset'] : 0,
		'status'        => isset( $row['status'] ) ? $row['status'] : 'active',
		'created_at'    => isset( $row['created_at'] ) ? $row['created_at'] : '',
		'updated_at'    => isset( $row['updated_at'] ) ? $row['updated_at'] : '',
	);
}

function almaden_bookster_save_highlight_ajax() {
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( 'Debes iniciar sesión para guardar un highlight.', 403 );
	}

	$book_id = isset( $_POST['book_id'] ) ? absint( $_POST['book_id'] ) : 0;
	$chapter_id = isset( $_POST['chapter_id'] ) ? absint( $_POST['chapter_id'] ) : 0;

	if ( ! $book_id || ! $chapter_id ) {
		wp_send_json_error( 'Faltan datos del highlight.', 400 );
	}

	$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'almaden_book_highlight_' . $book_id ) ) {
		wp_send_json_error( 'Validación de seguridad fallida.', 403 );
	}

	if ( ! almaden_bookster_user_can_access_book( $book_id ) ) {
		wp_send_json_error( 'No tienes acceso a este libro.', 403 );
	}

	$selected_text = isset( $_POST['selected_text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['selected_text'] ) ) : '';
	$start_offset = isset( $_POST['start_offset'] ) ? absint( $_POST['start_offset'] ) : 0;
	$end_offset = isset( $_POST['end_offset'] ) ? absint( $_POST['end_offset'] ) : 0;

	if ( '' === trim( $selected_text ) || $end_offset <= $start_offset ) {
		wp_send_json_error( 'La selección no es válida.', 400 );
	}

	global $wpdb;
	$table_name = almaden_bookster_get_highlights_table_name();
	$now = current_time( 'mysql' );
	$result = $wpdb->insert(
		$table_name,
		array(
			'book_id'       => $book_id,
			'chapter_id'    => $chapter_id,
			'user_id'       => get_current_user_id(),
			'selected_text' => $selected_text,
			'start_offset'  => $start_offset,
			'end_offset'    => $end_offset,
			'status'        => 'active',
			'created_at'    => $now,
			'updated_at'    => $now,
		),
		array( '%d', '%d', '%d', '%s', '%d', '%d', '%s', '%s', '%s' )
	);

	if ( false === $result ) {
		wp_send_json_error( 'No se pudo guardar el highlight.', 500 );
	}

	$highlight_id = (int) $wpdb->insert_id;
	$highlight = almaden_bookster_highlight_row( $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $highlight_id ), ARRAY_A ) );
	wp_send_json_success( array( 'highlight' => $highlight ) );
}
add_action( 'wp_ajax_almaden_save_book_highlight', 'almaden_bookster_save_highlight_ajax' );

function almaden_bookster_list_highlights_ajax() {
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( 'Debes iniciar sesión para ver highlights.', 403 );
	}

	$book_id = isset( $_GET['book_id'] ) ? absint( $_GET['book_id'] ) : 0;
	$chapter_id = isset( $_GET['chapter_id'] ) ? absint( $_GET['chapter_id'] ) : 0;
	$nonce = isset( $_GET['nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['nonce'] ) ) : '';

	if ( ! $book_id || ! wp_verify_nonce( $nonce, 'almaden_book_highlight_' . $book_id ) ) {
		wp_send_json_error( 'Validación de seguridad fallida.', 403 );
	}

	if ( ! almaden_bookster_user_can_access_book( $book_id ) ) {
		wp_send_json_error( 'No tienes acceso a este libro.', 403 );
	}

	$rows = almaden_bookster_get_user_book_highlights( $book_id, get_current_user_id(), $chapter_id > 0 ? $chapter_id : null );
	$highlights = array_map( 'almaden_bookster_highlight_row', $rows );

	wp_send_json_success( array( 'highlights' => $highlights ) );
}
add_action( 'wp_ajax_almaden_list_book_highlights', 'almaden_bookster_list_highlights_ajax' );

function almaden_bookster_delete_highlight_ajax() {
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( 'Debes iniciar sesión para borrar highlights.', 403 );
	}

	$highlight_id = isset( $_POST['highlight_id'] ) ? absint( $_POST['highlight_id'] ) : 0;
	$book_id = isset( $_POST['book_id'] ) ? absint( $_POST['book_id'] ) : 0;
	$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

	if ( ! $highlight_id || ! $book_id || ! wp_verify_nonce( $nonce, 'almaden_book_highlight_' . $book_id ) ) {
		wp_send_json_error( 'Validación de seguridad fallida.', 403 );
	}

	if ( ! almaden_bookster_user_can_access_book( $book_id ) ) {
		wp_send_json_error( 'No tienes acceso a este libro.', 403 );
	}

	global $wpdb;
	$table_name = almaden_bookster_get_highlights_table_name();
	$highlight = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $highlight_id ), ARRAY_A );
	if ( ! $highlight ) {
		wp_send_json_error( 'Highlight no encontrado.', 404 );
	}

	if ( (int) $highlight['user_id'] !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'No puedes borrar este highlight.', 403 );
	}

	$deleted = $wpdb->delete( $table_name, array( 'id' => $highlight_id ), array( '%d' ) );
	if ( false === $deleted ) {
		wp_send_json_error( 'No se pudo borrar el highlight.', 500 );
	}

	if ( function_exists( 'almaden_bookster_get_highlight_comments_table_name' ) ) {
		$comments_table = almaden_bookster_get_highlight_comments_table_name();
		$wpdb->delete( $comments_table, array( 'highlight_id' => $highlight_id ), array( '%d' ) );
	}

	wp_send_json_success( array( 'highlight_id' => $highlight_id ) );
}
add_action( 'wp_ajax_almaden_delete_book_highlight', 'almaden_bookster_delete_highlight_ajax' );
