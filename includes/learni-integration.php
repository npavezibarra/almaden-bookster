<?php
/**
 * AlmadenBookster - Learni integration settings and helpers.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'ALMADEN_BOOKSTER_LEARNI_INTEGRATION_OPTION' ) ) {
	define( 'ALMADEN_BOOKSTER_LEARNI_INTEGRATION_OPTION', 'almaden_bookster_learni_integration_enabled' );
}

if ( ! defined( 'ALMADEN_BOOKSTER_LEARNI_QUIZ_META' ) ) {
	define( 'ALMADEN_BOOKSTER_LEARNI_QUIZ_META', '_almaden_learni_quiz_id' );
}

if ( ! defined( 'ALMADEN_BOOKSTER_LEARNI_CHAPTER_QUIZ_META' ) ) {
	define( 'ALMADEN_BOOKSTER_LEARNI_CHAPTER_QUIZ_META', '_almaden_learni_chapter_quiz_id' );
}

function almaden_bookster_learni_is_available() {
	return class_exists( '\\Learni\\QuizEditor\\QuizEditor' )
		|| class_exists( '\\Learni\\Frontend\\QuizEditorScreen' )
		|| class_exists( 'PL_Learni_Module' )
		|| defined( 'LEARNI_VERSION' );
}

function almaden_bookster_learni_integration_enabled() {
	return get_option( ALMADEN_BOOKSTER_LEARNI_INTEGRATION_OPTION, '0' ) === '1';
}

function almaden_bookster_learni_integration_active() {
	return almaden_bookster_learni_integration_enabled() && almaden_bookster_learni_is_available();
}

function almaden_bookster_learni_editor_url( $book_id, $quiz_id = 0 ) {
	$book_id = absint( $book_id );
	$quiz_id = absint( $quiz_id );

	if ( class_exists( '\\Learni\\Frontend\\QuizEditorScreen' ) ) {
		return \Learni\Frontend\QuizEditorScreen::quiz_url( $book_id, $quiz_id );
	}

	$args = array();
	if ( $book_id > 0 ) {
		$args['book_id'] = $book_id;
	}
	if ( $quiz_id > 0 ) {
		$args['quiz_id'] = $quiz_id;
	}

	return add_query_arg( $args, home_url( '/almaden-book-quiz/' ) );
}

function almaden_bookster_learni_quiz_exists( $quiz_id ) {
	$quiz_id = absint( $quiz_id );
	if ( $quiz_id <= 0 ) {
		return false;
	}

	if ( class_exists( '\\Learni\\QuizEditor\\QuizRepository' ) ) {
		return \Learni\QuizEditor\QuizRepository::quiz_exists( $quiz_id );
	}

	return false;
}

function almaden_bookster_learni_get_quiz_id( $book_id ) {
	$book_id = absint( $book_id );
	if ( $book_id <= 0 ) {
		return 0;
	}

	$quiz_id = (int) get_post_meta( $book_id, ALMADEN_BOOKSTER_LEARNI_QUIZ_META, true );
	if ( $quiz_id > 0 && almaden_bookster_learni_quiz_exists( $quiz_id ) ) {
		return $quiz_id;
	}

	if ( $quiz_id > 0 ) {
		delete_post_meta( $book_id, ALMADEN_BOOKSTER_LEARNI_QUIZ_META );
	}

	if ( class_exists( '\\Learni\\QuizEditor\\QuizRepository' ) ) {
		$resolved = (int) \Learni\QuizEditor\QuizRepository::get_quiz_id_by_course( $book_id );
		if ( $resolved > 0 && almaden_bookster_learni_quiz_exists( $resolved ) ) {
			update_post_meta( $book_id, ALMADEN_BOOKSTER_LEARNI_QUIZ_META, $resolved );
			return $resolved;
		}
	}

	return 0;
}

function almaden_bookster_learni_set_quiz_id( $book_id, $quiz_id ) {
	$book_id = absint( $book_id );
	$quiz_id = absint( $quiz_id );

	if ( $book_id <= 0 ) {
		return false;
	}

	if ( $quiz_id > 0 ) {
		update_post_meta( $book_id, ALMADEN_BOOKSTER_LEARNI_QUIZ_META, $quiz_id );
		return true;
	}

	delete_post_meta( $book_id, ALMADEN_BOOKSTER_LEARNI_QUIZ_META );
	return true;
}

function almaden_bookster_learni_get_quiz_data_by_quiz_id( $quiz_id ) {
	$quiz_id = absint( $quiz_id );
	if ( $quiz_id <= 0 || ! almaden_bookster_learni_is_available() ) {
		return null;
	}

	if ( class_exists( '\\Learni\\QuizEditor\\QuizEditor' ) ) {
		return \Learni\QuizEditor\QuizEditor::get_quiz_data( $quiz_id );
	}

	return null;
}

function almaden_bookster_learni_get_quiz_primary_chapter_key( $quiz_id ) {
	$quiz_data = almaden_bookster_learni_get_quiz_data_by_quiz_id( $quiz_id );
	if ( ! is_array( $quiz_data ) || empty( $quiz_data['questions'] ) || ! is_array( $quiz_data['questions'] ) ) {
		return '';
	}

	foreach ( $quiz_data['questions'] as $question_row ) {
		if ( ! is_array( $question_row ) ) {
			continue;
		}

		$chapter_key = '';
		if ( isset( $question_row['chapter_key'] ) && is_scalar( $question_row['chapter_key'] ) ) {
			$chapter_key = sanitize_title( (string) $question_row['chapter_key'] );
		}

		if ( $chapter_key !== '' ) {
			return $chapter_key;
		}
	}

	return '';
}

function almaden_bookster_learni_get_quiz_primary_chapter_id( $quiz_id ) {
	$quiz_data = almaden_bookster_learni_get_quiz_data_by_quiz_id( $quiz_id );
	if ( ! is_array( $quiz_data ) || empty( $quiz_data['questions'] ) || ! is_array( $quiz_data['questions'] ) ) {
		return 0;
	}

	foreach ( $quiz_data['questions'] as $question_row ) {
		if ( ! is_array( $question_row ) ) {
			continue;
		}

		if ( isset( $question_row['chapter_id'] ) && is_numeric( $question_row['chapter_id'] ) ) {
			$chapter_id = absint( $question_row['chapter_id'] );
			if ( $chapter_id > 0 ) {
				return $chapter_id;
			}
		}
	}

	return 0;
}

function almaden_bookster_learni_get_chapter_counter_key( $chapter_id ) {
	$chapter_id = absint( $chapter_id );
	if ( $chapter_id <= 0 ) {
		return '';
	}

	$chapter = get_post( $chapter_id );
	if ( ! $chapter || 'book_chapter' !== $chapter->post_type ) {
		return '';
	}

	$chapter_number = (int) $chapter->menu_order;
	if ( $chapter_number > 0 ) {
		return sanitize_title( 'chapter ' . $chapter_number );
	}

	if ( $chapter->post_parent > 0 ) {
		$chapter_posts = get_posts(
			array(
				'post_type'      => 'book_chapter',
				'post_parent'    => (int) $chapter->post_parent,
				'posts_per_page' => -1,
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
			)
		);

		$filtered_index = 0;
		foreach ( $chapter_posts as $sibling ) {
			if ( '1' === (string) get_post_meta( $sibling->ID, '_is_toc', true ) || '1' === (string) get_post_meta( $sibling->ID, '_is_credits', true ) ) {
				continue;
			}

			$filtered_index++;
			if ( (int) $sibling->ID === $chapter_id ) {
				return sanitize_title( 'chapter ' . ( (int) $sibling->menu_order > 0 ? (int) $sibling->menu_order : $filtered_index ) );
			}
		}
	}

	return sanitize_title( 'chapter ' . $chapter_id );
}

function almaden_bookster_learni_get_quiz_id_for_chapter( $chapter_id ) {
	$chapter_id = absint( $chapter_id );
	if ( $chapter_id <= 0 || ! almaden_bookster_learni_is_available() ) {
		return 0;
	}

	$chapter = get_post( $chapter_id );
	if ( ! $chapter || 'book_chapter' !== $chapter->post_type ) {
		return 0;
	}

	$stored_quiz_id = (int) get_post_meta( $chapter_id, ALMADEN_BOOKSTER_LEARNI_CHAPTER_QUIZ_META, true );
	if ( $stored_quiz_id > 0 && almaden_bookster_learni_quiz_exists( $stored_quiz_id ) ) {
		$stored_quiz_chapter_id = almaden_bookster_learni_get_quiz_primary_chapter_id( $stored_quiz_id );
		if ( $stored_quiz_chapter_id > 0 ) {
			if ( $stored_quiz_chapter_id === $chapter_id ) {
				return $stored_quiz_id;
			}
		} else {
			$stored_quiz_chapter_key = almaden_bookster_learni_get_quiz_primary_chapter_key( $stored_quiz_id );
			$current_chapter_key = almaden_bookster_learni_get_chapter_counter_key( $chapter_id );
			if ( $stored_quiz_chapter_key !== '' && $current_chapter_key !== '' && $stored_quiz_chapter_key === $current_chapter_key ) {
				return $stored_quiz_id;
			}
		}

		delete_post_meta( $chapter_id, ALMADEN_BOOKSTER_LEARNI_CHAPTER_QUIZ_META );
		return 0;
	}

	if ( $stored_quiz_id > 0 ) {
		delete_post_meta( $chapter_id, ALMADEN_BOOKSTER_LEARNI_CHAPTER_QUIZ_META );
	}

	return 0;
}

function almaden_bookster_learni_set_quiz_id_for_chapter( $chapter_id, $quiz_id ) {
	$chapter_id = absint( $chapter_id );
	$quiz_id = absint( $quiz_id );

	if ( $chapter_id <= 0 ) {
		return false;
	}

	if ( $quiz_id > 0 ) {
		update_post_meta( $chapter_id, ALMADEN_BOOKSTER_LEARNI_CHAPTER_QUIZ_META, $quiz_id );
		return true;
	}

	delete_post_meta( $chapter_id, ALMADEN_BOOKSTER_LEARNI_CHAPTER_QUIZ_META );
	return true;
}

function almaden_bookster_learni_ensure_quiz_for_book( $book_id ) {
	$book_id = absint( $book_id );
	if ( $book_id <= 0 || ! almaden_bookster_learni_is_available() ) {
		return 0;
	}

	$existing_quiz_id = almaden_bookster_learni_get_quiz_id( $book_id );
	if ( $existing_quiz_id > 0 && almaden_bookster_learni_quiz_exists( $existing_quiz_id ) ) {
		return $existing_quiz_id;
	}

	if ( $existing_quiz_id > 0 ) {
		almaden_bookster_learni_set_quiz_id( $book_id, 0 );
	}

	$book_title = (string) get_the_title( $book_id );
	$quiz_args = array(
		'course_id' => $book_id,
		'title' => $book_title !== '' ? $book_title : __( 'Quiz del libro', 'almaden-bookster' ),
		'settings' => array(
			'passing_score' => 80,
			'time_limit_seconds' => 0,
			'question_order' => 'in_order',
			'shuffle_answers' => 1,
			'show_points' => 0,
			'run_once' => 0,
			'force_solve' => 1,
			'restart_cooldown_days' => 0,
		),
		'questions' => array(
			array(
				'title' => __( 'Pregunta 1', 'almaden-bookster' ),
				'question_text' => '',
				'answers' => array(
					array( 'text' => __( 'Respuesta 1', 'almaden-bookster' ), 'correct' => true ),
					array( 'text' => __( 'Respuesta 2', 'almaden-bookster' ), 'correct' => false ),
				),
			),
		),
	);

	$result = null;
	if ( class_exists( '\\Learni\\QuizEditor\\QuizEditor' ) ) {
		$result = \Learni\QuizEditor\QuizEditor::create_quiz( $quiz_args );
	}

	if ( null === $result ) {
		return 0;
	}

	if ( is_wp_error( $result ) ) {
		return 0;
	}

	$quiz_id = is_array( $result )
		? (int) ( $result['quiz_post_id'] ?? 0 )
		: absint( $result );

	if ( $quiz_id > 0 ) {
		almaden_bookster_learni_set_quiz_id( $book_id, $quiz_id );
	}

	return $quiz_id;
}

function almaden_bookster_learni_get_quiz_data( $book_id ) {
	$book_id = absint( $book_id );
	if ( $book_id <= 0 || ! almaden_bookster_learni_is_available() ) {
		return null;
	}

	$quiz_id = almaden_bookster_learni_get_quiz_id( $book_id );
	if ( $quiz_id <= 0 ) {
		return null;
	}

	if ( class_exists( '\\Learni\\QuizEditor\\QuizEditor' ) ) {
		return \Learni\QuizEditor\QuizEditor::get_quiz_data( $quiz_id );
	}

	return null;
}

function almaden_bookster_learni_normalize_book_quiz_payload( $payload, $book_id, $quiz_id ) {
	$book_id = absint( $book_id );
	$quiz_id = absint( $quiz_id );

	if ( $book_id <= 0 || ! is_array( $payload ) ) {
		return new WP_Error( 'invalid_payload', __( 'Payload inválido.', 'almaden-bookster' ) );
	}

	$source = isset( $payload['quiz'] ) && is_array( $payload['quiz'] ) ? $payload['quiz'] : $payload;
	$source_settings = isset( $source['settings'] ) && is_array( $source['settings'] ) ? $source['settings'] : array();
	$source_questions = array();
	if ( isset( $source['questions'] ) && is_array( $source['questions'] ) ) {
		$source_questions = $source['questions'];
	} elseif ( isset( $payload['questions'] ) && is_array( $payload['questions'] ) ) {
		$source_questions = $payload['questions'];
	}

	if ( empty( $source_questions ) ) {
		return new WP_Error( 'empty_questions', __( 'El quiz no contiene preguntas.', 'almaden-bookster' ) );
	}

	$book_title = (string) get_the_title( $book_id );
	$chapter_key = '';
	if ( isset( $payload['chapter_key'] ) && is_scalar( $payload['chapter_key'] ) ) {
		$chapter_key = sanitize_title( (string) $payload['chapter_key'] );
	} elseif ( isset( $source['chapter_key'] ) && is_scalar( $source['chapter_key'] ) ) {
		$chapter_key = sanitize_title( (string) $source['chapter_key'] );
	}

	$chapter_title = '';
	if ( isset( $payload['chapter_title'] ) && is_scalar( $payload['chapter_title'] ) ) {
		$chapter_title = sanitize_text_field( (string) $payload['chapter_title'] );
	} elseif ( isset( $source['chapter_title'] ) && is_scalar( $source['chapter_title'] ) ) {
		$chapter_title = sanitize_text_field( (string) $source['chapter_title'] );
	}

	$chapter_id = 0;
	if ( isset( $payload['chapter_id'] ) ) {
		$chapter_id = absint( $payload['chapter_id'] );
	} elseif ( isset( $source['chapter_id'] ) ) {
		$chapter_id = absint( $source['chapter_id'] );
	}

	$questions = array();
	foreach ( $source_questions as $index => $question ) {
		if ( ! is_array( $question ) ) {
			continue;
		}

		$question_chapter_key = '';
		if ( isset( $question['chapter_key'] ) && is_scalar( $question['chapter_key'] ) ) {
			$question_chapter_key = sanitize_title( (string) $question['chapter_key'] );
		} elseif ( $chapter_key !== '' ) {
			$question_chapter_key = $chapter_key;
		}

		$question_chapter_title = '';
		if ( isset( $question['chapter_title'] ) && is_scalar( $question['chapter_title'] ) ) {
			$question_chapter_title = sanitize_text_field( (string) $question['chapter_title'] );
		} elseif ( $chapter_title !== '' ) {
			$question_chapter_title = $chapter_title;
		}

		$question_chapter_id = 0;
		if ( isset( $question['chapter_id'] ) ) {
			$question_chapter_id = absint( $question['chapter_id'] );
		} elseif ( $chapter_id > 0 ) {
			$question_chapter_id = $chapter_id;
		}

		$answers = array();
		$source_answers = isset( $question['answers'] ) && is_array( $question['answers'] ) ? $question['answers'] : array();
		foreach ( $source_answers as $answer ) {
			if ( ! is_array( $answer ) ) {
				continue;
			}

			$answers[] = array(
				'text' => isset( $answer['text'] ) ? sanitize_text_field( (string) wp_unslash( $answer['text'] ) ) : '',
				'correct' => ! empty( $answer['correct'] ),
			);
		}

		$questions[] = array(
			'title' => isset( $question['title'] ) ? sanitize_text_field( (string) wp_unslash( $question['title'] ) ) : '',
			'question_text' => isset( $question['question_text'] ) ? wp_kses_post( (string) wp_unslash( $question['question_text'] ) ) : '',
			'chapter_key' => $question_chapter_key,
			'chapter_id' => $question_chapter_id,
			'chapter_title' => $question_chapter_title,
			'answers' => $answers,
		);
	}

	return array(
		'course_id' => $book_id,
		'quiz_id' => $quiz_id,
		'title' => isset( $payload['quiz_title'] ) ? sanitize_text_field( (string) $payload['quiz_title'] ) : ( isset( $source['title'] ) ? sanitize_text_field( (string) $source['title'] ) : $book_title ),
		'settings' => array(
			'passing_score' => isset( $source_settings['passing_score'] ) ? absint( $source_settings['passing_score'] ) : 80,
			'time_limit_seconds' => isset( $source_settings['time_limit_seconds'] ) ? absint( $source_settings['time_limit_seconds'] ) : 0,
			'question_order' => isset( $source_settings['question_order'] ) ? sanitize_key( (string) $source_settings['question_order'] ) : 'in_order',
			'shuffle_answers' => ! empty( $source_settings['shuffle_answers'] ) ? 1 : 0,
			'show_points' => ! empty( $source_settings['show_points'] ) ? 1 : 0,
			'run_once' => ! empty( $source_settings['run_once'] ) ? 1 : 0,
			'force_solve' => ! empty( $source_settings['force_solve'] ) ? 1 : 0,
			'restart_cooldown_days' => isset( $source_settings['restart_cooldown_days'] ) ? absint( $source_settings['restart_cooldown_days'] ) : 0,
			'scope' => isset( $payload['scope'] ) ? sanitize_key( (string ) $payload['scope'] ) : ( isset( $source['scope'] ) ? sanitize_key( (string ) $source['scope'] ) : 'chapter' ),
			'book_title' => isset( $payload['book_title'] ) ? sanitize_text_field( (string ) $payload['book_title'] ) : ( isset( $source['book_title'] ) ? sanitize_text_field( (string ) $source['book_title'] ) : $book_title ),
			'chapter_id' => $chapter_id,
			'chapter_title' => $chapter_title,
			'chapter_key' => $chapter_key,
			'chapter_ids_json' => isset( $source_settings['chapter_ids_json'] ) ? sanitize_textarea_field( (string ) $source_settings['chapter_ids_json'] ) : '',
			'chapter_titles_json' => isset( $source_settings['chapter_titles_json'] ) ? sanitize_textarea_field( (string ) $source_settings['chapter_titles_json'] ) : '',
		),
		'questions' => $questions,
	);
}
require_once dirname( __FILE__ ) . '/learni-integration-actions.php';
