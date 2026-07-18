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
	}

	public static function maybe_upgrade(): void {
		$current = (int) get_option( self::OPTION_DB_VERSION, 0 );
		if ( $current >= (int) ALMADEN_BOOKSTER_LEARNI_DB_VERSION ) {
			return;
		}

		self::install_or_upgrade_schema();
		self::ensure_caps();
	}

	private static function install_or_upgrade_schema(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$prefix          = $wpdb->prefix;

		$tables = array(
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
			dbDelta( $sql );
		}

		update_option( self::OPTION_DB_VERSION, (int) ALMADEN_BOOKSTER_LEARNI_DB_VERSION, true );
	}

	private static function ensure_caps(): void {
		foreach ( array( 'administrator', 'editor' ) as $role_key ) {
			$role = get_role( $role_key );
			if ( ! $role ) {
				continue;
			}

			$role->add_cap( 'manage_almaden_learni' );

			foreach ( self::post_type_caps( Course::POST_TYPE, 'almaden_learni_courses' ) as $cap ) {
				$role->add_cap( $cap );
			}

			foreach ( self::post_type_caps( Lesson::POST_TYPE, 'almaden_learni_lessons' ) as $cap ) {
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
}

