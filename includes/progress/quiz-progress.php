<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function almaden_bookster_get_quiz_attempts_table_name() {
	global $wpdb;
	return $wpdb->prefix . 'almaden_quiz_attempts';
}

function almaden_bookster_get_book_progress_session_meta_key() {
	return '_almaden_book_progress_sessions';
}

function almaden_bookster_get_book_quiz_entries( $book_id ) {
	$book_id = absint( $book_id );
	if ( $book_id <= 0 ) {
		return array();
	}

	$source_book_id = absint( get_post_meta( $book_id, '_almaden_source_book_id', true ) );
	if ( $source_book_id <= 0 ) {
		$source_book_id = $book_id;
	}

	$chapters = get_posts(
		array(
			'post_type'      => 'book_chapter',
			'post_parent'    => $source_book_id,
			'posts_per_page' => -1,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
		)
	);

	$entries = array();
	foreach ( $chapters as $chapter ) {
		$quiz_id = 0;
		if ( function_exists( 'almaden_bookster_learni_get_quiz_id_for_chapter' ) ) {
			$quiz_id = absint( almaden_bookster_learni_get_quiz_id_for_chapter( $chapter->ID ) );
		}
		if ( $quiz_id <= 0 ) {
			$quiz_id = absint( get_post_meta( $chapter->ID, '_almaden_learni_chapter_quiz_id', true ) );
		}
		if ( $quiz_id <= 0 ) {
			continue;
		}

		$entries[] = array(
			'chapter_id'    => (int) $chapter->ID,
			'chapter_title'  => get_the_title( $chapter->ID ),
			'quiz_id'        => $quiz_id,
		);
	}

	return $entries;
}

function almaden_bookster_create_quiz_progress_tables() {
	global $wpdb;
	$table_name = almaden_bookster_get_quiz_attempts_table_name();
	$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name;

	if ( $table_exists ) {
		return;
	}

	if ( ! function_exists( 'dbDelta' ) ) {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	}

	$charset_collate = $wpdb->get_charset_collate();
	$sql = "CREATE TABLE $table_name (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		user_id bigint(20) unsigned NOT NULL,
		book_id bigint(20) unsigned NOT NULL,
		chapter_id bigint(20) unsigned NOT NULL DEFAULT 0,
		quiz_id bigint(20) unsigned NOT NULL,
		session_token varchar(64) NOT NULL,
		attempt_number int(11) unsigned NOT NULL DEFAULT 1,
		score smallint(5) unsigned NOT NULL DEFAULT 0,
		required_score smallint(5) unsigned NOT NULL DEFAULT 0,
		passed tinyint(1) NOT NULL DEFAULT 0,
		created_at datetime NOT NULL,
		PRIMARY KEY  (id),
		KEY user_book_session (user_id, book_id, session_token),
		KEY quiz_session (quiz_id, session_token)
	) $charset_collate;";

	dbDelta( $sql );
	update_option( 'almaden_bookster_quiz_progress_db_version', '1.0.0' );
}
add_action( 'init', 'almaden_bookster_create_quiz_progress_tables' );

function almaden_bookster_get_user_book_progress_sessions( $user_id = null ) {
	$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
	$sessions = get_user_meta( $user_id, almaden_bookster_get_book_progress_session_meta_key(), true );
	return is_array( $sessions ) ? $sessions : array();
}

function almaden_bookster_get_user_book_progress_session_token( $book_id, $user_id = null, $create = true ) {
	$book_id = absint( $book_id );
	$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
	if ( $book_id <= 0 || $user_id <= 0 ) {
		return '';
	}

	$sessions = almaden_bookster_get_user_book_progress_sessions( $user_id );
	if ( ! empty( $sessions[ $book_id ] ) ) {
		return sanitize_text_field( (string) $sessions[ $book_id ] );
	}

	if ( ! $create ) {
		return '';
	}

	$sessions[ $book_id ] = wp_generate_uuid4();
	update_user_meta( $user_id, almaden_bookster_get_book_progress_session_meta_key(), $sessions );

	return sanitize_text_field( (string) $sessions[ $book_id ] );
}

function almaden_bookster_set_user_book_progress_session_token( $book_id, $session_token, $user_id = null ) {
	$book_id = absint( $book_id );
	$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
	$session_token = sanitize_text_field( (string) $session_token );

	if ( $book_id <= 0 || $user_id <= 0 || '' === $session_token ) {
		return false;
	}

	$sessions = almaden_bookster_get_user_book_progress_sessions( $user_id );
	$sessions[ $book_id ] = $session_token;
	update_user_meta( $user_id, almaden_bookster_get_book_progress_session_meta_key(), $sessions );

	return true;
}

function almaden_bookster_get_book_quiz_progress_payload( $book_id, $user_id = null ) {
	global $wpdb;

	$book_id = absint( $book_id );
	$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
	if ( $book_id <= 0 || $user_id <= 0 ) {
		return array();
	}

	$entries = almaden_bookster_get_book_quiz_entries( $book_id );
	$session_token = almaden_bookster_get_user_book_progress_session_token( $book_id, $user_id, true );
	$table_name = almaden_bookster_get_quiz_attempts_table_name();

	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM $table_name WHERE user_id = %d AND book_id = %d AND session_token = %s ORDER BY created_at ASC, id ASC",
			$user_id,
			$book_id,
			$session_token
		),
		ARRAY_A
	);
	$rows = is_array( $rows ) ? $rows : array();

	$summary = array();
	$attempts = array();
	foreach ( $rows as $row ) {
		$quiz_id = isset( $row['quiz_id'] ) ? absint( $row['quiz_id'] ) : 0;
		if ( ! isset( $summary[ $quiz_id ] ) ) {
			$summary[ $quiz_id ] = array(
				'quiz_id' => $quiz_id,
				'chapter_id' => isset( $row['chapter_id'] ) ? absint( $row['chapter_id'] ) : 0,
				'attempts' => 0,
				'latest_score' => 0,
				'best_score' => 0,
				'passed' => false,
				'latest_created_at' => '',
			);
		}

		$summary[ $quiz_id ]['attempts']++;
		$summary[ $quiz_id ]['latest_score'] = isset( $row['score'] ) ? absint( $row['score'] ) : 0;
		$summary[ $quiz_id ]['best_score'] = max( $summary[ $quiz_id ]['best_score'], isset( $row['score'] ) ? absint( $row['score'] ) : 0 );
		$summary[ $quiz_id ]['passed'] = ! empty( $row['passed'] );
		$summary[ $quiz_id ]['latest_created_at'] = isset( $row['created_at'] ) ? $row['created_at'] : '';

		$attempts[] = array(
			'quiz_id' => $quiz_id,
			'chapter_id' => isset( $row['chapter_id'] ) ? absint( $row['chapter_id'] ) : 0,
			'attempt_number' => isset( $row['attempt_number'] ) ? absint( $row['attempt_number'] ) : 0,
			'score' => isset( $row['score'] ) ? absint( $row['score'] ) : 0,
			'required_score' => isset( $row['required_score'] ) ? absint( $row['required_score'] ) : 0,
			'passed' => ! empty( $row['passed'] ),
			'created_at' => isset( $row['created_at'] ) ? $row['created_at'] : '',
		);
	}

	$completed_quiz_ids = array();
	foreach ( $summary as $quiz_id => $item ) {
		if ( ! empty( $item['passed'] ) ) {
			$completed_quiz_ids[] = $quiz_id;
		}
	}

	$total_quizzes = count( $entries );
	$completed_quizzes = count( $completed_quiz_ids );
	$all_completed = $total_quizzes > 0 && $completed_quizzes >= $total_quizzes;

	$quiz_items = array();
	foreach ( $entries as $entry ) {
		$quiz_id = $entry['quiz_id'];
		$item = isset( $summary[ $quiz_id ] ) ? $summary[ $quiz_id ] : array(
			'attempts' => 0,
			'latest_score' => 0,
			'best_score' => 0,
			'passed' => false,
			'latest_created_at' => '',
		);
		$quiz_items[] = array_merge(
			$entry,
			$item,
			array(
				'completed' => ! empty( $item['passed'] ),
			)
		);
	}

	return array(
		'bookId' => $book_id,
		'userId' => $user_id,
		'sessionToken' => $session_token,
		'totalQuizzes' => $total_quizzes,
		'completedQuizzes' => $completed_quizzes,
		'remainingQuizzes' => max( 0, $total_quizzes - $completed_quizzes ),
		'allQuizzesCompleted' => $all_completed,
		'resetAvailable' => $all_completed,
		'quizzes' => $quiz_items,
		'attempts' => array_reverse( $attempts ),
	);
}

function almaden_bookster_record_quiz_attempt( $book_id, $quiz_id, $score, $required_score, $passed, $chapter_id = 0, $user_id = null ) {
	global $wpdb;

	$book_id = absint( $book_id );
	$quiz_id = absint( $quiz_id );
	$chapter_id = absint( $chapter_id );
	$user_id = $user_id ? absint( $user_id ) : get_current_user_id();

	if ( $book_id <= 0 || $quiz_id <= 0 || $user_id <= 0 ) {
		return array();
	}

	$session_token = almaden_bookster_get_user_book_progress_session_token( $book_id, $user_id, true );
	$table_name = almaden_bookster_get_quiz_attempts_table_name();
	$attempt_number = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND book_id = %d AND session_token = %s AND quiz_id = %d",
			$user_id,
			$book_id,
			$session_token,
			$quiz_id
		)
	);
	$attempt_number++;

	$wpdb->insert(
		$table_name,
		array(
			'user_id' => $user_id,
			'book_id' => $book_id,
			'chapter_id' => $chapter_id,
			'quiz_id' => $quiz_id,
			'session_token' => $session_token,
			'attempt_number' => $attempt_number,
			'score' => absint( $score ),
			'required_score' => absint( $required_score ),
			'passed' => $passed ? 1 : 0,
			'created_at' => current_time( 'mysql' ),
		),
		array( '%d', '%d', '%d', '%d', '%s', '%d', '%d', '%d', '%d', '%s' )
	);

	return almaden_bookster_get_book_quiz_progress_payload( $book_id, $user_id );
}

function almaden_bookster_reset_book_progress( $book_id, $user_id = null ) {
	$book_id = absint( $book_id );
	$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
	if ( $book_id <= 0 || $user_id <= 0 ) {
		return new WP_Error( 'invalid_book', __( 'Libro inválido.', 'almaden-bookster' ) );
	}

	$payload = almaden_bookster_get_book_quiz_progress_payload( $book_id, $user_id );
	if ( empty( $payload['resetAvailable'] ) ) {
		return new WP_Error( 'reset_not_available', __( 'Primero debes completar todos los quizzes del libro.', 'almaden-bookster' ) );
	}

	$new_session_token = wp_generate_uuid4();
	almaden_bookster_set_user_book_progress_session_token( $book_id, $new_session_token, $user_id );

	return almaden_bookster_get_book_quiz_progress_payload( $book_id, $user_id );
}
