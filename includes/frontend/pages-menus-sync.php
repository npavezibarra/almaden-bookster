<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'almaden_bookster_get_store_navigation_menu_id' ) ) {
	function almaden_bookster_get_store_navigation_menu_id() {
		$settings = function_exists( 'almaden_bookster_get_distribution_settings' ) ? almaden_bookster_get_distribution_settings() : array();
		$menu_location = isset( $settings['menu_location'] ) ? sanitize_key( (string) $settings['menu_location'] ) : 'default';
		$locations = function_exists( 'get_nav_menu_locations' ) ? (array) get_nav_menu_locations() : array();

		if ( 'default' === $menu_location || '' === $menu_location ) {
			foreach ( $locations as $location => $menu_id ) {
				if ( absint( $menu_id ) > 0 ) {
					return absint( $menu_id );
				}
			}

			$menus = wp_get_nav_menus();
			if ( ! empty( $menus ) && $menus[0] instanceof WP_Term ) {
				return absint( $menus[0]->term_id );
			}

			return 0;
		}

		if ( isset( $locations[ $menu_location ] ) && absint( $locations[ $menu_location ] ) > 0 ) {
			return absint( $locations[ $menu_location ] );
		}

		return 0;
	}
}

if ( ! function_exists( 'almaden_bookster_sync_store_navigation_menu_item' ) ) {
	function almaden_bookster_sync_store_navigation_menu_item() {
		if ( ! function_exists( 'almaden_bookster_get_store_page_id' ) || ! function_exists( 'wp_update_nav_menu_item' ) ) {
			return;
		}

		$page_id = almaden_bookster_get_store_page_id();
		$menu_id  = almaden_bookster_get_store_navigation_menu_id();
		$marker_key = '_almaden_bookster_store_menu_item';

		if ( $page_id <= 0 || $menu_id <= 0 || ! almaden_bookster_should_show_bookshelf_in_regular_menu() ) {
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

					if ( 'nav_menu_item' !== $item->post_type ) {
						continue;
					}

					$is_managed = get_post_meta( $item->ID, $marker_key, true );
					$is_store_item = absint( $item->object_id ) === $page_id && 'page' === $item->object;
					if ( $is_managed || $is_store_item ) {
						wp_delete_post( (int) $item->ID, true );
					}
				}
			}

			return;
		}

		$menu_items = wp_get_nav_menu_items( $menu_id, array( 'update_post_term_cache' => false ) );
		$menu_items = is_array( $menu_items ) ? $menu_items : array();
		$existing_item = null;

		foreach ( $menu_items as $item ) {
			if ( ! $item instanceof WP_Post ) {
				continue;
			}

			if ( 'nav_menu_item' !== $item->post_type ) {
				continue;
			}

			$is_managed = get_post_meta( $item->ID, $marker_key, true );
			$is_store_item = absint( $item->object_id ) === $page_id && 'page' === $item->object;

			if ( $is_managed || $is_store_item ) {
				if ( null === $existing_item ) {
					$existing_item = $item;
				} else {
					wp_delete_post( (int) $item->ID, true );
				}
			}
		}

		$menu_title = function_exists( 'almaden_bookster_get_store_menu_label' ) ? almaden_bookster_get_store_menu_label() : ( function_exists( 'almaden_bookster_get_store_title' ) ? almaden_bookster_get_store_title() : 'Ebook Store' );
		$menu_url   = function_exists( 'almaden_bookster_get_store_page_url' ) ? almaden_bookster_get_store_page_url() : home_url( '/' );
		$menu_args  = array(
			'menu-item-title'     => $menu_title,
			'menu-item-url'       => $menu_url,
			'menu-item-object-id' => $page_id,
			'menu-item-object'    => 'page',
			'menu-item-type'      => 'post_type',
			'menu-item-status'    => 'publish',
		);

		if ( $existing_item instanceof WP_Post ) {
			$menu_args['menu-item-db-id'] = (int) $existing_item->ID;
		}

		$menu_item_id = wp_update_nav_menu_item( $menu_id, $existing_item instanceof WP_Post ? (int) $existing_item->ID : 0, $menu_args );
		if ( is_wp_error( $menu_item_id ) || ! $menu_item_id ) {
			return;
		}

		update_post_meta( (int) $menu_item_id, $marker_key, 1 );
	}
}

if ( ! function_exists( 'almaden_bookster_sync_bookshelf_navigation' ) ) {
	function almaden_bookster_sync_bookshelf_navigation() {
		almaden_bookster_sync_store_navigation_menu_item();
	}
}
