<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function almaden_bookster_get_chapter_progress_table_name() {
	global $wpdb;

	return $wpdb->prefix . 'almaden_book_chapter_reads';
}

function almaden_bookster_get_chapter_progress_db_version() {
	return '2.0.0';
}

function almaden_bookster_get_chapter_progress_rounds_meta_key() {
	return '_almaden_book_chapter_read_rounds';
}

function almaden_bookster_get_user_book_chapter_rounds_from_meta( $user_id = null ) {
	$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
	if ( $user_id <= 0 ) {
		return array();
	}

	$rounds = get_user_meta( $user_id, almaden_bookster_get_chapter_progress_rounds_meta_key(), true );
	return is_array( $rounds ) ? $rounds : array();
}

function almaden_bookster_get_user_book_current_chapter_round( $book_id, $user_id = null ) {
	$book_id = absint( $book_id );
	$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
	if ( $book_id <= 0 || $user_id <= 0 ) {
		return 1;
	}

	$rounds = almaden_bookster_get_user_book_chapter_rounds_from_meta( $user_id );
	$current_round = isset( $rounds[ $book_id ] ) ? absint( $rounds[ $book_id ] ) : 1;
	return $current_round > 0 ? $current_round : 1;
}

function almaden_bookster_set_user_book_current_chapter_round( $book_id, $round, $user_id = null ) {
	$book_id = absint( $book_id );
	$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
	$round   = max( 1, absint( $round ) );
	if ( $book_id <= 0 || $user_id <= 0 ) {
		return false;
	}

	$rounds = almaden_bookster_get_user_book_chapter_rounds_from_meta( $user_id );
	$rounds[ $book_id ] = $round;

	return (bool) update_user_meta( $user_id, almaden_bookster_get_chapter_progress_rounds_meta_key(), $rounds );
}

function almaden_bookster_delete_user_book_chapter_round_meta( $book_id, $user_id = null ) {
	$book_id = absint( $book_id );
	$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
	if ( $book_id <= 0 || $user_id <= 0 ) {
		return false;
	}

	$rounds = almaden_bookster_get_user_book_chapter_rounds_from_meta( $user_id );
	if ( isset( $rounds[ $book_id ] ) ) {
		unset( $rounds[ $book_id ] );
	}

	if ( empty( $rounds ) ) {
		return delete_user_meta( $user_id, almaden_bookster_get_chapter_progress_rounds_meta_key() );
	}

	return (bool) update_user_meta( $user_id, almaden_bookster_get_chapter_progress_rounds_meta_key(), $rounds );
}

function almaden_bookster_get_user_book_chapter_reads_from_table( $book_id, $user_id = null, $round = null ) {
	global $wpdb;

	$book_id = absint( $book_id );
	$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
	if ( $book_id <= 0 || $user_id <= 0 || ! function_exists( 'almaden_bookster_table_exists' ) ) {
		return array();
	}

	$round = null === $round ? almaden_bookster_get_user_book_current_chapter_round( $book_id, $user_id ) : max( 1, absint( $round ) );
	$table_name = almaden_bookster_get_chapter_progress_table_name();
	if ( ! almaden_bookster_table_exists( $table_name ) ) {
		return array();
	}

	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT chapter_id, reading_round, first_read_at, last_read_at, created_at, updated_at
			 FROM {$table_name}
			 WHERE user_id = %d AND book_id = %d AND reading_round = %d
			 ORDER BY chapter_id ASC",
			$user_id,
			$book_id,
			$round
		),
		ARRAY_A
	);

	if ( empty( $rows ) || ! is_array( $rows ) ) {
		return array();
	}

	$reads = array();
	foreach ( $rows as $row ) {
		$chapter_id = absint( $row['chapter_id'] ?? 0 );
		if ( $chapter_id <= 0 ) {
			continue;
		}

		$key = (string) $chapter_id;
		$reads[ $key ] = array(
			'read' => true,
			'first_read_at' => sanitize_text_field( (string) ( $row['first_read_at'] ?? '' ) ),
			'last_read_at' => sanitize_text_field( (string) ( $row['last_read_at'] ?? '' ) ),
			'created_at' => sanitize_text_field( (string) ( $row['created_at'] ?? '' ) ),
			'updated_at' => sanitize_text_field( (string) ( $row['updated_at'] ?? '' ) ),
		);
	}

	return $reads;
}

function almaden_bookster_get_user_book_chapter_reads( $book_id, $user_id = null ) {
	return almaden_bookster_get_user_book_chapter_reads_from_table( $book_id, $user_id );
}

function almaden_bookster_upsert_chapter_progress_row( $user_id, $book_id, $chapter_id, $first_read_at, $last_read_at, $round = 1 ) {
	global $wpdb;

	$table_name = almaden_bookster_get_chapter_progress_table_name();
	if ( ! function_exists( 'almaden_bookster_table_exists' ) || ! almaden_bookster_table_exists( $table_name ) ) {
		return false;
	}

	$sql = "INSERT INTO {$table_name}
		(user_id, book_id, chapter_id, reading_round, first_read_at, last_read_at, created_at, updated_at)
		VALUES (%d, %d, %d, %d, %s, %s, %s, %s)
		ON DUPLICATE KEY UPDATE
		reading_round = VALUES(reading_round),
		first_read_at = VALUES(first_read_at),
		last_read_at = VALUES(last_read_at),
		updated_at = VALUES(updated_at)";

	return false !== $wpdb->query(
		$wpdb->prepare(
			$sql,
			absint( $user_id ),
			absint( $book_id ),
			absint( $chapter_id ),
			max( 1, absint( $round ) ),
			sanitize_text_field( (string) $first_read_at ),
			sanitize_text_field( (string) $last_read_at ),
			sanitize_text_field( (string) $first_read_at ),
			sanitize_text_field( (string) $last_read_at )
		)
	);
}

function almaden_bookster_delete_chapter_progress_row( $user_id, $book_id, $chapter_id ) {
	global $wpdb;

	$table_name = almaden_bookster_get_chapter_progress_table_name();
	if ( ! function_exists( 'almaden_bookster_table_exists' ) || ! almaden_bookster_table_exists( $table_name ) ) {
		return false;
	}

	return false !== $wpdb->delete(
		$table_name,
		array(
			'user_id' => absint( $user_id ),
			'book_id' => absint( $book_id ),
			'chapter_id' => absint( $chapter_id ),
			'reading_round' => almaden_bookster_get_user_book_current_chapter_round( $book_id, $user_id ),
		),
		array( '%d', '%d', '%d', '%d' )
	);
}

function almaden_bookster_get_chapter_progress_table_column_exists( $table_name, $column_name ) {
	global $wpdb;

	if ( ! almaden_bookster_table_exists( $table_name ) ) {
		return false;
	}

	$column = $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM {$table_name} LIKE %s", $column_name ) );
	return ! empty( $column );
}

function almaden_bookster_get_chapter_progress_table_index_exists( $table_name, $index_name ) {
	global $wpdb;

	if ( ! almaden_bookster_table_exists( $table_name ) ) {
		return false;
	}

	$index = $wpdb->get_var( $wpdb->prepare( "SHOW INDEX FROM {$table_name} WHERE Key_name = %s", $index_name ) );
	return ! empty( $index );
}

function almaden_bookster_upgrade_chapter_progress_table() {
	global $wpdb;

	$table_name = almaden_bookster_get_chapter_progress_table_name();
	if ( ! almaden_bookster_table_exists( $table_name ) ) {
		return;
	}

	if ( ! almaden_bookster_get_chapter_progress_table_column_exists( $table_name, 'reading_round' ) ) {
		$wpdb->query( "ALTER TABLE {$table_name} ADD COLUMN reading_round bigint(20) unsigned NOT NULL DEFAULT 1 AFTER chapter_id" );
	}

	if ( almaden_bookster_get_chapter_progress_table_index_exists( $table_name, 'user_book_chapter' ) ) {
		$wpdb->query( "ALTER TABLE {$table_name} DROP INDEX user_book_chapter" );
	}

	if ( ! almaden_bookster_get_chapter_progress_table_index_exists( $table_name, 'user_book_round_chapter' ) ) {
		$wpdb->query( "ALTER TABLE {$table_name} ADD UNIQUE KEY user_book_round_chapter (user_id, book_id, reading_round, chapter_id)" );
	}

	if ( almaden_bookster_get_chapter_progress_table_column_exists( $table_name, 'reading_round' ) ) {
		$wpdb->query( "UPDATE {$table_name} SET reading_round = 1 WHERE reading_round IS NULL OR reading_round = 0" );
	}
}

function almaden_bookster_cleanup_legacy_chapter_progress_meta() {
	$option = 'almaden_bookster_chapter_progress_legacy_meta_cleaned';
	$version = '1.0.0';
	if ( (string) get_option( $option, '' ) === $version ) {
		return;
	}

	$user_ids = get_users(
		array(
			'fields' => 'ID',
			'number' => -1,
			'meta_key' => '_almaden_book_chapter_reads',
			'meta_compare' => 'EXISTS',
		)
	);

	foreach ( (array) $user_ids as $user_id ) {
		delete_user_meta( absint( $user_id ), '_almaden_book_chapter_reads' );
	}

	update_option( $option, $version, true );
}

function almaden_bookster_create_chapter_progress_table() {
	global $wpdb;

	$table_name = almaden_bookster_get_chapter_progress_table_name();
	$db_version = almaden_bookster_get_chapter_progress_db_version();
	$db_option  = 'almaden_bookster_chapter_progress_db_version';
	$table_exists = function_exists( 'almaden_bookster_table_exists' ) ? almaden_bookster_table_exists( $table_name ) : false;
	if ( $table_exists && (string) get_option( $db_option, '' ) === $db_version ) {
		return;
	}

	if ( ! function_exists( 'dbDelta' ) ) {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	}

	$charset_collate = $wpdb->get_charset_collate();
	$sql = "CREATE TABLE {$table_name} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		user_id bigint(20) unsigned NOT NULL,
		book_id bigint(20) unsigned NOT NULL,
		chapter_id bigint(20) unsigned NOT NULL,
		reading_round bigint(20) unsigned NOT NULL DEFAULT 1,
		first_read_at datetime NOT NULL,
		last_read_at datetime NOT NULL,
		created_at datetime NOT NULL,
		updated_at datetime NOT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY user_book_round_chapter (user_id, book_id, reading_round, chapter_id),
		KEY user_book (user_id, book_id),
		KEY book_chapter (book_id, chapter_id)
	) {$charset_collate};";

	dbDelta( $sql );
	almaden_bookster_upgrade_chapter_progress_table();
	update_option( $db_option, $db_version, true );
}

function almaden_bookster_get_book_readable_chapter_ids( $book_id ) {
	$book_id = absint( $book_id );
	if ( $book_id <= 0 ) {
		return array();
	}

	$source_book_id = absint( get_post_meta( $book_id, '_almaden_source_book_id', true ) );
	if ( $source_book_id <= 0 ) {
		$source_book_id = $book_id;
	}

	$chapter_ids = get_posts(
		array(
			'post_type'      => 'book_chapter',
			'post_parent'    => $source_book_id,
			'posts_per_page' => -1,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
			'fields'         => 'ids',
		)
	);

	return array_values(
		array_filter(
			array_map( 'absint', $chapter_ids ),
			static function ( $chapter_id ) {
				return '1' !== (string) get_post_meta( $chapter_id, '_is_toc', true )
					&& '1' !== (string) get_post_meta( $chapter_id, '_is_credits', true );
			}
		)
	);
}

function almaden_bookster_get_book_chapter_read_progress_payload( $book_id, $user_id = null ) {
	$book_id = absint( $book_id );
	$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
	if ( $book_id <= 0 || $user_id <= 0 ) {
		return array();
	}

	$chapter_ids = almaden_bookster_get_book_readable_chapter_ids( $book_id );
	$current_round = almaden_bookster_get_user_book_current_chapter_round( $book_id, $user_id );
	$stored_reads = almaden_bookster_get_user_book_chapter_reads( $book_id, $user_id );
	$chapters = array();
	$read_chapter_ids = array();

	foreach ( $chapter_ids as $chapter_id ) {
		$key = (string) $chapter_id;
		$entry = isset( $stored_reads[ $key ] ) && is_array( $stored_reads[ $key ] ) ? $stored_reads[ $key ] : array();
		$is_read = ! empty( $entry['read'] );
		if ( $is_read ) {
			$read_chapter_ids[] = $chapter_id;
		}
		$chapters[ $key ] = array(
			'read' => $is_read,
			'firstReadAt' => isset( $entry['first_read_at'] ) ? sanitize_text_field( $entry['first_read_at'] ) : '',
			'lastReadAt' => isset( $entry['last_read_at'] ) ? sanitize_text_field( $entry['last_read_at'] ) : '',
		);
	}

	$total = count( $chapter_ids );
	$read = count( $read_chapter_ids );
	return array(
		'bookId' => $book_id,
		'totalChapters' => $total,
		'readChapters' => $read,
		'remainingChapters' => max( 0, $total - $read ),
		'completionPercent' => $total > 0 ? (int) round( ( $read / $total ) * 100 ) : 0,
		'activeRound' => $current_round,
		'readChapterIds' => $read_chapter_ids,
		'chapters' => $chapters,
	);
}

function almaden_bookster_set_chapter_read_state( $book_id, $chapter_id, $is_read, $user_id = null ) {
	$book_id = absint( $book_id );
	$chapter_id = absint( $chapter_id );
	$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
	if ( $book_id <= 0 || $chapter_id <= 0 || $user_id <= 0 ) {
		return new WP_Error( 'invalid_progress_data', __( 'Datos de lectura inválidos.', 'almaden-bookster' ) );
	}

	$chapter_ids = almaden_bookster_get_book_readable_chapter_ids( $book_id );
	if ( ! in_array( $chapter_id, $chapter_ids, true ) ) {
		return new WP_Error( 'invalid_chapter', __( 'Este capítulo no pertenece al libro.', 'almaden-bookster' ) );
	}

	$book_reads = almaden_bookster_get_user_book_chapter_reads( $book_id, $user_id );
	$key = (string) $chapter_id;
	$current_round = almaden_bookster_get_user_book_current_chapter_round( $book_id, $user_id );

	if ( $is_read ) {
		$existing = isset( $book_reads[ $key ] ) && is_array( $book_reads[ $key ] ) ? $book_reads[ $key ] : array();
		$now = current_time( 'mysql' );
		$first_read_at = ! empty( $existing['first_read_at'] ) ? sanitize_text_field( $existing['first_read_at'] ) : $now;
		$book_reads[ $key ] = array(
			'read' => true,
			'first_read_at' => $first_read_at,
			'last_read_at' => $now,
		);
		almaden_bookster_upsert_chapter_progress_row( $user_id, $book_id, $chapter_id, $first_read_at, $now, $current_round );
	} else {
		unset( $book_reads[ $key ] );
		almaden_bookster_delete_chapter_progress_row( $user_id, $book_id, $chapter_id );
	}

	return almaden_bookster_get_book_chapter_read_progress_payload( $book_id, $user_id );
}

function almaden_bookster_ajax_set_chapter_read_state() {
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( __( 'Debes iniciar sesión para guardar tu lectura.', 'almaden-bookster' ) );
	}

	$book_id = isset( $_POST['book_id'] ) ? absint( $_POST['book_id'] ) : 0;
	$chapter_id = isset( $_POST['chapter_id'] ) ? absint( $_POST['chapter_id'] ) : 0;
	$is_read = ! empty( $_POST['is_read'] ) && '0' !== (string) $_POST['is_read'];
	$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'almaden_book_progress_' . $book_id ) ) {
		wp_send_json_error( __( 'Validación de seguridad fallida.', 'almaden-bookster' ) );
	}
	if ( function_exists( 'almaden_bookster_user_can_access_book' ) && ! almaden_bookster_user_can_access_book( $book_id ) ) {
		wp_send_json_error( __( 'No tienes acceso a este libro.', 'almaden-bookster' ) );
	}

	$progress = almaden_bookster_set_chapter_read_state( $book_id, $chapter_id, $is_read );
	if ( is_wp_error( $progress ) ) {
		wp_send_json_error( $progress->get_error_message() );
	}

	wp_send_json_success( array( 'progress' => $progress ) );
}
add_action( 'wp_ajax_almaden_set_chapter_read_state', 'almaden_bookster_ajax_set_chapter_read_state' );

function almaden_bookster_restart_book_chapter_reading( $book_id, $user_id = null ) {
	$book_id = absint( $book_id );
	$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
	if ( $book_id <= 0 || $user_id <= 0 ) {
		return new WP_Error( 'invalid_progress_data', __( 'Datos de lectura inválidos.', 'almaden-bookster' ) );
	}

	$current_round = almaden_bookster_get_user_book_current_chapter_round( $book_id, $user_id );
	$next_round = max( 1, $current_round + 1 );
	almaden_bookster_set_user_book_current_chapter_round( $book_id, $next_round, $user_id );

	return almaden_bookster_get_book_chapter_read_progress_payload( $book_id, $user_id );
}

function almaden_bookster_ajax_restart_book_chapter_reading() {
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( __( 'Debes iniciar sesión para reiniciar la lectura.', 'almaden-bookster' ) );
	}

	$book_id = isset( $_POST['book_id'] ) ? absint( $_POST['book_id'] ) : 0;
	$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'almaden_book_progress_' . $book_id ) ) {
		wp_send_json_error( __( 'Validación de seguridad fallida.', 'almaden-bookster' ) );
	}
	if ( function_exists( 'almaden_bookster_user_can_access_book' ) && ! almaden_bookster_user_can_access_book( $book_id ) ) {
		wp_send_json_error( __( 'No tienes acceso a este libro.', 'almaden-bookster' ) );
	}

	$progress = almaden_bookster_restart_book_chapter_reading( $book_id );
	if ( is_wp_error( $progress ) ) {
		wp_send_json_error( $progress->get_error_message() );
	}

	wp_send_json_success( array( 'progress' => $progress ) );
}
add_action( 'wp_ajax_almaden_restart_book_chapter_reading', 'almaden_bookster_ajax_restart_book_chapter_reading' );

add_action( 'plugins_loaded', 'almaden_bookster_cleanup_legacy_chapter_progress_meta', 20 );
