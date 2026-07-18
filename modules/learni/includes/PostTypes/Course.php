<?php

namespace AlmadenBookster\Learni\PostTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Course {
	public const POST_TYPE = 'almaden_learni_course';
	public const META_PRICE = 'almaden_learni_price';
	public const META_LINEAR_ORDER = 'almaden_learni_linear_order';
	public const META_PAYMENT_MODE = 'almaden_learni_payment_mode';
	public const META_COVER_PHOTO_ID = 'almaden_learni_cover_photo_id';
	public const META_QUIZ_ID = 'almaden_learni_quiz_id';

	private static bool $did_register_meta = false;

	public static function register(): void {
		if ( ! post_type_exists( self::POST_TYPE ) ) {
			register_post_type(
				self::POST_TYPE,
				array(
					'labels'            => array(
						'name'          => __( 'Courses', 'almaden-bookster' ),
						'singular_name' => __( 'Course', 'almaden-bookster' ),
					),
					'public'            => true,
					'show_in_rest'      => true,
					'has_archive'       => true,
					'supports'          => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'page-attributes' ),
					'rewrite'           => array( 'slug' => 'courses', 'with_front' => false ),
					'capability_type'   => array( 'almaden_learni_course', 'almaden_learni_courses' ),
					'map_meta_cap'      => true,
				)
			);
		}

		if ( ! self::$did_register_meta ) {
			self::register_meta();
			self::$did_register_meta = true;
		}
	}

	private static function register_meta(): void {
		register_post_meta(
			self::POST_TYPE,
			self::META_PRICE,
			array(
				'type'              => 'number',
				'single'            => true,
				'show_in_rest'      => true,
				'default'           => 0,
				'sanitize_callback' => static function ( $value ) {
					return is_numeric( $value ) ? (float) $value : 0;
				},
				'auth_callback'     => static function ( $allowed, $meta_key, $post_id ) {
					return current_user_can( 'edit_post', (int) $post_id );
				},
			)
		);

		register_post_meta(
			self::POST_TYPE,
			self::META_LINEAR_ORDER,
			array(
				'type'              => 'boolean',
				'single'            => true,
				'show_in_rest'      => true,
				'default'           => true,
				'sanitize_callback' => static function ( $value ) {
					return (bool) $value;
				},
				'auth_callback'     => static function ( $allowed, $meta_key, $post_id ) {
					return current_user_can( 'edit_post', (int) $post_id );
				},
			)
		);

		register_post_meta(
			self::POST_TYPE,
			self::META_PAYMENT_MODE,
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'default'           => 'woocommerce',
				'sanitize_callback' => static function ( $value ) {
					$value = is_string( $value ) ? sanitize_key( $value ) : '';
					return in_array( $value, array( 'woocommerce', 'direct' ), true ) ? $value : 'woocommerce';
				},
				'auth_callback'     => static function ( $allowed, $meta_key, $post_id ) {
					return current_user_can( 'edit_post', (int) $post_id );
				},
			)
		);

		register_post_meta(
			self::POST_TYPE,
			self::META_COVER_PHOTO_ID,
			array(
				'type'              => 'integer',
				'single'            => true,
				'show_in_rest'      => true,
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'auth_callback'     => static function ( $allowed, $meta_key, $post_id ) {
					return current_user_can( 'edit_post', (int) $post_id );
				},
			)
		);

		register_post_meta(
			self::POST_TYPE,
			self::META_QUIZ_ID,
			array(
				'type'              => 'integer',
				'single'            => true,
				'show_in_rest'      => true,
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'auth_callback'     => static function ( $allowed, $meta_key, $post_id ) {
					return current_user_can( 'edit_post', (int) $post_id );
				},
			)
		);
	}
}
