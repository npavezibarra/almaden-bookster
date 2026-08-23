<?php
/**
 * Shared database schema helpers for Almaden Bookster.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'almaden_bookster_table_exists' ) ) {
	function almaden_bookster_table_exists( string $table_name ): bool {
		global $wpdb;

		$like = $wpdb->esc_like( $table_name );

		return $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $like ) ) === $table_name;
	}
}

if ( ! function_exists( 'almaden_bookster_maybe_install_table' ) ) {
	function almaden_bookster_maybe_install_table( string $table_name, string $sql, string $version_option = '', string $schema_version = '', bool $update_version = true ): bool {
		$table_exists = almaden_bookster_table_exists( $table_name );
		$current      = '' !== $version_option ? (string) get_option( $version_option, '' ) : '';

		if ( '' !== $version_option && '' !== $schema_version && $table_exists && $current === $schema_version ) {
			return false;
		}

		if ( '' === $version_option && $table_exists ) {
			return false;
		}

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		if ( $update_version && '' !== $version_option && '' !== $schema_version ) {
			update_option( $version_option, $schema_version, true );
		}

		return true;
	}
}

if ( ! function_exists( 'almaden_bookster_install_database_schema' ) ) {
	function almaden_bookster_install_database_schema(): void {
		if ( function_exists( 'almaden_bookster_create_book_authors_table' ) ) {
			almaden_bookster_create_book_authors_table();
		}

		if ( function_exists( 'almaden_bookster_create_settings_table' ) ) {
			almaden_bookster_create_settings_table();
		}

		if ( function_exists( 'almaden_bookster_create_highlights_table' ) ) {
			almaden_bookster_create_highlights_table();
		}

		if ( function_exists( 'almaden_bookster_create_highlight_comments_table' ) ) {
			almaden_bookster_create_highlight_comments_table();
		}

		if ( function_exists( 'almaden_bookster_create_quiz_progress_tables' ) ) {
			almaden_bookster_create_quiz_progress_tables();
		}

		if ( function_exists( 'almaden_bookster_create_chapter_progress_table' ) ) {
			almaden_bookster_create_chapter_progress_table();
		}

		if ( function_exists( 'almaden_bookster_create_fonts_table' ) ) {
			almaden_bookster_create_fonts_table();
		}

		if ( function_exists( 'almaden_bookster_create_publishers_tables' ) ) {
			almaden_bookster_create_publishers_tables();
		}

		if ( function_exists( 'almaden_bookster_create_book_sample_chapters_table' ) ) {
			almaden_bookster_create_book_sample_chapters_table();
		}
	}
}

add_action( 'plugins_loaded', 'almaden_bookster_install_database_schema', 8 );
