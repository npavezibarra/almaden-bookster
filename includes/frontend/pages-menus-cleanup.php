<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'almaden_bookster_cleanup_navigation_posts' ) ) {
	function almaden_bookster_cleanup_navigation_posts() {
		$navigation_posts = get_posts(
			array(
				'post_type'              => 'wp_navigation',
				'post_status'            => 'any',
				'posts_per_page'         => -1,
				'no_found_rows'          => true,
				'orderby'                => 'ID',
				'order'                  => 'ASC',
				'suppress_filters'       => true,
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false,
			)
		);

		foreach ( $navigation_posts as $navigation_post ) {
			if ( ! $navigation_post instanceof WP_Post ) {
				continue;
			}

			$blocks = function_exists( 'parse_blocks' ) ? parse_blocks( (string) $navigation_post->post_content ) : array();
			$clean_blocks = almaden_bookster_prune_shell_pages_from_block_children( $blocks );
			$clean_markup = function_exists( 'serialize_blocks' ) ? serialize_blocks( $clean_blocks ) : '';

			if ( $clean_markup !== (string) $navigation_post->post_content ) {
				wp_update_post(
					array(
						'ID'           => (int) $navigation_post->ID,
						'post_content' => $clean_markup,
					)
				);
			}
		}
	}
}

if ( ! function_exists( 'almaden_bookster_cleanup_classic_nav_menus' ) ) {
	function almaden_bookster_cleanup_classic_nav_menus() {
		$menus = wp_get_nav_menus();

		foreach ( $menus as $menu ) {
			$items = wp_get_nav_menu_items( $menu->term_id, array( 'update_post_term_cache' => false ) );
			if ( empty( $items ) || ! is_array( $items ) ) {
				continue;
			}

			foreach ( $items as $item ) {
				if ( ! $item instanceof WP_Post ) {
					continue;
				}

				$item_url   = isset( $item->url ) ? (string) $item->url : '';
				$item_title = isset( $item->title ) ? (string) $item->title : '';
				$item_id    = isset( $item->object_id ) ? absint( $item->object_id ) : 0;

				if ( almaden_bookster_should_strip_shell_navigation_item( $item_url, $item_title, $item_id ) ) {
					wp_delete_post( (int) $item->ID, true );
				}
			}
		}
	}
}

if ( ! function_exists( 'almaden_bookster_cleanup_navigation_entries_on_activation' ) ) {
	function almaden_bookster_cleanup_navigation_entries_on_activation() {
		almaden_bookster_cleanup_classic_nav_menus();
		almaden_bookster_cleanup_navigation_posts();
	}
}
