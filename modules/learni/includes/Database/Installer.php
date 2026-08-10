<?php

namespace AlmadenBookster\Learni\Database;

use AlmadenBookster\Learni\PostTypes\Course;
use AlmadenBookster\Learni\PostTypes\Lesson;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Installer {
	private const OPTION_DB_VERSION = 'almaden_bookster_learni_db_version';

	public static function activate(): void {
		self::install_or_upgrade_schema();
		self::ensure_caps();
		self::maybe_migrate_legacy_learni_data();
	}

	public static function maybe_upgrade(): void {
		$current = (int) get_option( self::OPTION_DB_VERSION, 0 );
		if ( $current >= (int) ALMADEN_BOOKSTER_LEARNI_DB_VERSION ) {
			self::maybe_migrate_legacy_learni_data();
			return;
		}

		self::install_or_upgrade_schema();
		self::ensure_caps();
		self::maybe_migrate_legacy_learni_data();
	}

	private static function install_or_upgrade_schema(): void {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$prefix          = $wpdb->prefix;

		$tables = array(
			"CREATE TABLE {$prefix}almaden_learni_course_items (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				course_post_id BIGINT UNSIGNED NOT NULL,
				item_type VARCHAR(32) NOT NULL,
				item_ref_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
				label VARCHAR(255) NOT NULL DEFAULT '',
				sort_order INT UNSIGNED NOT NULL DEFAULT 0,
				is_preview TINYINT(1) NOT NULL DEFAULT 0,
				created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
				PRIMARY KEY  (id),
				KEY course_post_id (course_post_id),
				KEY item_lookup (item_type, item_ref_id),
				KEY sort_order (sort_order)
			) {$charset_collate};",
			"CREATE TABLE {$prefix}almaden_learni_quizzes (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				course_post_id BIGINT UNSIGNED NOT NULL,
				lesson_post_id BIGINT UNSIGNED NULL,
				title VARCHAR(255) NOT NULL,
				passing_score INT UNSIGNED NOT NULL DEFAULT 80,
				time_limit_seconds INT UNSIGNED NOT NULL DEFAULT 0,
				settings_json LONGTEXT NULL,
				created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
				PRIMARY KEY  (id),
				KEY course_post_id (course_post_id),
				KEY lesson_post_id (lesson_post_id)
			) {$charset_collate};",
			"CREATE TABLE {$prefix}almaden_learni_quiz_questions (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				quiz_id BIGINT UNSIGNED NOT NULL,
				type VARCHAR(32) NOT NULL DEFAULT 'single',
				prompt LONGTEXT NOT NULL,
				explanation LONGTEXT NULL,
				points INT UNSIGNED NOT NULL DEFAULT 1,
				sort_order INT UNSIGNED NOT NULL DEFAULT 0,
				meta_json LONGTEXT NULL,
				PRIMARY KEY  (id),
				KEY quiz_id (quiz_id)
			) {$charset_collate};",
			"CREATE TABLE {$prefix}almaden_learni_quiz_answers (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				question_id BIGINT UNSIGNED NOT NULL,
				answer_text LONGTEXT NOT NULL,
				is_correct TINYINT(1) NOT NULL DEFAULT 0,
				sort_order INT UNSIGNED NOT NULL DEFAULT 0,
				meta_json LONGTEXT NULL,
				PRIMARY KEY  (id),
				KEY question_id (question_id)
			) {$charset_collate};",
		);

		foreach ( $tables as $sql ) {
			$table_name = self::extract_table_name( $sql );
			if ( '' === $table_name ) {
				continue;
			}

			almaden_bookster_maybe_install_table( $table_name, $sql, self::OPTION_DB_VERSION, (string) ALMADEN_BOOKSTER_LEARNI_DB_VERSION, false );
		}

		self::migrate_post_types();
		update_option( self::OPTION_DB_VERSION, (int) ALMADEN_BOOKSTER_LEARNI_DB_VERSION, true );
	}

	private static function extract_table_name( string $sql ): string {
		if ( preg_match( '/CREATE TABLE\s+`?([A-Za-z0-9_]+)`?/i', $sql, $matches ) ) {
			return $matches[1];
		}

		return '';
	}

	private static function maybe_migrate_legacy_learni_data(): void {
		if ( '1' === (string) get_option( 'almaden_bookster_learni_legacy_migrated', '0' ) ) {
			return;
		}

		global $wpdb;

		$legacy_quiz_table     = $wpdb->prefix . 'learni_quizzes';
		$legacy_question_table = $wpdb->prefix . 'learni_quiz_questions';
		$legacy_answer_table   = $wpdb->prefix . 'learni_quiz_answers';
		$legacy_items_table    = $wpdb->prefix . 'learni_course_items';
		$legacy_course_type    = 'learni_course';
		$legacy_lesson_type    = 'learni_lesson';

		$has_legacy_courses = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(1) FROM {$wpdb->posts} WHERE post_type = %s", $legacy_course_type ) ) > 0;
		$has_legacy_lessons  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(1) FROM {$wpdb->posts} WHERE post_type = %s", $legacy_lesson_type ) ) > 0;
		$has_legacy_quizzes  = self::table_exists( $legacy_quiz_table ) && (int) $wpdb->get_var( "SELECT COUNT(1) FROM {$legacy_quiz_table}" ) > 0;
		$has_legacy_items    = self::table_exists( $legacy_items_table ) && (int) $wpdb->get_var( "SELECT COUNT(1) FROM {$legacy_items_table}" ) > 0;

		if ( ! $has_legacy_courses && ! $has_legacy_lessons && ! $has_legacy_quizzes && ! $has_legacy_items ) {
			update_option( 'almaden_bookster_learni_legacy_migrated', '1', true );
			return;
		}

		self::migrate_legacy_post_types( $legacy_course_type, $legacy_lesson_type );
		self::migrate_legacy_course_meta();
		self::migrate_legacy_course_items( $legacy_items_table );
		self::migrate_legacy_quizzes( $legacy_quiz_table, $legacy_question_table, $legacy_answer_table );

		update_option( 'almaden_bookster_learni_legacy_migrated', '1', true );
	}

	private static function migrate_legacy_post_types( string $legacy_course_type, string $legacy_lesson_type ): void {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->posts} SET post_type = %s WHERE post_type = %s",
				Course::POST_TYPE,
				$legacy_course_type
			)
		);

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->posts} SET post_type = %s WHERE post_type = %s",
				Lesson::POST_TYPE,
				$legacy_lesson_type
			)
		);
	}

	private static function migrate_legacy_course_meta(): void {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->postmeta} SET meta_key = %s WHERE meta_key = %s",
				Course::META_LINEAR_ORDER,
				'learni_linear_order'
			)
		);

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->postmeta} SET meta_key = %s WHERE meta_key = %s",
				Course::META_PAYMENT_MODE,
				'learni_access_mode'
			)
		);

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->postmeta} SET meta_value = %s WHERE meta_key = %s AND meta_value = %s",
				'direct',
				Course::META_PAYMENT_MODE,
				'private'
			)
		);

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->postmeta} SET meta_value = %s WHERE meta_key = %s AND meta_value = %s",
				'woocommerce',
				Course::META_PAYMENT_MODE,
				'public'
			)
		);

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->postmeta} SET meta_key = %s WHERE meta_key = %s",
				Course::META_QUIZ_ID,
				'learni_quiz_id'
			)
		);

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->postmeta} SET meta_key = %s WHERE meta_key = %s",
				Lesson::META_SOURCE_POST_ID,
				'learni_source_post_id'
			)
		);

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->postmeta} SET meta_key = %s WHERE meta_key = %s",
				Lesson::META_QUIZ_ID,
				'learni_quiz_id'
			)
		);
	}

	private static function migrate_legacy_course_items( string $legacy_items_table ): void {
		global $wpdb;
		$target_table = $wpdb->prefix . 'almaden_learni_course_items';

		if ( ! self::table_exists( $legacy_items_table ) ) {
			return;
		}

		$rows = $wpdb->get_results( "SELECT course_post_id, item_type, item_ref_id, label, sort_order, is_preview, created_at FROM {$legacy_items_table}", ARRAY_A );
		if ( empty( $rows ) ) {
			return;
		}

		foreach ( (array) $rows as $row ) {
			$wpdb->insert(
				$target_table,
				array(
					'course_post_id' => (int) ( $row['course_post_id'] ?? 0 ),
					'item_type'      => (string) ( $row['item_type'] ?? '' ),
					'item_ref_id'    => (int) ( $row['item_ref_id'] ?? 0 ),
					'label'          => (string) ( $row['label'] ?? '' ),
					'sort_order'     => (int) ( $row['sort_order'] ?? 0 ),
					'is_preview'     => ! empty( $row['is_preview'] ) ? 1 : 0,
					'created_at'     => (string) ( $row['created_at'] ?? current_time( 'mysql' ) ),
				),
				array( '%d', '%s', '%d', '%s', '%d', '%d', '%s' )
			);
		}
	}

	private static function migrate_legacy_quizzes( string $legacy_quiz_table, string $legacy_question_table, string $legacy_answer_table ): void {
		global $wpdb;
		if ( ! self::table_exists( $legacy_quiz_table ) || ! self::table_exists( $legacy_question_table ) || ! self::table_exists( $legacy_answer_table ) ) {
			return;
		}

		$new_quiz_table     = $wpdb->prefix . 'almaden_learni_quizzes';
		$new_question_table = $wpdb->prefix . 'almaden_learni_quiz_questions';
		$new_answer_table   = $wpdb->prefix . 'almaden_learni_quiz_answers';

		$quiz_rows = $wpdb->get_results( "SELECT id, course_post_id, lesson_post_id, title, passing_score, time_limit_seconds, settings_json, created_at FROM {$legacy_quiz_table}", ARRAY_A );
		if ( empty( $quiz_rows ) ) {
			return;
		}

		foreach ( (array) $quiz_rows as $quiz_row ) {
			$old_quiz_id = (int) ( $quiz_row['id'] ?? 0 );
			if ( $old_quiz_id <= 0 ) {
				continue;
			}

			$wpdb->insert(
				$new_quiz_table,
				array(
					'course_post_id'    => (int) ( $quiz_row['course_post_id'] ?? 0 ),
					'lesson_post_id'    => isset( $quiz_row['lesson_post_id'] ) ? absint( $quiz_row['lesson_post_id'] ) : null,
					'title'             => (string) ( $quiz_row['title'] ?? '' ),
					'passing_score'     => (int) ( $quiz_row['passing_score'] ?? 80 ),
					'time_limit_seconds'=> (int) ( $quiz_row['time_limit_seconds'] ?? 0 ),
					'settings_json'     => (string) ( $quiz_row['settings_json'] ?? '' ),
					'created_at'        => (string) ( $quiz_row['created_at'] ?? current_time( 'mysql' ) ),
				),
				array( '%d', '%d', '%s', '%d', '%d', '%s', '%s' )
			);

			$new_quiz_id = (int) $wpdb->insert_id;
			if ( $new_quiz_id <= 0 ) {
				continue;
			}

			$question_rows = $wpdb->get_results( $wpdb->prepare( "SELECT id, prompt, sort_order, meta_json FROM {$legacy_question_table} WHERE quiz_id = %d ORDER BY sort_order ASC, id ASC", $old_quiz_id ), ARRAY_A );
			$question_id_map = array();
			foreach ( (array) $question_rows as $question_row ) {
				$old_question_id = (int) ( $question_row['id'] ?? 0 );
				if ( $old_question_id <= 0 ) {
					continue;
				}

				$wpdb->insert(
					$new_question_table,
					array(
						'quiz_id'     => $new_quiz_id,
						'type'        => 'single',
						'prompt'      => (string) ( $question_row['prompt'] ?? '' ),
						'explanation' => null,
						'points'      => 1,
						'sort_order'  => (int) ( $question_row['sort_order'] ?? 0 ),
						'meta_json'   => (string) ( $question_row['meta_json'] ?? '' ),
					),
					array( '%d', '%s', '%s', '%s', '%d', '%d', '%s' )
				);

				$new_question_id = (int) $wpdb->insert_id;
				if ( $new_question_id > 0 ) {
					$question_id_map[ $old_question_id ] = $new_question_id;
				}
			}

			foreach ( $question_id_map as $old_question_id => $new_question_id ) {
				$answer_rows = $wpdb->get_results( $wpdb->prepare( "SELECT answer_text, is_correct, sort_order, meta_json FROM {$legacy_answer_table} WHERE question_id = %d ORDER BY sort_order ASC, id ASC", $old_question_id ), ARRAY_A );
				foreach ( (array) $answer_rows as $answer_row ) {
					$wpdb->insert(
						$new_answer_table,
						array(
							'question_id'  => $new_question_id,
							'answer_text'  => (string) ( $answer_row['answer_text'] ?? '' ),
							'is_correct'   => ! empty( $answer_row['is_correct'] ) ? 1 : 0,
							'sort_order'   => (int) ( $answer_row['sort_order'] ?? 0 ),
							'meta_json'    => (string) ( $answer_row['meta_json'] ?? '' ),
						),
						array( '%d', '%s', '%d', '%d', '%s' )
					);
				}
			}

			self::update_legacy_quiz_references( $old_quiz_id, $new_quiz_id );
		}
	}

	private static function update_legacy_quiz_references( int $old_quiz_id, int $new_quiz_id ): void {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->postmeta}
				SET meta_value = %d
				WHERE meta_key = %s AND meta_value = %s",
				$new_quiz_id,
				Course::META_QUIZ_ID,
				(string) $old_quiz_id
			)
		);

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->postmeta}
				SET meta_value = %d
				WHERE meta_key = %s AND meta_value = %s",
				$new_quiz_id,
				Lesson::META_QUIZ_ID,
				(string) $old_quiz_id
			)
		);
	}

	private static function table_exists( string $table_name ): bool {
		return almaden_bookster_table_exists( $table_name );
	}

	private static function ensure_caps(): void {
		global $wp_roles;

		if ( ! isset( $wp_roles ) || ! $wp_roles instanceof \WP_Roles ) {
			$wp_roles = wp_roles();
		}

		if ( ! $wp_roles instanceof \WP_Roles ) {
			return;
		}

		$course_caps = self::post_type_caps( Course::POST_TYPE, 'almaden_learni_courses' );
		$lesson_caps = self::post_type_caps( Lesson::POST_TYPE, 'almaden_learni_lessons' );

		foreach ( $wp_roles->roles as $role_key => $role_data ) {
			$role = get_role( $role_key );
			if ( ! $role ) {
				continue;
			}

			$can_edit_posts = ! empty( $role_data['capabilities']['edit_posts'] ) || ! empty( $role_data['capabilities']['manage_options'] );
			if ( ! $can_edit_posts ) {
				continue;
			}

			$role->add_cap( 'manage_almaden_learni' );

			foreach ( $course_caps as $cap ) {
				$role->add_cap( $cap );
			}

			foreach ( $lesson_caps as $cap ) {
				$role->add_cap( $cap );
			}
		}
	}

	private static function post_type_caps( string $singular, string $plural ): array {
		return array(
			"edit_{$singular}",
			"read_{$singular}",
			"delete_{$singular}",
			"edit_{$plural}",
			"edit_others_{$plural}",
			"delete_{$plural}",
			"publish_{$plural}",
			"read_private_{$plural}",
		);
	}

	private static function migrate_post_types(): void {
		global $wpdb;

		$migrations = array(
			'almaden_learni_course' => Course::POST_TYPE,
			'almaden_learni_lesson' => Lesson::POST_TYPE,
		);

		foreach ( $migrations as $old_type => $new_type ) {
			if ( $old_type === $new_type ) {
				continue;
			}

			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->posts} SET post_type = %s WHERE post_type = %s",
					$new_type,
					$old_type
				)
			);
		}
	}
}
