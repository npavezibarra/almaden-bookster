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

require_once dirname( __FILE__ ) . '/learni-integration-helpers.php';
require_once dirname( __FILE__ ) . '/learni-integration-actions.php';
