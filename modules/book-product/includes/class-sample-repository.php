<?php
namespace AlmadenBookster\BookProduct;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Persistence and authorization helpers for public sample chapters.
 *
 * A junction table is used instead of serialized post meta so reads remain
 * index-backed when the catalogue contains many books and chapters.
 */
final class Sample_Repository {
	const VERSION_OPTION = 'almaden_bookster_sample_chapters_db_version';
	const VERSION        = '1.0.0';

	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . 'almaden_book_sample_chapters';
	}

	public static function install() {
		global $wpdb;
		$table   = self::table_name();
		$charset = $wpdb->get_charset_collate();
		$sql     = "CREATE TABLE {$table} (
			book_id bigint(20) unsigned NOT NULL,
			chapter_id bigint(20) unsigned NOT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (book_id, chapter_id),
			KEY chapter_id (chapter_id)
		) {$charset};";

		if ( function_exists( 'almaden_bookster_maybe_install_table' ) ) {
			almaden_bookster_maybe_install_table( $table, $sql, self::VERSION_OPTION, self::VERSION );
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
		update_option( self::VERSION_OPTION, self::VERSION, true );
	}

	public static function get_ids( $book_id ) {
		global $wpdb;
		$book_id = absint( $book_id );
		if ( ! $book_id ) {
			return array();
		}

		return array_map(
			'absint',
			(array) $wpdb->get_col(
				$wpdb->prepare( 'SELECT chapter_id FROM ' . self::table_name() . ' WHERE book_id = %d', $book_id )
			)
		);
	}

	public static function has_samples( $book_id ) {
		global $wpdb;
		return (bool) $wpdb->get_var(
			$wpdb->prepare( 'SELECT 1 FROM ' . self::table_name() . ' WHERE book_id = %d LIMIT 1', absint( $book_id ) )
		);
	}

	public static function is_sample( $book_id, $chapter_id ) {
		global $wpdb;
		return (bool) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT 1 FROM ' . self::table_name() . ' WHERE book_id = %d AND chapter_id = %d LIMIT 1',
				absint( $book_id ),
				absint( $chapter_id )
			)
		);
	}

	public static function chapters( $book_id ) {
		$source_book_id = absint( get_post_meta( $book_id, '_almaden_source_book_id', true ) );
		$source_book_id = $source_book_id ? $source_book_id : absint( $book_id );
		$selected       = array_flip( self::get_ids( $book_id ) );
		$posts          = get_posts(
			array(
				'post_type'      => 'book_chapter',
				'post_parent'    => $source_book_id,
				'post_status'    => array( 'publish', 'draft', 'private', 'pending' ),
				'posts_per_page' => -1,
				'orderby'        => 'menu_order ID',
				'order'          => 'ASC',
			)
		);

		$result = array();
		foreach ( $posts as $chapter ) {
			if ( '1' === (string) get_post_meta( $chapter->ID, '_is_toc', true ) || '1' === (string) get_post_meta( $chapter->ID, '_is_credits', true ) ) {
				continue;
			}
			$result[] = array(
				'id'       => (int) $chapter->ID,
				'title'    => get_the_title( $chapter ),
				'selected' => isset( $selected[ (int) $chapter->ID ] ),
			);
		}

		return $result;
	}

	public static function save( $book_id, $chapter_ids ) {
		global $wpdb;
		$book_id     = absint( $book_id );
		$allowed_ids = wp_list_pluck( self::chapters( $book_id ), 'id' );
		$chapter_ids = array_values( array_intersect( array_unique( array_map( 'absint', (array) $chapter_ids ) ), $allowed_ids ) );
		$current_ids = self::get_ids( $book_id );
		$remove_ids  = array_diff( $current_ids, $chapter_ids );
		$add_ids     = array_diff( $chapter_ids, $current_ids );

		if ( $remove_ids ) {
			$placeholders = implode( ',', array_fill( 0, count( $remove_ids ), '%d' ) );
			$wpdb->query(
				$wpdb->prepare(
					'DELETE FROM ' . self::table_name() . " WHERE book_id = %d AND chapter_id IN ({$placeholders})",
					array_merge( array( $book_id ), array_values( $remove_ids ) )
				)
			);
		}

		$now = current_time( 'mysql', true );
		foreach ( $add_ids as $chapter_id ) {
			$wpdb->query(
				$wpdb->prepare(
					'INSERT IGNORE INTO ' . self::table_name() . ' (book_id, chapter_id, created_at) VALUES (%d, %d, %s)',
					$book_id,
					$chapter_id,
					$now
				)
			);
		}

		return self::chapters( $book_id );
	}
}
