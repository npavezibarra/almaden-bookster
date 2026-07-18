<?php

namespace AlmadenBookster\Learni\QuizEditor;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class QuizEditor {
	public static function init(): void {
		add_shortcode( 'almaden_learni_quiz_creator', array( __CLASS__, 'shortcode' ) );
		add_shortcode( 'almaden_learni_quiz_editor', array( __CLASS__, 'shortcode' ) );
	}

	public static function get_quiz_data( int $quiz_id ): ?array {
		return QuizRepository::get_quiz_data( $quiz_id );
	}

	public static function create_quiz( array $quiz_data ) {
		return QuizRepository::create_quiz( $quiz_data );
	}

	public static function save_quiz( array $quiz_data ) {
		return QuizRepository::save_quiz( $quiz_data );
	}

	public static function quiz_exists( int $quiz_id ): bool {
		return QuizRepository::quiz_exists( $quiz_id );
	}

	public static function get_quiz_id_by_course( int $course_id ): int {
		return QuizRepository::get_quiz_id_by_course( $course_id );
	}

	public static function shortcode( $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'course_id' => 0,
				'quiz_id'    => 0,
			),
			$atts
		);

		$course_id = absint( $atts['course_id'] );
		$quiz_id   = absint( $atts['quiz_id'] );

		if ( ! Permissions::can_access( $course_id, $quiz_id ) ) {
			return '<p>' . esc_html__( 'No tienes permiso para acceder al editor de quizzes.', 'almaden-bookster' ) . '</p>';
		}

		if ( $course_id > 0 && $quiz_id <= 0 ) {
			$quiz_id = self::get_quiz_id_by_course( $course_id );
		}

		return sprintf(
			'<div id="almaden-learni-quiz-editor" class="almaden-learni-quiz-editor" data-course-id="%d" data-quiz-id="%d"><p>%s</p></div>',
			$course_id,
			$quiz_id,
			esc_html__( 'Quiz editor shell ready for the next phase.', 'almaden-bookster' )
		);
	}
}

