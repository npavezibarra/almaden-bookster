<?php

namespace AlmadenBookster\Learni\Dashboard;

use AlmadenBookster\Learni\PostTypes\Course;
use AlmadenBookster\Learni\PostTypes\Lesson;
use AlmadenBookster\Learni\QuizEditor\QuizRepository;
use WP_Post;
use WP_Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CreatorDashboard {
	public static function init(): void {
		add_action( 'template_redirect', array( __CLASS__, 'maybe_render_dashboard' ), 5 );
		add_action( 'admin_post_almaden_learni_create_course', array( __CLASS__, 'handle_create_course' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	public static function maybe_render_dashboard(): void {
		if ( ! function_exists( 'almaden_bookster_get_course_creator_slug' ) ) {
			return;
		}

		if ( ! is_page( almaden_bookster_get_course_creator_slug() ) || ! is_main_query() ) {
			return;
		}

		if ( ! is_user_logged_in() ) {
			auth_redirect();
		}

		if ( ! self::current_user_can_access() ) {
			wp_die( esc_html__( 'No tienes permisos para acceder al creador de cursos.', 'almaden-bookster' ) );
		}

		show_admin_bar( false );

		$template_path = ALMADEN_BOOKSTER_LEARNI_PLUGIN_DIR . 'templates/dashboard/course-dashboard-app.php';
		if ( file_exists( $template_path ) ) {
			require_once $template_path;
			exit;
		}

		wp_die( esc_html__( 'Plantilla del creador de cursos no encontrada.', 'almaden-bookster' ) );
	}

	public static function enqueue_assets(): void {
		if ( ! function_exists( 'almaden_bookster_get_course_creator_slug' ) || ! is_page( almaden_bookster_get_course_creator_slug() ) ) {
			return;
		}

		$css_path = ALMADEN_BOOKSTER_LEARNI_PLUGIN_DIR . 'assets/dashboard/course-dashboard.css';
		$css_ver  = file_exists( $css_path ) ? (string) filemtime( $css_path ) : ALMADEN_BOOKSTER_LEARNI_VERSION;
		wp_enqueue_style( 'almaden-bookster-learni-dashboard', ALMADEN_BOOKSTER_LEARNI_PLUGIN_URL . 'assets/dashboard/course-dashboard.css', array(), $css_ver );

		$js_path = ALMADEN_BOOKSTER_LEARNI_PLUGIN_DIR . 'assets/dashboard/course-dashboard.js';
		$js_ver  = file_exists( $js_path ) ? (string) filemtime( $js_path ) : ALMADEN_BOOKSTER_LEARNI_VERSION;
		wp_enqueue_script( 'almaden-bookster-learni-dashboard', ALMADEN_BOOKSTER_LEARNI_PLUGIN_URL . 'assets/dashboard/course-dashboard.js', array(), $js_ver, true );
	}

	public static function handle_create_course(): void {
		if ( ! is_user_logged_in() ) {
			wp_die( esc_html__( 'Debes iniciar sesión para crear cursos.', 'almaden-bookster' ) );
		}

		if ( ! self::current_user_can_access() ) {
			wp_die( esc_html__( 'No tienes permisos para crear cursos.', 'almaden-bookster' ) );
		}

		$nonce_action = 'almaden_learni_create_course';
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), $nonce_action ) ) {
			wp_die( esc_html__( 'Validación de seguridad fallida.', 'almaden-bookster' ) );
		}

		$title = isset( $_POST['course_title'] ) ? sanitize_text_field( wp_unslash( $_POST['course_title'] ) ) : '';
		if ( $title === '' ) {
			$title = __( 'Nuevo curso', 'almaden-bookster' );
		}

		$description = isset( $_POST['course_description'] ) ? wp_kses_post( wp_unslash( $_POST['course_description'] ) ) : '';
		$course_id   = wp_insert_post(
			array(
				'post_author'  => get_current_user_id(),
				'post_title'   => $title,
				'post_content' => $description,
				'post_excerpt' => '',
				'post_status'  => 'draft',
				'post_type'    => Course::POST_TYPE,
				'meta_input'   => array(
					Course::META_LINEAR_ORDER => 1,
					Course::META_PAYMENT_MODE  => 'woocommerce',
				),
			)
		);

		if ( is_wp_error( $course_id ) || ! $course_id ) {
			wp_die( esc_html__( 'No se pudo crear el curso.', 'almaden-bookster' ) );
		}

		$redirect = almaden_bookster_get_course_creator_page_url(
			array(
				'course_id'      => (int) $course_id,
				'course_created'  => '1',
			)
		);

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_user_courses( int $user_id ): array {
		$query = new WP_Query(
			array(
				'post_type'      => Course::POST_TYPE,
				'post_status'    => array( 'draft', 'pending', 'publish', 'private', 'future' ),
				'author'         => $user_id,
				'posts_per_page' => 24,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		$courses = array();
		if ( $query->have_posts() ) {
			foreach ( $query->posts as $post ) {
				$courses[] = self::normalize_course_card( $post );
			}
		}

		return $courses;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_public_courses( int $posts_per_page = 24 ): array {
		$query = new WP_Query(
			array(
				'post_type'      => Course::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => $posts_per_page,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		$courses = array();
		if ( $query->have_posts() ) {
			foreach ( $query->posts as $post ) {
				$card = self::normalize_course_card( $post );
				$card['url'] = get_permalink( $post->ID );
				$card['author_name'] = get_the_author_meta( 'display_name', (int) $post->post_author );
				$card['published'] = get_the_date( get_option( 'date_format' ), $post->ID );
				$courses[] = $card;
			}
		}

		return $courses;
	}

	public static function get_selected_course( int $course_id, int $user_id ): ?WP_Post {
		$course_id = absint( $course_id );
		if ( $course_id <= 0 ) {
			return null;
		}

		$post = get_post( $course_id );
		if ( ! $post || Course::POST_TYPE !== $post->post_type ) {
			return null;
		}

		if ( (int) $post->post_author !== $user_id && ! current_user_can( 'manage_options' ) ) {
			return null;
		}

		return $post;
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public static function get_selected_course_card( int $course_id, int $user_id ): ?array {
		$post = self::get_selected_course( $course_id, $user_id );
		if ( ! $post ) {
			return null;
		}

		return self::normalize_course_card( $post );
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function normalize_course_card( WP_Post $post ): array {
		$course_id   = (int) $post->ID;
		$lesson_count = self::count_lessons_for_course( $course_id );
		$quiz_id     = class_exists( QuizRepository::class ) ? QuizRepository::get_quiz_id_by_course( $course_id ) : 0;
		$has_quiz    = $quiz_id > 0 && ( class_exists( QuizRepository::class ) ? QuizRepository::quiz_exists( $quiz_id ) : false );
		$status      = get_post_status( $course_id );
		$status_label = self::status_label( $status );
		$thumb      = get_the_post_thumbnail_url( $course_id, 'large' );
		$excerpt    = trim( wp_strip_all_tags( (string) $post->post_excerpt ) );
		if ( $excerpt === '' ) {
			$excerpt = wp_trim_words( wp_strip_all_tags( (string) $post->post_content ), 18 );
		}

		return array(
			'id'            => $course_id,
			'title'         => get_the_title( $course_id ),
			'url'           => get_edit_post_link( $course_id, 'raw' ),
			'status'        => $status,
			'status_label'  => $status_label,
			'lesson_count'  => $lesson_count,
			'quiz_id'       => $quiz_id,
			'has_quiz'      => $has_quiz,
			'thumbnail_url' => is_string( $thumb ) ? $thumb : '',
			'excerpt'       => $excerpt,
			'updated'       => get_the_modified_date( get_option( 'date_format' ), $course_id ),
		);
	}

	private static function count_lessons_for_course( int $course_id ): int {
		global $wpdb;

		$table = $wpdb->prefix . 'postmeta';
		$meta_key = Lesson::META_COURSE_ID;

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(pm.post_id)
				FROM {$table} pm
				INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				WHERE pm.meta_key = %s
					AND pm.meta_value = %d
					AND p.post_type = %s
					AND p.post_status NOT IN ('trash', 'auto-draft')",
				$meta_key,
				$course_id,
				Lesson::POST_TYPE
			)
		);
	}

	private static function status_label( string $status ): string {
		switch ( $status ) {
			case 'publish':
				return __( 'Publicado', 'almaden-bookster' );
			case 'draft':
				return __( 'Borrador', 'almaden-bookster' );
			case 'pending':
				return __( 'Pendiente', 'almaden-bookster' );
			case 'private':
				return __( 'Privado', 'almaden-bookster' );
			default:
				return ucfirst( $status );
		}
	}

	private static function current_user_can_access(): bool {
		return current_user_can( 'manage_options' )
			|| current_user_can( 'manage_almaden_learni' )
			|| current_user_can( 'edit_posts' );
	}
}
