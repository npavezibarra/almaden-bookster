<?php
/**
 * Book sharing functions and database helpers for Almaden Bookster.
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns the table name for book shares.
 *
 * @return string
 */
function almaden_bookster_get_book_shares_table_name(): string {
	global $wpdb;
	return $wpdb->prefix . 'almaden_book_shares';
}

/**
 * Creates the book shares table in the database if it doesn't exist.
 *
 * @return void
 */
function almaden_bookster_create_book_shares_table(): void {
	global $wpdb;

	$table_name        = almaden_bookster_get_book_shares_table_name();
	$db_version_option = 'almaden_bookster_book_shares_db_version';
	$schema_version    = '1.0.0';
	$charset_collate   = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		book_id bigint(20) unsigned NOT NULL,
		user_id bigint(20) unsigned NOT NULL,
		shared_by bigint(20) unsigned NOT NULL,
		permission varchar(50) DEFAULT 'edit' NOT NULL,
		created_at datetime NOT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY book_user (book_id, user_id),
		KEY book_id (book_id),
		KEY user_id (user_id),
		KEY shared_by (shared_by)
	) $charset_collate;";

	almaden_bookster_maybe_install_table(
		$table_name,
		$sql,
		$db_version_option,
		$schema_version
	);
}

/**
 * Shares a book with a target user.
 *
 * @param int    $book_id        The ID of the book.
 * @param int    $target_user_id The ID of the user receiving access.
 * @param int    $shared_by      The ID of the user sharing the book.
 * @param string $permission     The permission level ('edit' by default).
 * @return bool|WP_Error
 */
function almaden_bookster_share_book_with_user( int $book_id, int $target_user_id, int $shared_by = 0, string $permission = 'edit' ) {
	global $wpdb;

	$book_id        = absint( $book_id );
	$target_user_id = absint( $target_user_id );
	$shared_by      = $shared_by > 0 ? absint( $shared_by ) : get_current_user_id();
	$permission     = sanitize_key( $permission );

	if ( $book_id <= 0 || $target_user_id <= 0 ) {
		return new WP_Error( 'invalid_data', __( 'Parámetros de libro o usuario inválidos.', 'almaden-bookster' ) );
	}

	$book = get_post( $book_id );
	if ( ! $book || 'almaden-books' !== $book->post_type ) {
		return new WP_Error( 'book_not_found', __( 'El libro especificado no existe.', 'almaden-bookster' ) );
	}

	if ( absint( $book->post_author ) === $target_user_id ) {
		return new WP_Error( 'already_owner', __( 'El usuario ya es el autor propietario de este libro.', 'almaden-bookster' ) );
	}

	$target_user = get_user_by( 'id', $target_user_id );
	if ( ! $target_user ) {
		return new WP_Error( 'user_not_found', __( 'El usuario destino no existe.', 'almaden-bookster' ) );
	}

	$table_name = almaden_bookster_get_book_shares_table_name();

	$existing = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT id FROM $table_name WHERE book_id = %d AND user_id = %d LIMIT 1",
			$book_id,
			$target_user_id
		)
	);

	if ( $existing ) {
		$wpdb->update(
			$table_name,
			array(
				'permission' => $permission,
				'shared_by'  => $shared_by,
			),
			array( 'id' => absint( $existing ) ),
			array( '%s', '%d' ),
			array( '%d' )
		);
		return true;
	}

	$result = $wpdb->insert(
		$table_name,
		array(
			'book_id'    => $book_id,
			'user_id'    => $target_user_id,
			'shared_by'  => $shared_by,
			'permission' => $permission,
			'created_at' => current_time( 'mysql' ),
		),
		array( '%d', '%d', '%d', '%s', '%s' )
	);

	return false !== $result;
}

/**
 * Removes a sharing relationship for a book and user.
 *
 * @param int $book_id        The ID of the book.
 * @param int $target_user_id The ID of the user.
 * @return bool
 */
function almaden_bookster_unshare_book_with_user( int $book_id, int $target_user_id ): bool {
	global $wpdb;

	$book_id        = absint( $book_id );
	$target_user_id = absint( $target_user_id );

	if ( $book_id <= 0 || $target_user_id <= 0 ) {
		return false;
	}

	$table_name = almaden_bookster_get_book_shares_table_name();

	$deleted = $wpdb->delete(
		$table_name,
		array(
			'book_id' => $book_id,
			'user_id' => $target_user_id,
		),
		array( '%d', '%d' )
	);

	return false !== $deleted;
}

/**
 * Retrieves all users with whom a book has been shared.
 *
 * @param int $book_id The ID of the book.
 * @return array
 */
function almaden_bookster_get_book_shares( int $book_id ): array {
	global $wpdb;

	$book_id = absint( $book_id );
	if ( $book_id <= 0 ) {
		return array();
	}

	$table_name = almaden_bookster_get_book_shares_table_name();
	if ( ! almaden_bookster_table_exists( $table_name ) ) {
		return array();
	}

	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM $table_name WHERE book_id = %d ORDER BY created_at DESC, id DESC",
			$book_id
		),
		ARRAY_A
	);

	if ( empty( $rows ) ) {
		return array();
	}

	$shares = array();
	foreach ( $rows as $row ) {
		$user_id = absint( $row['user_id'] );
		$user    = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			continue;
		}

		$shares[] = array(
			'id'           => absint( $row['id'] ),
			'book_id'      => $book_id,
			'user_id'      => $user_id,
			'display_name' => ! empty( $user->display_name ) ? $user->display_name : $user->user_login,
			'user_login'   => $user->user_login,
			'user_email'   => $user->user_email,
			'avatar_url'   => get_avatar_url( $user_id, array( 'size' => 64 ) ),
			'permission'   => sanitize_key( $row['permission'] ),
			'shared_by'    => absint( $row['shared_by'] ),
			'created_at'   => $row['created_at'],
		);
	}

	return $shares;
}

/**
 * Checks if a specific book is shared with a given user.
 *
 * @param int      $book_id The ID of the book.
 * @param int|null $user_id The ID of the user. Defaults to current user.
 * @return bool
 */
function almaden_bookster_is_book_shared_with_user( int $book_id, ?int $user_id = null ): bool {
	global $wpdb;

	$book_id = absint( $book_id );
	$user_id = $user_id ? absint( $user_id ) : get_current_user_id();

	if ( $book_id <= 0 || $user_id <= 0 ) {
		return false;
	}

	$table_name = almaden_bookster_get_book_shares_table_name();
	if ( ! almaden_bookster_table_exists( $table_name ) ) {
		return false;
	}

	$exists = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT id FROM $table_name WHERE book_id = %d AND user_id = %d LIMIT 1",
			$book_id,
			$user_id
		)
	);

	return ! empty( $exists );
}

/**
 * Returns an array of book IDs shared with the given user.
 *
 * @param int|null $user_id The ID of the user. Defaults to current user.
 * @return array
 */
function almaden_bookster_get_user_shared_book_ids( ?int $user_id = null ): array {
	global $wpdb;

	$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
	if ( $user_id <= 0 ) {
		return array();
	}

	$table_name = almaden_bookster_get_book_shares_table_name();
	if ( ! almaden_bookster_table_exists( $table_name ) ) {
		return array();
	}

	$book_ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT book_id FROM $table_name WHERE user_id = %d",
			$user_id
		)
	);

	if ( empty( $book_ids ) ) {
		return array();
	}

	return array_values( array_unique( array_map( 'absint', $book_ids ) ) );
}

/**
 * Returns all book IDs that should be visible in the user's Workshop/Taller.
 * This includes books authored by the user AND books shared with the user.
 *
 * @param int|null $user_id The ID of the user. Defaults to current user.
 * @return array
 */
function almaden_bookster_get_user_workshop_book_ids( ?int $user_id = null ): array {
	$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
	if ( $user_id <= 0 ) {
		return array();
	}

	// 1. Books authored by user
	$authored_query = get_posts(
		array(
			'post_type'      => 'almaden-books',
			'post_status'    => 'publish',
			'author'         => $user_id,
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);
	$authored_ids = is_array( $authored_query ) ? array_map( 'absint', $authored_query ) : array();

	// 2. Books shared with user
	$shared_ids = almaden_bookster_get_user_shared_book_ids( $user_id );

	return array_values( array_unique( array_merge( $authored_ids, $shared_ids ) ) );
}
