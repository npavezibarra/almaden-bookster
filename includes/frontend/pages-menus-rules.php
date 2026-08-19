<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'almaden_bookster_get_shell_navigation_exclusion_rules' ) ) {
	function almaden_bookster_get_shell_navigation_exclusion_rules() {
		$rules = array();

		$add_rule = static function( $key, $slug, $label, $page_id = 0 ) use ( &$rules ) {
			$slug    = trim( (string) $slug, '/' );
			$label   = trim( (string) $label );
			$page_id = absint( $page_id );

			if ( $page_id <= 0 || 'page' !== get_post_type( $page_id ) ) {
				return;
			}

			$rules[] = array(
				'key'     => sanitize_key( (string) $key ),
				'slug'    => $slug,
				'label'   => $label,
				'page_id' => $page_id,
				'url'     => '' !== $slug ? home_url( '/' . $slug . '/' ) : '',
			);
		};

		$add_rule(
			'creator',
			function_exists( 'almaden_bookster_get_creator_slug' ) ? almaden_bookster_get_creator_slug() : 'almaden-booklist',
			function_exists( 'almaden_bookster_get_creator_title' ) ? almaden_bookster_get_creator_title() : 'Taller',
			function_exists( 'almaden_bookster_get_creator_page_id' ) ? almaden_bookster_get_creator_page_id() : 0
		);
		if ( ! function_exists( 'almaden_bookster_is_shell_home_menu_enabled' ) || ! almaden_bookster_is_shell_home_menu_enabled() ) {
			$add_rule(
				'shell_home',
				function_exists( 'almaden_bookster_get_shell_home_slug' ) ? almaden_bookster_get_shell_home_slug() : 'almaden-home',
				function_exists( 'almaden_bookster_get_shell_home_title' ) ? almaden_bookster_get_shell_home_title() : 'Almaden App',
				function_exists( 'almaden_bookster_get_shell_home_page_id' ) ? almaden_bookster_get_shell_home_page_id() : 0
			);
		}
		$add_rule(
			'dashboard',
			function_exists( 'almaden_bookster_get_dashboard_slug' ) ? almaden_bookster_get_dashboard_slug() : 'dashboard',
			function_exists( 'almaden_bookster_get_dashboard_title' ) ? almaden_bookster_get_dashboard_title() : 'Dashboard',
			function_exists( 'almaden_bookster_get_dashboard_page_id' ) ? almaden_bookster_get_dashboard_page_id() : 0
		);
		$add_rule(
			'reading_stats',
			function_exists( 'almaden_bookster_get_reading_stats_slug' ) ? almaden_bookster_get_reading_stats_slug() : 'my-reading-stats',
			function_exists( 'almaden_bookster_get_reading_stats_title' ) ? almaden_bookster_get_reading_stats_title() : 'My Reading Stats',
			function_exists( 'almaden_bookster_get_reading_stats_page_id' ) ? almaden_bookster_get_reading_stats_page_id() : 0
		);
		$add_rule(
			'course_creator',
			function_exists( 'almaden_bookster_get_course_creator_slug' ) ? almaden_bookster_get_course_creator_slug() : 'sala-de-clases',
			function_exists( 'almaden_bookster_get_course_creator_title' ) ? almaden_bookster_get_course_creator_title() : 'Sala de clases',
			function_exists( 'almaden_bookster_get_course_creator_page_id' ) ? almaden_bookster_get_course_creator_page_id() : 0
		);
		$add_rule(
			'course_archive',
			function_exists( 'almaden_bookster_get_course_archive_slug' ) ? almaden_bookster_get_course_archive_slug() : 'almaden-cursos',
			function_exists( 'almaden_bookster_get_course_archive_title' ) ? almaden_bookster_get_course_archive_title() : 'Cursos',
			function_exists( 'almaden_bookster_get_course_archive_page_id' ) ? almaden_bookster_get_course_archive_page_id() : 0
		);
		$add_rule(
			'blog_creator',
			function_exists( 'almaden_bookster_get_blog_creator_slug' ) ? almaden_bookster_get_blog_creator_slug() : 'blog-editor',
			function_exists( 'almaden_bookster_get_blog_creator_title' ) ? almaden_bookster_get_blog_creator_title() : 'Blog',
			function_exists( 'almaden_bookster_get_blog_creator_page_id' ) ? almaden_bookster_get_blog_creator_page_id() : 0
		);
		$add_rule(
			'authors',
			function_exists( 'almaden_bookster_get_authors_slug' ) ? almaden_bookster_get_authors_slug() : 'autores',
			function_exists( 'almaden_bookster_get_authors_title' ) ? almaden_bookster_get_authors_title() : 'Autores',
			function_exists( 'almaden_bookster_get_authors_page_id' ) ? almaden_bookster_get_authors_page_id() : 0
		);
		$add_rule(
			'author',
			function_exists( 'almaden_bookster_get_author_page_slug' ) ? almaden_bookster_get_author_page_slug() : 'autor',
			function_exists( 'almaden_bookster_get_author_page_title' ) ? almaden_bookster_get_author_page_title() : 'Autor',
			function_exists( 'almaden_bookster_get_author_page_id' ) ? almaden_bookster_get_author_page_id() : 0
		);
		$add_rule(
			'publisher',
			function_exists( 'almaden_bookster_get_publisher_page_slug' ) ? almaden_bookster_get_publisher_page_slug() : 'editorial',
			function_exists( 'almaden_bookster_get_publisher_page_title' ) ? almaden_bookster_get_publisher_page_title() : 'Editorial',
			function_exists( 'almaden_bookster_get_publisher_page_id' ) ? almaden_bookster_get_publisher_page_id() : 0
		);
		$add_rule(
			'publisher_onboarding',
			function_exists( 'almaden_bookster_get_publisher_onboarding_page_slug' ) ? almaden_bookster_get_publisher_onboarding_page_slug() : ( function_exists( 'almaden_bookster_get_publisher_onboarding_slug' ) ? almaden_bookster_get_publisher_onboarding_slug() : 'crear-editorial' ),
			function_exists( 'almaden_bookster_get_publisher_onboarding_page_title' ) ? almaden_bookster_get_publisher_onboarding_page_title() : ( function_exists( 'almaden_bookster_get_publisher_onboarding_title' ) ? almaden_bookster_get_publisher_onboarding_title() : 'Crear editorial' ),
			function_exists( 'almaden_bookster_get_publisher_onboarding_page_id' ) ? almaden_bookster_get_publisher_onboarding_page_id() : 0
		);
		$add_rule(
			'quiz_builder',
			function_exists( 'almaden_bookster_get_quiz_page_slug' ) ? almaden_bookster_get_quiz_page_slug() : 'almaden-book-quiz',
			function_exists( 'almaden_bookster_get_quiz_page_title' ) ? almaden_bookster_get_quiz_page_title() : 'Book Quiz',
			function_exists( 'almaden_bookster_get_quiz_page_id' ) ? almaden_bookster_get_quiz_page_id() : 0
		);
		if ( ! function_exists( 'almaden_bookster_should_show_bookshelf_in_regular_menu' ) || ! almaden_bookster_should_show_bookshelf_in_regular_menu() ) {
			$add_rule(
				'store',
				function_exists( 'almaden_bookster_get_store_slug' ) ? almaden_bookster_get_store_slug() : 'bookshelf',
				function_exists( 'almaden_bookster_get_store_title' ) ? almaden_bookster_get_store_title() : 'Ebook Store',
				function_exists( 'almaden_bookster_get_store_page_id' ) ? almaden_bookster_get_store_page_id() : 0
			);
		}

		return $rules;
	}
}

if ( ! function_exists( 'almaden_bookster_should_show_bookshelf_in_regular_menu' ) ) {
	function almaden_bookster_should_show_bookshelf_in_regular_menu() {
		$menu_enabled = function_exists( 'almaden_bookster_is_store_menu_enabled' ) && almaden_bookster_is_store_menu_enabled();
		$distribution_settings = function_exists( 'almaden_bookster_get_distribution_settings' ) ? almaden_bookster_get_distribution_settings() : array();
		$distribution_enabled = ! empty( $distribution_settings['menu_injection_enabled'] );
		$page_id = function_exists( 'almaden_bookster_get_store_page_id' ) ? almaden_bookster_get_store_page_id() : 0;
		$is_admin_only = function_exists( 'almaden_bookster_is_page_admin_only' ) && almaden_bookster_is_page_admin_only( 'store' );

		return $menu_enabled && $distribution_enabled && $page_id > 0 && ! $is_admin_only;
	}
}

if ( ! function_exists( 'almaden_bookster_should_show_shell_home_in_regular_menu' ) ) {
	function almaden_bookster_should_show_shell_home_in_regular_menu() {
		$is_admin_only = function_exists( 'almaden_bookster_is_page_admin_only' ) && almaden_bookster_is_page_admin_only( 'shell_home' );

		return function_exists( 'almaden_bookster_is_shell_home_menu_enabled' ) && almaden_bookster_is_shell_home_menu_enabled() && function_exists( 'almaden_bookster_get_shell_home_page_id' ) && almaden_bookster_get_shell_home_page_id() > 0 && ! $is_admin_only;
	}
}

if ( ! function_exists( 'almaden_bookster_build_shell_home_menu_item_html' ) ) {
	function almaden_bookster_build_shell_home_menu_item_html() {
		if ( ! almaden_bookster_should_show_shell_home_in_regular_menu() ) {
			return '';
		}

		$url   = function_exists( 'almaden_bookster_get_shell_home_page_url' ) ? almaden_bookster_get_shell_home_page_url() : home_url( '/' );
		$label = function_exists( 'almaden_bookster_get_shell_home_title' ) ? almaden_bookster_get_shell_home_title() : 'Almaden App';
		$page_id = function_exists( 'almaden_bookster_get_shell_home_page_id' ) ? almaden_bookster_get_shell_home_page_id() : 0;
		$classes = array( 'menu-item', 'menu-item-type-post_type', 'menu-item-object-page', 'menu-item-shell-home' );

		if ( $page_id > 0 && is_page( $page_id ) ) {
			$classes[] = 'current-menu-item';
			$classes[] = 'current_page_item';
		}

		return sprintf(
			'<li class="%1$s"><a href="%2$s">%3$s</a></li>',
			esc_attr( implode( ' ', $classes ) ),
			esc_url( $url ),
			esc_html( $label )
		);
	}
}

if ( ! function_exists( 'almaden_bookster_build_bookshelf_menu_item_html' ) ) {
	function almaden_bookster_build_bookshelf_menu_item_html() {
		if ( ! almaden_bookster_should_show_bookshelf_in_regular_menu() ) {
			return '';
		}

		$url     = function_exists( 'almaden_bookster_get_store_page_url' ) ? almaden_bookster_get_store_page_url() : home_url( '/' );
		$label   = function_exists( 'almaden_bookster_get_store_menu_label' ) ? almaden_bookster_get_store_menu_label() : ( function_exists( 'almaden_bookster_get_store_title' ) ? almaden_bookster_get_store_title() : 'Ebook Store' );
		$page_id = function_exists( 'almaden_bookster_get_store_page_id' ) ? almaden_bookster_get_store_page_id() : 0;
		$classes = array( 'menu-item', 'menu-item-type-post_type', 'menu-item-object-page', 'menu-item-bookshelf' );

		if ( $page_id > 0 && is_page( $page_id ) ) {
			$classes[] = 'current-menu-item';
			$classes[] = 'current_page_item';
		}

		return sprintf(
			'<li class="%1$s"><a href="%2$s">%3$s</a></li>',
			esc_attr( implode( ' ', $classes ) ),
			esc_url( $url ),
			esc_html( $label )
		);
	}
}

if ( ! function_exists( 'almaden_bookster_should_strip_shell_navigation_item' ) ) {
	function almaden_bookster_should_strip_shell_navigation_item( $item_url = '', $item_title = '', $item_id = 0 ) {
		$item_url   = trim( (string) $item_url );
		$item_id    = absint( $item_id );

		foreach ( almaden_bookster_get_shell_navigation_exclusion_rules() as $rule ) {
			$rule_url   = isset( $rule['url'] ) ? trim( (string) $rule['url'] ) : '';
			$rule_id    = isset( $rule['page_id'] ) ? absint( $rule['page_id'] ) : 0;

			if ( $rule_id > 0 && $item_id > 0 && $rule_id === $item_id ) {
				return true;
			}

			if ( '' !== $rule_url && '' !== $item_url && untrailingslashit( $rule_url ) === untrailingslashit( $item_url ) ) {
				return true;
			}
		}

		return false;
	}
}

if ( ! function_exists( 'almaden_bookster_exclude_shell_pages_from_page_lists' ) ) {
	function almaden_bookster_exclude_shell_pages_from_page_lists( $excluded_page_ids ) {
		$excluded_page_ids = is_array( $excluded_page_ids ) ? array_map( 'absint', $excluded_page_ids ) : array();

		foreach ( almaden_bookster_get_shell_navigation_exclusion_rules() as $rule ) {
			$page_id = isset( $rule['page_id'] ) ? absint( $rule['page_id'] ) : 0;
			if ( $page_id > 0 ) {
				$excluded_page_ids[] = $page_id;
			}
		}

		return array_values( array_unique( array_filter( $excluded_page_ids ) ) );
	}
}

if ( ! function_exists( 'almaden_bookster_remove_shell_pages_from_nav_menu_items' ) ) {
	function almaden_bookster_remove_shell_pages_from_nav_menu_items( $items ) {
		if ( is_admin() || empty( $items ) || ! is_array( $items ) ) {
			return $items;
		}

		return array_values(
			array_filter(
				$items,
				static function( $item ) {
					$item_url   = isset( $item->url ) ? (string) $item->url : '';
					$item_title = isset( $item->title ) ? (string) $item->title : '';
					$item_id    = isset( $item->object_id ) ? absint( $item->object_id ) : 0;

					return ! almaden_bookster_should_strip_shell_navigation_item( $item_url, $item_title, $item_id );
				}
			)
		);
	}
}

if ( ! function_exists( 'almaden_bookster_prepend_shell_home_to_nav_menu_items_html' ) ) {
	function almaden_bookster_prepend_shell_home_to_nav_menu_items_html( $items ) {
		if ( is_admin() || '' === trim( (string) $items ) || ! almaden_bookster_should_show_shell_home_in_regular_menu() ) {
			return $items;
		}

		$shell_home_url = function_exists( 'almaden_bookster_get_shell_home_page_url' ) ? esc_url( almaden_bookster_get_shell_home_page_url() ) : '';
		if ( '' !== $shell_home_url && false !== stripos( $items, $shell_home_url ) ) {
			return $items;
		}

		$shell_home_item = almaden_bookster_build_shell_home_menu_item_html();
		if ( '' === $shell_home_item ) {
			return $items;
		}

		return $shell_home_item . $items;
	}
}

if ( ! function_exists( 'almaden_bookster_prepend_bookshelf_to_nav_menu_items_html' ) ) {
	function almaden_bookster_prepend_bookshelf_to_nav_menu_items_html( $items ) {
		if ( is_admin() || '' === trim( (string) $items ) || ! almaden_bookster_should_show_bookshelf_in_regular_menu() ) {
			return $items;
		}

		$bookshelf_url = function_exists( 'almaden_bookster_get_store_page_url' ) ? esc_url( almaden_bookster_get_store_page_url() ) : '';
		if ( '' !== $bookshelf_url && false !== stripos( $items, $bookshelf_url ) ) {
			return $items;
		}

		$bookshelf_item = almaden_bookster_build_bookshelf_menu_item_html();
		if ( '' === $bookshelf_item ) {
			return $items;
		}

		return $bookshelf_item . $items;
	}
}
