<?php

namespace AlmadenBookster\Learni\QuizEditor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class QuizRepository {
	private const TABLE_QUIZZES = 'almaden_learni_quizzes';
	private const TABLE_QUESTIONS = 'almaden_learni_quiz_questions';
	private const TABLE_ANSWERS = 'almaden_learni_quiz_answers';

	public static function quiz_exists( int $quiz_id ): bool {
		if ( $quiz_id <= 0 ) {
			return false;
		}

		global $wpdb;
		$table = $wpdb->prefix . self::TABLE_QUIZZES;

		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE id = %d LIMIT 1", $quiz_id ) ) > 0;
	}

	public static function get_course_id_by_quiz_id( int $quiz_id ): int {
		if ( $quiz_id <= 0 ) {
			return 0;
		}

		global $wpdb;
		$table = $wpdb->prefix . self::TABLE_QUIZZES;

		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT course_post_id FROM {$table} WHERE id = %d LIMIT 1", $quiz_id ) );
	}

	public static function get_quiz_id_by_course( int $course_id ): int {
		if ( $course_id <= 0 ) {
			return 0;
		}

		global $wpdb;
		$table = $wpdb->prefix . self::TABLE_QUIZZES;

		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE course_post_id = %d ORDER BY id DESC LIMIT 1", $course_id ) );
	}

	public static function get_quiz_raw( int $quiz_id ): ?array {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE_QUIZZES;

		$row = $wpdb->get_row( $wpdb->prepare( "SELECT id, course_post_id, lesson_post_id, title, passing_score, time_limit_seconds, settings_json FROM {$table} WHERE id = %d LIMIT 1", $quiz_id ), ARRAY_A );
		return is_array( $row ) ? $row : null;
	}

	public static function get_questions_raw( int $quiz_id ): array {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE_QUESTIONS;

		return $wpdb->get_results( $wpdb->prepare( "SELECT id, prompt, sort_order, meta_json FROM {$table} WHERE quiz_id = %d ORDER BY sort_order ASC, id ASC", $quiz_id ), ARRAY_A );
	}

	public static function get_answers_raw( int $question_id ): array {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE_ANSWERS;

		return $wpdb->get_results( $wpdb->prepare( "SELECT answer_text, is_correct, sort_order, meta_json FROM {$table} WHERE question_id = %d ORDER BY sort_order ASC, id ASC", $question_id ), ARRAY_A );
	}

	public static function delete_quiz_children( int $quiz_id ): void {
		global $wpdb;
		$questions = $wpdb->prefix . self::TABLE_QUESTIONS;
		$answers   = $wpdb->prefix . self::TABLE_ANSWERS;

		$question_ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$questions} WHERE quiz_id = %d", $quiz_id ) );
		foreach ( (array) $question_ids as $question_id ) {
			$wpdb->delete( $answers, array( 'question_id' => (int) $question_id ), array( '%d' ) );
		}

		$wpdb->delete( $questions, array( 'quiz_id' => $quiz_id ), array( '%d' ) );
	}

	public static function create_quiz( array $quiz_data ) {
		global $wpdb;

		$title     = sanitize_text_field( (string) ( $quiz_data['title'] ?? '' ) );
		$settings  = is_array( $quiz_data['settings'] ?? null ) ? $quiz_data['settings'] : array();
		$course_id = (int) ( $settings['course_id'] ?? 0 );
		if ( $title === '' || $course_id <= 0 ) {
			return new \WP_Error( 'invalid_data', __( 'Quiz title and course are required.', 'almaden-bookster' ) );
		}

		$questions = self::normalize_questions( $quiz_data['questions'] ?? array() );
		if ( is_wp_error( $questions ) ) {
			return $questions;
		}

		$table = $wpdb->prefix . self::TABLE_QUIZZES;
		$wpdb->insert(
			$table,
			array(
				'course_post_id'    => $course_id,
				'lesson_post_id'    => isset( $settings['lesson_post_id'] ) ? absint( $settings['lesson_post_id'] ) : null,
				'title'             => $title,
				'passing_score'     => isset( $settings['passing_percentage'] ) ? absint( $settings['passing_percentage'] ) : 80,
				'time_limit_seconds'=> isset( $settings['time_limit'] ) ? absint( $settings['time_limit'] ) : 0,
				'settings_json'     => wp_json_encode( $settings ),
				'created_at'        => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%d', '%d', '%s', '%s' )
		);

		$quiz_id = (int) $wpdb->insert_id;
		if ( $quiz_id <= 0 ) {
			return new \WP_Error( 'insert_failed', __( 'Unable to create quiz.', 'almaden-bookster' ) );
		}

		self::insert_questions( $quiz_id, $questions );

		return array(
			'quiz_post_id' => $quiz_id,
			'questions'    => $questions,
		);
	}

	public static function save_quiz( array $quiz_data ) {
		global $wpdb;

		$quiz_id = (int) ( $quiz_data['quiz_id'] ?? 0 );
		if ( $quiz_id <= 0 ) {
			return new \WP_Error( 'invalid_quiz', __( 'Quiz ID required.', 'almaden-bookster' ) );
		}

		$settings = is_array( $quiz_data['settings'] ?? null ) ? $quiz_data['settings'] : array();
		$title = sanitize_text_field( (string) ( $quiz_data['title'] ?? '' ) );
		$update_data = array();
		$update_format = array();

		if ( $title !== '' ) {
			$update_data['title'] = $title;
			$update_format[] = '%s';
		}

		if ( isset( $settings['passing_percentage'] ) ) {
			$update_data['passing_score'] = absint( $settings['passing_percentage'] );
			$update_format[] = '%d';
		}

		if ( isset( $settings['time_limit'] ) ) {
			$update_data['time_limit_seconds'] = absint( $settings['time_limit'] );
			$update_format[] = '%d';
		}

		if ( ! empty( $settings ) ) {
			$update_data['settings_json'] = wp_json_encode( $settings );
			$update_format[] = '%s';
		}

		if ( ! empty( $update_data ) ) {
			$wpdb->update( $wpdb->prefix . self::TABLE_QUIZZES, $update_data, array( 'id' => $quiz_id ), $update_format, array( '%d' ) );
		}

		$questions = self::normalize_questions( $quiz_data['questions'] ?? array() );
		if ( is_wp_error( $questions ) ) {
			return $questions;
		}

		self::delete_quiz_children( $quiz_id );
		self::insert_questions( $quiz_id, $questions );

		return $quiz_id;
	}

	public static function delete_quiz( int $quiz_id ): bool {
		if ( $quiz_id <= 0 ) {
			return false;
		}

		self::delete_quiz_children( $quiz_id );

		global $wpdb;
		$table = $wpdb->prefix . self::TABLE_QUIZZES;
		$wpdb->delete( $table, array( 'id' => $quiz_id ), array( '%d' ) );

		return true;
	}

	public static function get_quiz_data( int $quiz_id ): ?array {
		$quiz = self::get_quiz_raw( $quiz_id );
		if ( ! is_array( $quiz ) ) {
			return null;
		}

		$questions = array();
		foreach ( self::get_questions_raw( $quiz_id ) as $question_row ) {
			$question_id = (int) ( $question_row['id'] ?? 0 );
			$meta        = json_decode( (string) ( $question_row['meta_json'] ?? '' ), true );
			$meta        = is_array( $meta ) ? $meta : array();
			$out_answers = array();

			foreach ( self::get_answers_raw( $question_id ) as $answer_row ) {
				$answer_meta = json_decode( (string) ( $answer_row['meta_json'] ?? '' ), true );
				$answer_meta = is_array( $answer_meta ) ? $answer_meta : array();
				$out_answers[] = array(
					'text'    => (string) ( $answer_row['answer_text'] ?? '' ),
					'correct' => ! empty( $answer_row['is_correct'] ),
					'image_id'=> isset( $answer_meta['image_id'] ) ? absint( $answer_meta['image_id'] ) : 0,
				);
			}

			$questions[] = array(
				'id'            => $question_id,
				'title'         => (string) ( $meta['title'] ?? '' ),
				'question_text'  => (string) ( $question_row['prompt'] ?? '' ),
				'answers'       => $out_answers,
			);
		}

		return array(
			'id'                  => (int) $quiz['id'],
			'title'               => (string) $quiz['title'],
			'passing_score'       => (int) $quiz['passing_score'],
			'time_limit_seconds'  => (int) $quiz['time_limit_seconds'],
			'settings'            => json_decode( (string) $quiz['settings_json'], true ) ?: array(),
			'questions'           => $questions,
		);
	}

	private static function insert_questions( int $quiz_id, array $questions ): void {
		global $wpdb;
		$q_table = $wpdb->prefix . self::TABLE_QUESTIONS;
		$a_table = $wpdb->prefix . self::TABLE_ANSWERS;

		foreach ( array_values( $questions ) as $index => $question ) {
			$wpdb->insert(
				$q_table,
				array(
					'quiz_id'    => $quiz_id,
					'type'       => 'single',
					'prompt'     => (string) ( $question['question_text'] ?? '' ),
					'explanation'=> null,
					'points'     => 1,
					'sort_order' => (int) $index,
					'meta_json'  => wp_json_encode( array( 'title' => (string) ( $question['title'] ?? '' ) ) ),
				),
				array( '%d', '%s', '%s', '%s', '%d', '%d', '%s' )
			);

			$question_id = (int) $wpdb->insert_id;
			foreach ( array_values( (array) ( $question['answers'] ?? array() ) ) as $answer_index => $answer ) {
				$wpdb->insert(
					$a_table,
					array(
						'question_id'  => $question_id,
						'answer_text'  => (string) ( $answer['text'] ?? '' ),
						'is_correct'   => ! empty( $answer['correct'] ) ? 1 : 0,
						'sort_order'   => (int) $answer_index,
						'meta_json'    => wp_json_encode( array( 'image_id' => isset( $answer['image_id'] ) ? absint( $answer['image_id'] ) : 0 ) ),
					),
					array( '%d', '%s', '%d', '%d', '%s' )
				);
			}
		}
	}

	private static function normalize_questions( $questions ) {
		if ( ! is_array( $questions ) || empty( $questions ) ) {
			return new \WP_Error( 'empty_questions', __( 'The quiz needs at least one question.', 'almaden-bookster' ) );
		}

		$out = array();
		foreach ( $questions as $question ) {
			if ( ! is_array( $question ) ) {
				continue;
			}

			$answers = array();
			foreach ( (array) ( $question['answers'] ?? array() ) as $answer ) {
				if ( ! is_array( $answer ) ) {
					continue;
				}
				$text = sanitize_text_field( (string) ( $answer['text'] ?? '' ) );
				if ( $text === '' ) {
					continue;
				}
				$answers[] = array(
					'text'      => $text,
					'correct'   => ! empty( $answer['correct'] ),
					'image_id'  => isset( $answer['image_id'] ) ? absint( $answer['image_id'] ) : 0,
				);
			}

			$out[] = array(
				'title'        => sanitize_text_field( (string) ( $question['title'] ?? '' ) ),
				'question_text'=> wp_kses_post( (string) ( $question['question_text'] ?? '' ) ),
				'answers'      => $answers,
			);
		}

		return $out;
	}
}
