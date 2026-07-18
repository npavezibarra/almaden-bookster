<?php

namespace AlmadenBookster\Learni\Dashboard;

use AlmadenBookster\Learni\PostTypes\Course;
use AlmadenBookster\Learni\PostTypes\Lesson;
use AlmadenBookster\Learni\QuizEditor\QuizEditor;
use AlmadenBookster\Learni\QuizEditor\QuizRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CourseEditorHandler {
	public static function init(): void {
		add_action( 'admin_post_almaden_learni_save_course', array( __CLASS__, 'handle_save_course' ) );
		add_action( 'admin_post_almaden_learni_save_lesson', array( __CLASS__, 'handle_save_lesson' ) );
		add_action( 'admin_post_almaden_learni_delete_lesson', array( __CLASS__, 'handle_delete_lesson' ) );
		add_action( 'admin_post_almaden_learni_save_course_quiz', array( __CLASS__, 'handle_save_course_quiz' ) );
	}

	public static function handle_save_course(): void {
		$course_id = self::require_course_access();
		self::verify_nonce( 'almaden_learni_save_course_' . $course_id );

		$title = isset( $_POST['course_title'] ) ? sanitize_text_field( wp_unslash( $_POST['course_title'] ) ) : '';
		$content = isset( $_POST['course_content'] ) ? wp_kses_post( wp_unslash( $_POST['course_content'] ) ) : '';
		$excerpt = isset( $_POST['course_excerpt'] ) ? sanitize_text_field( wp_unslash( $_POST['course_excerpt'] ) ) : '';
		$status = isset( $_POST['course_status'] ) ? sanitize_key( wp_unslash( $_POST['course_status'] ) ) : 'draft';
		$linear_order = ! empty( $_POST['course_linear_order'] ) ? 1 : 0;
		$payment_mode = isset( $_POST['course_payment_mode'] ) ? sanitize_key( wp_unslash( $_POST['course_payment_mode'] ) ) : 'woocommerce';
		$cover_photo_id = isset( $_POST['course_cover_photo_id'] ) ? absint( $_POST['course_cover_photo_id'] ) : 0;

		if ( $title === '' ) {
			$title = __( 'Nuevo curso', 'almaden-bookster' );
		}

		wp_update_post(
			array(
				'ID'           => $course_id,
				'post_title'   => $title,
				'post_content' => $content,
				'post_excerpt' => $excerpt,
				'post_status'  => in_array( $status, array( 'draft', 'pending', 'publish', 'private' ), true ) ? $status : 'draft',
			)
		);

		update_post_meta( $course_id, Course::META_LINEAR_ORDER, $linear_order );
		update_post_meta( $course_id, Course::META_PAYMENT_MODE, in_array( $payment_mode, array( 'woocommerce', 'direct' ), true ) ? $payment_mode : 'woocommerce' );
		update_post_meta( $course_id, Course::META_COVER_PHOTO_ID, $cover_photo_id );

		self::redirect_back( $course_id, 'curso', array( 'course_saved' => '1' ) );
	}

	public static function handle_save_lesson(): void {
		$course_id = self::require_course_access();
		self::verify_nonce( 'almaden_learni_save_lesson_' . $course_id );

		$lesson_id = isset( $_POST['lesson_id'] ) ? absint( $_POST['lesson_id'] ) : 0;
		$title = isset( $_POST['lesson_title'] ) ? sanitize_text_field( wp_unslash( $_POST['lesson_title'] ) ) : '';
		$content = isset( $_POST['lesson_content'] ) ? wp_kses_post( wp_unslash( $_POST['lesson_content'] ) ) : '';
		$video_url = isset( $_POST['lesson_video_url'] ) ? esc_url_raw( wp_unslash( $_POST['lesson_video_url'] ) ) : '';
		$available_at = isset( $_POST['lesson_available_at'] ) ? sanitize_text_field( wp_unslash( $_POST['lesson_available_at'] ) ) : '';
		$menu_order = isset( $_POST['lesson_menu_order'] ) ? absint( $_POST['lesson_menu_order'] ) : 0;

		if ( $title === '' ) {
			$title = __( 'Nueva lección', 'almaden-bookster' );
		}

		$postarr = array(
			'post_title'   => $title,
			'post_content' => $content,
			'post_status'  => 'draft',
			'post_type'    => Lesson::POST_TYPE,
			'post_parent'  => $course_id,
			'menu_order'   => $menu_order,
			'meta_input'   => array(
				Lesson::META_COURSE_ID    => $course_id,
				Lesson::META_VIDEO_URL    => $video_url,
				Lesson::META_AVAILABLE_AT  => $available_at,
			),
		);

		if ( $lesson_id > 0 ) {
			$postarr['ID'] = $lesson_id;
			wp_update_post( $postarr );
		} else {
			$postarr['post_author'] = get_current_user_id();
			$lesson_id = (int) wp_insert_post( $postarr );
		}

		if ( $lesson_id > 0 ) {
			update_post_meta( $lesson_id, Lesson::META_COURSE_ID, $course_id );
			update_post_meta( $lesson_id, Lesson::META_VIDEO_URL, $video_url );
			update_post_meta( $lesson_id, Lesson::META_AVAILABLE_AT, $available_at );
			wp_update_post(
				array(
					'ID'         => $lesson_id,
					'menu_order' => $menu_order,
				)
			);
		}

		self::redirect_back( $course_id, 'lecciones', array( 'lesson_saved' => '1' ) );
	}

	public static function handle_delete_lesson(): void {
		$course_id = self::require_course_access();
		self::verify_nonce( 'almaden_learni_delete_lesson_' . $course_id );

		$lesson_id = isset( $_POST['lesson_id'] ) ? absint( $_POST['lesson_id'] ) : 0;
		if ( $lesson_id > 0 ) {
			$lesson_course = (int) get_post_meta( $lesson_id, Lesson::META_COURSE_ID, true );
			if ( $lesson_course === $course_id ) {
				$lesson_quiz_id = (int) get_post_meta( $lesson_id, Lesson::META_QUIZ_ID, true );
				if ( $lesson_quiz_id > 0 ) {
					QuizRepository::delete_quiz( $lesson_quiz_id );
				}

				wp_delete_post( $lesson_id, true );
			}
		}

		self::redirect_back( $course_id, 'lecciones', array( 'lesson_deleted' => '1' ) );
	}

	public static function handle_save_course_quiz(): void {
		$course_id = self::require_course_access();
		self::verify_nonce( 'almaden_learni_save_course_quiz_' . $course_id );

		$quiz_id = isset( $_POST['quiz_id'] ) ? absint( $_POST['quiz_id'] ) : 0;
		$title = isset( $_POST['quiz_title'] ) ? sanitize_text_field( wp_unslash( $_POST['quiz_title'] ) ) : '';
		$passing_score = isset( $_POST['passing_score'] ) ? absint( $_POST['passing_score'] ) : 80;
		$time_limit = isset( $_POST['time_limit_seconds'] ) ? absint( $_POST['time_limit_seconds'] ) : 0;
		$raw_questions = isset( $_POST['quiz_questions_json'] ) ? wp_unslash( $_POST['quiz_questions_json'] ) : '';
		$questions = json_decode( $raw_questions, true );

		if ( ! is_array( $questions ) ) {
			wp_die( esc_html__( 'El JSON del quiz no es válido.', 'almaden-bookster' ) );
		}

		$payload = array(
			'quiz_id' => $quiz_id,
			'title' => $title !== '' ? $title : get_the_title( $course_id ),
			'settings' => array(
				'course_id' => $course_id,
				'passing_percentage' => $passing_score,
				'time_limit' => $time_limit,
				'questionOrder' => 'in_order',
				'run_once' => 0,
				'force_solve' => 1,
				'show_points' => 0,
			),
			'questions' => $questions,
		);

		if ( $quiz_id > 0 && QuizRepository::quiz_exists( $quiz_id ) ) {
			$result = QuizEditor::save_quiz( $payload );
		} else {
			$result = QuizEditor::create_quiz( $payload );
		}

		if ( is_wp_error( $result ) ) {
			wp_die( esc_html( $result->get_error_message() ) );
		}

		$final_quiz_id = is_array( $result ) ? (int) ( $result['quiz_post_id'] ?? 0 ) : (int) $result;
		if ( $final_quiz_id > 0 ) {
			update_post_meta( $course_id, Course::META_QUIZ_ID, $final_quiz_id );
		}

		self::redirect_back( $course_id, 'evaluacion', array( 'quiz_saved' => '1' ) );
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_course_lessons( int $course_id ): array {
		$lessons = get_posts(
			array(
				'post_type'      => Lesson::POST_TYPE,
				'post_parent'    => $course_id,
				'posts_per_page' => -1,
				'orderby'        => 'menu_order ID',
				'order'          => 'ASC',
				'post_status'    => array( 'draft', 'pending', 'publish', 'private' ),
			)
		);

		$out = array();
		foreach ( $lessons as $lesson ) {
			$out[] = self::normalize_lesson( $lesson );
		}

		return $out;
	}

	public static function get_course_quiz( int $course_id ): array {
		$quiz_id = (int) get_post_meta( $course_id, Course::META_QUIZ_ID, true );
		if ( $quiz_id <= 0 ) {
			$quiz_id = QuizRepository::get_quiz_id_by_course( $course_id );
			if ( $quiz_id > 0 ) {
				update_post_meta( $course_id, Course::META_QUIZ_ID, $quiz_id );
			}
		}

		$quiz = $quiz_id > 0 ? QuizEditor::get_quiz_data( $quiz_id ) : null;

		return array(
			'quiz_id' => $quiz_id,
			'quiz' => is_array( $quiz ) ? $quiz : null,
			'questions_json' => self::serialize_questions_for_form( is_array( $quiz ) ? $quiz['questions'] ?? array() : array() ),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function get_editor_state( int $course_id, int $user_id ): array {
		$course = CreatorDashboard::get_selected_course_card( $course_id, $user_id );
		$post = CreatorDashboard::get_selected_course( $course_id, $user_id );

		return array(
			'course' => $course,
			'post' => $post,
			'lessons' => $post ? self::get_course_lessons( $course_id ) : array(),
			'quiz' => $post ? self::get_course_quiz( $course_id ) : array( 'quiz_id' => 0, 'quiz' => null, 'questions_json' => self::starter_questions_json() ),
		);
	}

	private static function normalize_lesson( \WP_Post $lesson ): array {
		return array(
			'id' => (int) $lesson->ID,
			'title' => get_the_title( $lesson->ID ),
			'content' => (string) $lesson->post_content,
			'video_url' => (string) get_post_meta( $lesson->ID, Lesson::META_VIDEO_URL, true ),
			'available_at' => (string) get_post_meta( $lesson->ID, Lesson::META_AVAILABLE_AT, true ),
			'menu_order' => (int) $lesson->menu_order,
			'quiz_id' => (int) get_post_meta( $lesson->ID, Lesson::META_QUIZ_ID, true ),
		);
	}

	private static function serialize_questions_for_form( array $questions ): string {
		return wp_json_encode( $questions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
	}

	private static function starter_questions_json(): string {
		return wp_json_encode(
			array(
				array(
					'title' => 'Pregunta 1',
					'question_text' => '',
					'answers' => array(
						array( 'text' => 'Respuesta 1', 'correct' => true ),
						array( 'text' => 'Respuesta 2', 'correct' => false ),
					),
				),
			),
			JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
		);
	}

	private static function require_course_access(): int {
		$course_id = isset( $_POST['course_id'] ) ? absint( $_POST['course_id'] ) : 0;
		if ( $course_id <= 0 ) {
			wp_die( esc_html__( 'Curso inválido.', 'almaden-bookster' ) );
		}

		$user_id = get_current_user_id();
		$post = CreatorDashboard::get_selected_course( $course_id, $user_id );
		if ( ! $post ) {
			wp_die( esc_html__( 'No tienes permisos para editar este curso.', 'almaden-bookster' ) );
		}

		return $course_id;
	}

	private static function verify_nonce( string $action ): void {
		$nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, $action ) ) {
			wp_die( esc_html__( 'Validación de seguridad fallida.', 'almaden-bookster' ) );
		}
	}

	private static function redirect_back( int $course_id, string $tab, array $query_args = array() ): void {
		$url = function_exists( 'almaden_bookster_get_course_creator_page_url' ) ? almaden_bookster_get_course_creator_page_url() : home_url( '/' );
		$args = array_merge(
			array(
				'course_id' => $course_id,
				'tab' => $tab,
			),
			$query_args
		);

		wp_safe_redirect( add_query_arg( $args, $url ) );
		exit;
	}
}
