<?php
/**
 * AJAX endpoints for book sharing and user search.
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Searches users for the share book autocomplete dropdown.
 */
function almaden_bookster_ajax_search_users_for_share() {
	check_ajax_referer( 'almaden_share_book_nonce', 'nonce' );

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => __( 'No has iniciado sesión.', 'almaden-bookster' ) ), 403 );
	}

	$book_id = isset( $_POST['book_id'] ) ? absint( $_POST['book_id'] ) : 0;
	$term    = isset( $_POST['term'] ) ? sanitize_text_field( wp_unslash( $_POST['term'] ) ) : '';

	if ( $book_id <= 0 ) {
		wp_send_json_error( array( 'message' => __( 'ID de libro no válido.', 'almaden-bookster' ) ), 400 );
	}

	if ( function_exists( 'almaden_bookster_user_can_manage_book' ) && ! almaden_bookster_user_can_manage_book( $book_id ) ) {
		wp_send_json_error( array( 'message' => __( 'No tienes permisos para compartir este libro.', 'almaden-bookster' ) ), 403 );
	}

	$term = trim( $term );
	if ( '' === $term ) {
		wp_send_json_success( array( 'users' => array() ) );
	}

	$current_user_id = get_current_user_id();
	$book            = get_post( $book_id );
	$owner_id        = $book ? absint( $book->post_author ) : 0;

	// Exclude current user, post author, and already shared users
	$exclude_ids = array( $current_user_id );
	if ( $owner_id > 0 ) {
		$exclude_ids[] = $owner_id;
	}

	if ( function_exists( 'almaden_bookster_get_book_shares' ) ) {
		$existing_shares = almaden_bookster_get_book_shares( $book_id );
		foreach ( $existing_shares as $share ) {
			$exclude_ids[] = absint( $share['user_id'] );
		}
	}
	$exclude_ids = array_values( array_unique( array_filter( $exclude_ids ) ) );

	$user_query_args = array(
		'number'         => 10,
		'search'         => '*' . $term . '*',
		'search_columns' => array( 'display_name', 'user_login', 'user_nicename', 'user_email' ),
		'exclude'        => $exclude_ids,
		'orderby'        => 'display_name',
		'order'          => 'ASC',
		'fields'         => 'all',
	);

	$user_query = new WP_User_Query( $user_query_args );
	$users      = $user_query->get_results();

	$results = array();
	if ( ! empty( $users ) ) {
		foreach ( $users as $user ) {
			$name = ! empty( $user->display_name ) ? $user->display_name : $user->user_login;
			$results[] = array(
				'id'         => (int) $user->ID,
				'name'       => $name,
				'login'      => $user->user_login,
				'email'      => $user->user_email,
				'avatar'     => get_avatar_url( (int) $user->ID, array( 'size' => 48 ) ),
			);
		}
	}

	wp_send_json_success( array( 'users' => $results ) );
}
add_action( 'wp_ajax_almaden_search_users_for_share', 'almaden_bookster_ajax_search_users_for_share' );

/**
 * Shares a book with a target user.
 */
function almaden_bookster_ajax_share_book() {
	check_ajax_referer( 'almaden_share_book_nonce', 'nonce' );

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => __( 'No has iniciado sesión.', 'almaden-bookster' ) ), 403 );
	}

	$book_id        = isset( $_POST['book_id'] ) ? absint( $_POST['book_id'] ) : 0;
	$target_user_id = isset( $_POST['target_user_id'] ) ? absint( $_POST['target_user_id'] ) : 0;

	if ( $book_id <= 0 ) {
		wp_send_json_error( array( 'message' => __( 'ID de libro no válido.', 'almaden-bookster' ) ), 400 );
	}

	// If no user ID is provided, try finding user by email or username token
	if ( $target_user_id <= 0 && ! empty( $_POST['user_token'] ) ) {
		$token = sanitize_text_field( wp_unslash( $_POST['user_token'] ) );
		if ( function_exists( 'almaden_bookster_find_user_for_book_author_token' ) ) {
			$found = almaden_bookster_find_user_for_book_author_token( $token );
			if ( $found ) {
				$target_user_id = absint( $found->ID );
			}
		}
	}

	if ( $target_user_id <= 0 ) {
		wp_send_json_error( array( 'message' => __( 'Por favor selecciona un usuario válido para compartir.', 'almaden-bookster' ) ), 400 );
	}

	if ( function_exists( 'almaden_bookster_user_can_manage_book' ) && ! almaden_bookster_user_can_manage_book( $book_id ) ) {
		wp_send_json_error( array( 'message' => __( 'No tienes permisos para compartir este libro.', 'almaden-bookster' ) ), 403 );
	}

	$result = almaden_bookster_share_book_with_user( $book_id, $target_user_id, get_current_user_id() );

	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
	}

	if ( ! $result ) {
		wp_send_json_error( array( 'message' => __( 'Ocurrió un error al intentar compartir el libro.', 'almaden-bookster' ) ), 500 );
	}

	$shares = almaden_bookster_get_book_shares( $book_id );

	wp_send_json_success(
		array(
			'message' => __( 'Libro compartido exitosamente.', 'almaden-bookster' ),
			'shares'  => $shares,
		)
	);
}
add_action( 'wp_ajax_almaden_share_book', 'almaden_bookster_ajax_share_book' );

/**
 * Removes a shared user from a book.
 */
function almaden_bookster_ajax_unshare_book() {
	check_ajax_referer( 'almaden_share_book_nonce', 'nonce' );

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => __( 'No has iniciado sesión.', 'almaden-bookster' ) ), 403 );
	}

	$book_id        = isset( $_POST['book_id'] ) ? absint( $_POST['book_id'] ) : 0;
	$target_user_id = isset( $_POST['target_user_id'] ) ? absint( $_POST['target_user_id'] ) : 0;

	if ( $book_id <= 0 || $target_user_id <= 0 ) {
		wp_send_json_error( array( 'message' => __( 'Parámetros inválidos.', 'almaden-bookster' ) ), 400 );
	}

	if ( function_exists( 'almaden_bookster_user_can_manage_book' ) && ! almaden_bookster_user_can_manage_book( $book_id ) ) {
		wp_send_json_error( array( 'message' => __( 'No tienes permisos para modificar este libro.', 'almaden-bookster' ) ), 403 );
	}

	$deleted = almaden_bookster_unshare_book_with_user( $book_id, $target_user_id );
	if ( ! $deleted ) {
		wp_send_json_error( array( 'message' => __( 'No se pudo desvincular al usuario.', 'almaden-bookster' ) ), 500 );
	}

	$shares = almaden_bookster_get_book_shares( $book_id );

	wp_send_json_success(
		array(
			'message' => __( 'Acceso revocado exitosamente.', 'almaden-bookster' ),
			'shares'  => $shares,
		)
	);
}
add_action( 'wp_ajax_almaden_unshare_book', 'almaden_bookster_ajax_unshare_book' );

/**
 * Fetches the current list of shared users for a book.
 */
function almaden_bookster_ajax_get_book_shares() {
	check_ajax_referer( 'almaden_share_book_nonce', 'nonce' );

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => __( 'No has iniciado sesión.', 'almaden-bookster' ) ), 403 );
	}

	$book_id = isset( $_POST['book_id'] ) ? absint( $_POST['book_id'] ) : 0;
	if ( $book_id <= 0 ) {
		wp_send_json_error( array( 'message' => __( 'ID de libro no válido.', 'almaden-bookster' ) ), 400 );
	}

	if ( function_exists( 'almaden_bookster_user_can_manage_book' ) && ! almaden_bookster_user_can_manage_book( $book_id ) ) {
		wp_send_json_error( array( 'message' => __( 'No tienes permisos para ver este libro.', 'almaden-bookster' ) ), 403 );
	}

	$shares = almaden_bookster_get_book_shares( $book_id );

	wp_send_json_success( array( 'shares' => $shares ) );
}
add_action( 'wp_ajax_almaden_get_book_shares', 'almaden_bookster_ajax_get_book_shares' );
