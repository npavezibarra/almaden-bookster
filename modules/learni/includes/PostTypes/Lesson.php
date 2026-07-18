<?php

namespace AlmadenBookster\Learni\PostTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Lesson {
	public const POST_TYPE = 'almaden_learni_lesson';
	public const META_VIDEO_URL = 'almaden_learni_video_url';
	public const META_AVAILABLE_AT = 'almaden_learni_available_at';
	public const META_SOURCE_POST_ID = 'almaden_learni_source_post_id';
	public const META_COURSE_ID = 'almaden_learni_course_id';
	public const META_QUIZ_ID = 'almaden_learni_quiz_id';

	private static bool $did_register_meta = false;

	public static function register(): void {
		if ( ! post_type_exists( self::POST_TYPE ) ) {
			register_post_type(
				self::POST_TYPE,
				array(
					'labels'              => array(
						'name'          => __( 'Lessons', 'almaden-bookster' ),
						'singular_name' => __( 'Lesson', 'almaden-bookster' ),
					),
					'public'              => false,
					'publicly_queryable'   => true,
					'exclude_from_search'  => true,
					'show_ui'             => true,
					'show_in_rest'        => true,
					'supports'            => array( 'title', 'editor', 'thumbnail', 'revisions' ),
					'rewrite'             => false,
					'capability_type'     => array( 'almaden_learni_lesson', 'almaden_learni_lessons' ),
					'map_meta_cap'        => true,
				)
			);
		}

		if ( ! self::$did_register_meta ) {
			self::register_meta();
			self::$did_register_meta = true;
		}
	}

	private static function register_meta(): void {
		foreach (
			array(
				self::META_VIDEO_URL      => 'esc_url_raw',
				self::META_AVAILABLE_AT   => static function ( $value ) {
					return is_string( $value ) ? trim( $value ) : '';
				},
				self::META_SOURCE_POST_ID => 'absint',
				self::META_COURSE_ID      => 'absint',
				self::META_QUIZ_ID        => 'absint',
			) as $meta_key => $sanitize_callback
		) {
			register_post_meta(
				self::POST_TYPE,
				$meta_key,
				array(
					'type'              => in_array( $meta_key, array( self::META_VIDEO_URL, self::META_AVAILABLE_AT ), true ) ? 'string' : 'integer',
					'single'            => true,
					'show_in_rest'      => true,
					'default'           => in_array( $meta_key, array( self::META_VIDEO_URL, self::META_AVAILABLE_AT ), true ) ? '' : 0,
					'sanitize_callback' => $sanitize_callback,
					'auth_callback'     => static function ( $allowed, $meta_key, $post_id ) {
						return current_user_can( 'edit_post', (int) $post_id );
					},
				)
			);
		}
	}
}
