<?php

namespace AlmadenBookster\BlogPost;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Template {
	public static function init(): void {
		add_filter( 'template_include', array( __CLASS__, 'template_include' ), 20 );
		add_filter( 'comments_template', array( __CLASS__, 'comments_template' ), 20 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_filter( 'body_class', array( __CLASS__, 'body_class' ) );
	}

	public static function template_include( string $template ): string {
		if ( is_admin() ) {
			return $template;
		}

		$custom_template = '';
		if ( is_singular( 'post' ) ) {
			$custom_template = ALMADEN_BOOKSTER_BLOG_POST_PLUGIN_DIR . 'templates/blog-post/single-post.php';
		} elseif ( is_home() || is_post_type_archive( 'post' ) ) {
			$custom_template = ALMADEN_BOOKSTER_BLOG_POST_PLUGIN_DIR . 'templates/blog-post/archive-blog.php';
		}

		if ( '' !== $custom_template && file_exists( $custom_template ) ) {
			return $custom_template;
		}

		return $template;
	}

	public static function comments_template( string $template ): string {
		if ( ! is_singular( 'post' ) || is_admin() ) {
			return $template;
		}

		$custom_template = ALMADEN_BOOKSTER_BLOG_POST_PLUGIN_DIR . 'templates/blog-post/comments.php';
		return file_exists( $custom_template ) ? $custom_template : $template;
	}

	public static function enqueue_assets(): void {
		if ( ! is_singular( 'post' ) && ! is_home() && ! is_post_type_archive( 'post' ) ) {
			return;
		}

		$css_file = is_singular( 'post' ) ? 'blog-post.css' : 'blog-archive.css';
		$css_path = ALMADEN_BOOKSTER_BLOG_POST_PLUGIN_DIR . 'assets/css/' . $css_file;
		$css_ver = file_exists( $css_path ) ? (string) filemtime( $css_path ) : '1.0.0';

		wp_enqueue_style(
			'almaden-blog-post',
			ALMADEN_BOOKSTER_BLOG_POST_PLUGIN_URL . 'assets/css/' . $css_file,
			array(),
			$css_ver
		);

		wp_enqueue_style(
			'almaden-blog-post-fonts',
			'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Newsreader:opsz,wght@6..72,200;6..72,300;6..72,400;6..72,500;6..72,600&family=Poppins:wght@400;500;600;700;800&display=swap',
			array(),
			null
		);

		if ( is_singular( 'post' ) && comments_open() && get_option( 'thread_comments' ) ) {
			wp_enqueue_script( 'comment-reply' );
		}
	}

	public static function body_class( array $classes ): array {
		if ( is_singular( 'post' ) ) {
			$classes[] = 'almaden-blog-post-body';
			$classes[] = has_post_thumbnail( get_queried_object_id() ) ? 'almaden-blog-post-has-image' : 'almaden-blog-post-no-image';
		}

		if ( is_home() || is_post_type_archive( 'post' ) ) {
			$classes[] = 'almaden-blog-archive-body';
		}

		return $classes;
	}
}

