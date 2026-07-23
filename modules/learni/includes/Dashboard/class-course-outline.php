<?php

namespace AlmadenBookster\Learni\Dashboard;

use AlmadenBookster\Learni\PostTypes\Lesson;
use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CourseOutline {
	private const TABLE = 'almaden_learni_course_items';

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_items( int $course_id ): array {
		if ( $course_id <= 0 ) {
			return array();
		}

		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, course_post_id, item_type, item_ref_id, label, sort_order, is_preview
				FROM {$table}
				WHERE course_post_id = %d
				ORDER BY sort_order ASC, id ASC",
				$course_id
			),
			ARRAY_A
		);

		if ( empty( $rows ) ) {
			self::seed_from_lessons( $course_id );
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT id, course_post_id, item_type, item_ref_id, label, sort_order, is_preview
					FROM {$table}
					WHERE course_post_id = %d
					ORDER BY sort_order ASC, id ASC",
					$course_id
				),
				ARRAY_A
			);
		}

		$items = array();
		foreach ( (array) $rows as $row ) {
			$type = isset( $row['item_type'] ) ? (string) $row['item_type'] : '';
			if ( ! in_array( $type, array( 'lesson', 'section' ), true ) ) {
				continue;
			}

			$item = array(
				'id'         => (int) ( $row['id'] ?? 0 ),
				'course_id'  => (int) ( $row['course_post_id'] ?? 0 ),
				'type'       => $type,
				'ref_id'     => (int) ( $row['item_ref_id'] ?? 0 ),
				'label'      => (string) ( $row['label'] ?? '' ),
				'sort_order' => (int) ( $row['sort_order'] ?? 0 ),
				'is_preview' => ! empty( $row['is_preview'] ),
			);

			if ( 'lesson' === $type ) {
				$lesson = get_post( $item['ref_id'] );
				if ( $lesson instanceof WP_Post && Lesson::POST_TYPE === $lesson->post_type ) {
					$item['lesson'] = array(
						'id'            => (int) $lesson->ID,
						'title'         => get_the_title( $lesson->ID ),
						'content'       => (string) $lesson->post_content,
						'video_url'     => (string) get_post_meta( $lesson->ID, Lesson::META_VIDEO_URL, true ),
						'available_at'  => (string) get_post_meta( $lesson->ID, Lesson::META_AVAILABLE_AT, true ),
						'menu_order'    => (int) $lesson->menu_order,
					);
				}
			}

			$items[] = $item;
		}

		return $items;
	}

	public static function sync_lesson( int $course_id, int $lesson_id, string $label = '' ): void {
		if ( $course_id <= 0 || $lesson_id <= 0 ) {
			return;
		}

		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;

		$current = self::get_item_by_ref( $course_id, 'lesson', $lesson_id );
		if ( $label === '' ) {
			$label = get_the_title( $lesson_id );
		}

		if ( $current ) {
			$wpdb->update(
				$table,
				array( 'label' => $label ),
				array( 'id' => (int) $current['id'] ),
				array( '%s' ),
				array( '%d' )
			);
			return;
		}

		$wpdb->insert(
			$table,
			array(
				'course_post_id' => $course_id,
				'item_type'      => 'lesson',
				'item_ref_id'    => $lesson_id,
				'label'          => $label,
				'sort_order'     => self::next_sort_order( $course_id ),
				'is_preview'     => 0,
			),
			array( '%d', '%s', '%d', '%s', '%d', '%d' )
		);
	}

	public static function delete_lesson( int $course_id, int $lesson_id ): void {
		if ( $course_id <= 0 || $lesson_id <= 0 ) {
			return;
		}

		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;

		$wpdb->delete(
			$table,
			array(
				'course_post_id' => $course_id,
				'item_type'      => 'lesson',
				'item_ref_id'    => $lesson_id,
			),
			array( '%d', '%s', '%d' )
		);
	}

	public static function create_section( int $course_id, string $label ): int {
		if ( $course_id <= 0 ) {
			return 0;
		}

		$label = sanitize_text_field( $label );
		if ( '' === $label ) {
			$label = __( 'Sección', 'almaden-bookster' );
		}

		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;
		$wpdb->insert(
			$table,
			array(
				'course_post_id' => $course_id,
				'item_type'      => 'section',
				'item_ref_id'    => 0,
				'label'          => $label,
				'sort_order'     => self::next_sort_order( $course_id ),
				'is_preview'     => 0,
			),
			array( '%d', '%s', '%d', '%s', '%d', '%d' )
		);

		return (int) $wpdb->insert_id;
	}

	public static function save_section( int $course_id, int $item_id, string $label ): bool {
		if ( $course_id <= 0 || $item_id <= 0 ) {
			return false;
		}

		$label = sanitize_text_field( $label );
		if ( '' === $label ) {
			$label = __( 'Sección', 'almaden-bookster' );
		}

		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;

		return false !== $wpdb->update(
			$table,
			array( 'label' => $label ),
			array( 'id' => $item_id, 'course_post_id' => $course_id, 'item_type' => 'section' ),
			array( '%s' ),
			array( '%d', '%d', '%s' )
		);
	}

	public static function delete_section( int $course_id, int $item_id ): void {
		if ( $course_id <= 0 || $item_id <= 0 ) {
			return;
		}

		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;

		$wpdb->delete(
			$table,
			array(
				'id'             => $item_id,
				'course_post_id' => $course_id,
				'item_type'      => 'section',
			),
			array( '%d', '%d', '%s' )
		);
	}

	/**
	 * @param array<int, array<string, mixed>> $items
	 */
	public static function reorder( int $course_id, array $items ): void {
		if ( $course_id <= 0 || empty( $items ) ) {
			return;
		}

		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;
		$lesson_order = 0;

		foreach ( array_values( $items ) as $index => $item ) {
			$item_id = isset( $item['id'] ) ? absint( $item['id'] ) : 0;
			$type = isset( $item['type'] ) ? sanitize_key( (string) $item['type'] ) : '';
			$label = isset( $item['label'] ) ? sanitize_text_field( (string) $item['label'] ) : '';

			if ( $item_id <= 0 || ! in_array( $type, array( 'lesson', 'section' ), true ) ) {
				continue;
			}

			$update = array(
				'sort_order' => (int) $index,
			);
			$formats = array( '%d' );

			if ( 'section' === $type ) {
				if ( '' !== $label ) {
					$update['label'] = $label;
					$formats[] = '%s';
				}

				$wpdb->update(
					$table,
					$update,
					array( 'id' => $item_id, 'course_post_id' => $course_id, 'item_type' => 'section' ),
					$formats,
					array( '%d', '%d', '%s' )
				);
				continue;
			}

			$row = self::get_item_by_ref( $course_id, 'lesson', $item_id );
			if ( ! $row ) {
				continue;
			}

			$row_id = (int) ( $row['id'] ?? 0 );
			if ( $row_id <= 0 ) {
				continue;
			}

			$wpdb->update(
				$table,
				array(
					'sort_order' => (int) $index,
					'label'      => $label !== '' ? $label : get_the_title( $item_id ),
				),
				array( 'id' => $row_id, 'course_post_id' => $course_id, 'item_type' => 'lesson' ),
				array( '%d', '%s' ),
				array( '%d', '%d', '%s' )
			);

			wp_update_post(
				array(
					'ID'         => $item_id,
					'menu_order' => (int) $lesson_order,
				)
			);
			$lesson_order++;
		}
	}

	/**
	 * @return array<string, mixed>|null
	 */
	private static function get_item_by_ref( int $course_id, string $type, int $ref_id ): ?array {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id, course_post_id, item_type, item_ref_id, label, sort_order, is_preview
				FROM {$table}
				WHERE course_post_id = %d AND item_type = %s AND item_ref_id = %d
				LIMIT 1",
				$course_id,
				$type,
				$ref_id
			),
			ARRAY_A
		);

		return is_array( $row ) ? $row : null;
	}

	private static function next_sort_order( int $course_id ): int {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COALESCE(MAX(sort_order), -1) + 1 FROM {$table} WHERE course_post_id = %d",
				$course_id
			)
		);
	}

	private static function seed_from_lessons( int $course_id ): void {
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

		if ( empty( $lessons ) ) {
			return;
		}

		foreach ( $lessons as $index => $lesson ) {
			self::sync_lesson( $course_id, (int) $lesson->ID, get_the_title( $lesson->ID ) );
			self::set_item_sort_order_for_lesson( $course_id, (int) $lesson->ID, (int) $index );
		}
	}

	private static function set_item_sort_order_for_lesson( int $course_id, int $lesson_id, int $sort_order ): void {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;

		$wpdb->update(
			$table,
			array( 'sort_order' => $sort_order ),
			array( 'course_post_id' => $course_id, 'item_type' => 'lesson', 'item_ref_id' => $lesson_id ),
			array( '%d' ),
			array( '%d', '%s', '%d' )
		);
	}
}
