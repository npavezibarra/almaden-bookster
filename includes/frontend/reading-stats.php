<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'almaden_bookster_get_reading_stats_page_id' ) ) {
	function almaden_bookster_get_reading_stats_page_id() {
		$settings = almaden_bookster_get_pages_settings();
		return isset( $settings['reading_stats_page_id'] ) ? absint( $settings['reading_stats_page_id'] ) : 0;
	}
}

if ( ! function_exists( 'almaden_bookster_get_reading_stats_slug' ) ) {
	function almaden_bookster_get_reading_stats_slug() {
		$settings = almaden_bookster_get_pages_settings();
		return isset( $settings['reading_stats_slug'] ) && '' !== $settings['reading_stats_slug'] ? $settings['reading_stats_slug'] : 'my-reading-stats';
	}
}

if ( ! function_exists( 'almaden_bookster_get_reading_stats_title' ) ) {
	function almaden_bookster_get_reading_stats_title() {
		$settings = almaden_bookster_get_pages_settings();
		return isset( $settings['reading_stats_title'] ) && '' !== $settings['reading_stats_title'] ? $settings['reading_stats_title'] : 'My Reading Stats';
	}
}

if ( ! function_exists( 'almaden_bookster_get_reading_stats_page_url' ) ) {
	function almaden_bookster_get_reading_stats_page_url( $query_args = array() ) {
		$slug = trim( almaden_bookster_get_reading_stats_slug(), '/' );
		$url  = home_url( '/' . $slug . '/' );

		if ( empty( $query_args ) ) {
			return $url;
		}

		return add_query_arg( $query_args, $url );
	}
}

if ( ! function_exists( 'almaden_bookster_register_reading_stats_routes' ) ) {
	function almaden_bookster_register_reading_stats_routes() {
		$base_slug = trim( almaden_bookster_get_reading_stats_slug(), '/' );
		if ( '' === $base_slug ) {
			$base_slug = 'my-reading-stats';
		}

		add_rewrite_rule(
			'^' . preg_quote( $base_slug, '/' ) . '/?$',
			'index.php?almaden_reading_stats_view=1',
			'top'
		);
	}
}
add_action( 'init', 'almaden_bookster_register_reading_stats_routes', 20 );

if ( ! function_exists( 'almaden_bookster_register_reading_stats_query_vars' ) ) {
	function almaden_bookster_register_reading_stats_query_vars( $vars ) {
		$vars[] = 'almaden_reading_stats_view';
		return $vars;
	}
}
add_filter( 'query_vars', 'almaden_bookster_register_reading_stats_query_vars' );

if ( ! function_exists( 'almaden_bookster_maybe_flush_reading_stats_rewrite_rules' ) ) {
	function almaden_bookster_maybe_flush_reading_stats_rewrite_rules() {
		$rewrite_version_option = 'almaden_bookster_reading_stats_rewrite_version';
		$rewrite_version        = '1.0.0';

		if ( get_option( $rewrite_version_option ) === $rewrite_version ) {
			return;
		}

		flush_rewrite_rules( false );
		update_option( $rewrite_version_option, $rewrite_version );
	}
}
add_action( 'init', 'almaden_bookster_maybe_flush_reading_stats_rewrite_rules', 99 );

if ( ! function_exists( 'almaden_bookster_sync_reading_stats_page' ) ) {
	function almaden_bookster_sync_reading_stats_page() {
		$settings = almaden_bookster_get_pages_settings();
		$slug     = almaden_bookster_get_reading_stats_slug();
		$title    = almaden_bookster_get_reading_stats_title();
		$page_id  = isset( $settings['reading_stats_page_id'] ) ? absint( $settings['reading_stats_page_id'] ) : 0;
		$page     = $page_id > 0 ? get_post( $page_id ) : null;

		if ( $page && 'page' !== $page->post_type ) {
			$page = null;
		}

		if ( ! $page ) {
			$page = get_page_by_path( $slug, OBJECT, 'page' );
		}

		if ( ! $page ) {
			$new_page_id = wp_insert_post(
				array(
					'post_title'   => $title,
					'post_name'    => $slug,
					'post_status'  => 'publish',
					'post_type'    => 'page',
					'post_content' => '<!-- El contenido de esta pagina es generado dinamicamente por el plugin AlmadenBookster -->',
				)
			);

			if ( ! is_wp_error( $new_page_id ) && $new_page_id ) {
				$settings['reading_stats_page_id'] = absint( $new_page_id );
				$settings['reading_stats_slug']    = $slug;
				$settings['reading_stats_title']   = $title;
				update_option( 'almaden_bookster_pages_settings', $settings );
			}

			return;
		}

		$updates = array( 'ID' => $page->ID );

		if ( $page->post_name !== $slug ) {
			$updates['post_name'] = $slug;
		}

		if ( $page->post_title !== $title ) {
			$updates['post_title'] = $title;
		}

		if ( count( $updates ) > 1 ) {
			wp_update_post( $updates );
		}

		if ( $page_id !== (int) $page->ID ) {
			$settings['reading_stats_page_id'] = (int) $page->ID;
			$settings['reading_stats_slug']    = $slug;
			$settings['reading_stats_title']   = $title;
			update_option( 'almaden_bookster_pages_settings', $settings );
		}
	}
}

if ( ! function_exists( 'almaden_bookster_get_user_reading_stats' ) ) {
	function almaden_bookster_get_user_reading_stats( $user_id = null ) {
		global $wpdb;

		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
		$empty = array(
			'userId' => $user_id,
			'summary' => array(
				'totalBooks' => 0,
				'totalHighlights' => 0,
				'totalAttempts' => 0,
				'totalQuizzesCompleted' => 0,
				'totalQuizzesAvailable' => 0,
				'completionRate' => 0,
				'lastActivityAt' => '',
			),
			'books' => array(),
			'recentHighlights' => array(),
			'recentAttempts' => array(),
			'activity' => array(),
		);

		if ( $user_id <= 0 ) {
			return $empty;
		}

		$highlight_rows = array();
		$attempt_rows   = array();

		$highlight_table = function_exists( 'almaden_bookster_get_highlights_table_name' ) ? almaden_bookster_get_highlights_table_name() : '';
		if ( '' !== $highlight_table && function_exists( 'almaden_bookster_table_exists' ) && almaden_bookster_table_exists( $highlight_table ) ) {
			$highlight_rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM $highlight_table WHERE user_id = %d AND status = %s ORDER BY created_at DESC, id DESC",
					$user_id,
					'active'
				),
				ARRAY_A
			);
		}

		$attempt_table = function_exists( 'almaden_bookster_get_quiz_attempts_table_name' ) ? almaden_bookster_get_quiz_attempts_table_name() : '';
		if ( '' !== $attempt_table && function_exists( 'almaden_bookster_table_exists' ) && almaden_bookster_table_exists( $attempt_table ) ) {
			$attempt_rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM $attempt_table WHERE user_id = %d ORDER BY created_at DESC, id DESC",
					$user_id
				),
				ARRAY_A
			);
		}

		$books = array();
		$passed_quizzes = array();
		$recent_highlights = array();
		$recent_attempts = array();
		$activity = array();
		$last_activity_ts = 0;

		$ensure_book = static function( $book_id ) use ( &$books ) {
			$book_id = absint( $book_id );
			if ( $book_id <= 0 ) {
				return null;
			}

			if ( ! isset( $books[ $book_id ] ) ) {
				$books[ $book_id ] = array(
					'bookId' => $book_id,
					'bookTitle' => get_the_title( $book_id ) ? get_the_title( $book_id ) : sprintf( 'Libro #%d', $book_id ),
					'bookUrl' => get_permalink( $book_id ) ? get_permalink( $book_id ) : '',
					'highlightCount' => 0,
					'attemptCount' => 0,
					'quizCompletedCount' => 0,
					'totalQuizCount' => 0,
					'lastActivityAt' => '',
					'lastActivityTs' => 0,
					'lastChapterTitle' => '',
					'lastHighlightExcerpt' => '',
				);
			}

			return $books[ $book_id ];
		};

		foreach ( $highlight_rows as $row ) {
			$book_id = isset( $row['book_id'] ) ? absint( $row['book_id'] ) : 0;
			if ( $book_id <= 0 ) {
				continue;
			}

			$book = $ensure_book( $book_id );
			if ( null === $book ) {
				continue;
			}

			$created_at = isset( $row['created_at'] ) ? (string) $row['created_at'] : '';
			$ts = $created_at ? strtotime( $created_at ) : 0;
			if ( $ts > 0 ) {
				$last_activity_ts = max( $last_activity_ts, $ts );
				if ( $ts >= (int) $books[ $book_id ]['lastActivityTs'] ) {
					$books[ $book_id ]['lastActivityTs'] = $ts;
					$books[ $book_id ]['lastActivityAt'] = $created_at;
				}
			}

			$books[ $book_id ]['highlightCount']++;
			$books[ $book_id ]['lastChapterTitle'] = isset( $row['chapter_id'] ) ? ( get_the_title( (int) $row['chapter_id'] ) ?: '' ) : '';
			$books[ $book_id ]['lastHighlightExcerpt'] = isset( $row['selected_text'] ) ? trim( preg_replace( '/\s+/', ' ', (string) $row['selected_text'] ) ) : '';

			$recent_highlights[] = array(
				'type' => 'highlight',
				'bookId' => $book_id,
				'bookTitle' => $books[ $book_id ]['bookTitle'],
				'bookUrl' => $books[ $book_id ]['bookUrl'],
				'chapterTitle' => $books[ $book_id ]['lastChapterTitle'],
				'text' => $books[ $book_id ]['lastHighlightExcerpt'],
				'createdAt' => $created_at,
				'timestamp' => $ts,
			);

			$activity[] = array(
				'type' => 'highlight',
				'bookId' => $book_id,
				'bookTitle' => $books[ $book_id ]['bookTitle'],
				'bookUrl' => $books[ $book_id ]['bookUrl'],
				'detail' => $books[ $book_id ]['lastHighlightExcerpt'],
				'chapterTitle' => $books[ $book_id ]['lastChapterTitle'],
				'createdAt' => $created_at,
				'timestamp' => $ts,
			);
		}

		foreach ( $attempt_rows as $row ) {
			$book_id = isset( $row['book_id'] ) ? absint( $row['book_id'] ) : 0;
			if ( $book_id <= 0 ) {
				continue;
			}

			$book = $ensure_book( $book_id );
			if ( null === $book ) {
				continue;
			}

			$created_at = isset( $row['created_at'] ) ? (string) $row['created_at'] : '';
			$ts = $created_at ? strtotime( $created_at ) : 0;
			if ( $ts > 0 ) {
				$last_activity_ts = max( $last_activity_ts, $ts );
				if ( $ts >= (int) $books[ $book_id ]['lastActivityTs'] ) {
					$books[ $book_id ]['lastActivityTs'] = $ts;
					$books[ $book_id ]['lastActivityAt'] = $created_at;
				}
			}

			$books[ $book_id ]['attemptCount']++;
			$quiz_id = isset( $row['quiz_id'] ) ? absint( $row['quiz_id'] ) : 0;
			if ( ! empty( $row['passed'] ) && $quiz_id > 0 ) {
				if ( ! isset( $passed_quizzes[ $book_id ] ) ) {
					$passed_quizzes[ $book_id ] = array();
				}
				$passed_quizzes[ $book_id ][ $quiz_id ] = true;
			}

			$chapter_title = isset( $row['chapter_id'] ) && absint( $row['chapter_id'] ) > 0 ? ( get_the_title( absint( $row['chapter_id'] ) ) ?: '' ) : '';
			$score = isset( $row['score'] ) ? absint( $row['score'] ) : 0;
			$required_score = isset( $row['required_score'] ) ? absint( $row['required_score'] ) : 0;
			$passed = ! empty( $row['passed'] );

			$recent_attempts[] = array(
				'type' => 'attempt',
				'bookId' => $book_id,
				'bookTitle' => $books[ $book_id ]['bookTitle'],
				'bookUrl' => $books[ $book_id ]['bookUrl'],
				'chapterTitle' => $chapter_title,
				'score' => $score,
				'requiredScore' => $required_score,
				'passed' => $passed,
				'createdAt' => $created_at,
				'timestamp' => $ts,
			);

			$activity[] = array(
				'type' => 'attempt',
				'bookId' => $book_id,
				'bookTitle' => $books[ $book_id ]['bookTitle'],
				'bookUrl' => $books[ $book_id ]['bookUrl'],
				'detail' => sprintf( '%d/%d %s', $score, $required_score, $passed ? 'aprobado' : 'pendiente' ),
				'chapterTitle' => $chapter_title,
				'createdAt' => $created_at,
				'timestamp' => $ts,
			);
		}

		$quiz_totals = array();
		foreach ( array_keys( $books ) as $book_id ) {
			if ( function_exists( 'almaden_bookster_get_book_quiz_entries' ) ) {
				$quiz_totals[ $book_id ] = count( almaden_bookster_get_book_quiz_entries( $book_id ) );
			} else {
				$quiz_totals[ $book_id ] = 0;
			}

			$books[ $book_id ]['totalQuizCount'] = $quiz_totals[ $book_id ];
			$books[ $book_id ]['quizCompletedCount'] = isset( $passed_quizzes[ $book_id ] ) ? count( $passed_quizzes[ $book_id ] ) : 0;
			$books[ $book_id ]['completionRate'] = $books[ $book_id ]['totalQuizCount'] > 0 ? (int) round( ( $books[ $book_id ]['quizCompletedCount'] / $books[ $book_id ]['totalQuizCount'] ) * 100 ) : 0;
		}

		uasort(
			$books,
			static function( $a, $b ) {
				$a_ts = isset( $a['lastActivityTs'] ) ? (int) $a['lastActivityTs'] : 0;
				$b_ts = isset( $b['lastActivityTs'] ) ? (int) $b['lastActivityTs'] : 0;
				return $b_ts <=> $a_ts;
			}
		);

		usort(
			$activity,
			static function( $a, $b ) {
				$a_ts = isset( $a['timestamp'] ) ? (int) $a['timestamp'] : 0;
				$b_ts = isset( $b['timestamp'] ) ? (int) $b['timestamp'] : 0;
				return $b_ts <=> $a_ts;
			}
		);

		usort(
			$recent_highlights,
			static function( $a, $b ) {
				$a_ts = isset( $a['timestamp'] ) ? (int) $a['timestamp'] : 0;
				$b_ts = isset( $b['timestamp'] ) ? (int) $b['timestamp'] : 0;
				return $b_ts <=> $a_ts;
			}
		);

		usort(
			$recent_attempts,
			static function( $a, $b ) {
				$a_ts = isset( $a['timestamp'] ) ? (int) $a['timestamp'] : 0;
				$b_ts = isset( $b['timestamp'] ) ? (int) $b['timestamp'] : 0;
				return $b_ts <=> $a_ts;
			}
		);

		$books_array = array_values( $books );
		$total_books = count( $books_array );
		$total_highlights = count( $highlight_rows );
		$total_attempts = count( $attempt_rows );
		$total_quizzes_available = array_sum( $quiz_totals );
		$total_quizzes_completed = 0;
		foreach ( $books_array as $book ) {
			$total_quizzes_completed += isset( $book['quizCompletedCount'] ) ? (int) $book['quizCompletedCount'] : 0;
		}
		$completion_rate = $total_quizzes_available > 0 ? (int) round( ( $total_quizzes_completed / $total_quizzes_available ) * 100 ) : 0;

		return array(
			'userId' => $user_id,
			'summary' => array(
				'totalBooks' => $total_books,
				'totalHighlights' => $total_highlights,
				'totalAttempts' => $total_attempts,
				'totalQuizzesCompleted' => $total_quizzes_completed,
				'totalQuizzesAvailable' => $total_quizzes_available,
				'completionRate' => $completion_rate,
				'lastActivityAt' => $last_activity_ts > 0 ? gmdate( 'Y-m-d H:i:s', $last_activity_ts ) : '',
			),
			'books' => $books_array,
			'recentHighlights' => array_slice( $recent_highlights, 0, 8 ),
			'recentAttempts' => array_slice( $recent_attempts, 0, 8 ),
			'activity' => array_slice( $activity, 0, 12 ),
		);
	}
}
