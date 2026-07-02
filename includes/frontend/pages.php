<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function almaden_bookster_get_pages_settings_defaults() {
	return array(
		'creator_page_id' => 0,
		'creator_slug'    => 'almaden-booklist',
		'creator_title'   => 'Taller',
		'store_page_id'   => 0,
		'store_slug'      => 'bookshelf',
		'store_title'     => 'Ebook Store',
		'store_menu_label' => 'Ebook Store',
		'store_menu_enabled' => 1,
	);
}

function almaden_bookster_get_pages_settings() {
	$saved_settings = get_option( 'almaden_bookster_pages_settings', array() );

	if ( ! is_array( $saved_settings ) ) {
		$saved_settings = array();
	}

	return wp_parse_args( $saved_settings, almaden_bookster_get_pages_settings_defaults() );
}

function almaden_bookster_sanitize_pages_settings( $raw_settings ) {
	$defaults = almaden_bookster_get_pages_settings_defaults();
	$slug     = isset( $raw_settings['creator_slug'] ) ? sanitize_title( wp_unslash( $raw_settings['creator_slug'] ) ) : $defaults['creator_slug'];
	$title    = isset( $raw_settings['creator_title'] ) ? sanitize_text_field( wp_unslash( $raw_settings['creator_title'] ) ) : $defaults['creator_title'];
	$store_slug  = isset( $raw_settings['store_slug'] ) ? sanitize_title( wp_unslash( $raw_settings['store_slug'] ) ) : $defaults['store_slug'];
	$store_title = isset( $raw_settings['store_title'] ) ? sanitize_text_field( wp_unslash( $raw_settings['store_title'] ) ) : $defaults['store_title'];
	$store_menu_label = isset( $raw_settings['store_menu_label'] ) ? sanitize_text_field( wp_unslash( $raw_settings['store_menu_label'] ) ) : $defaults['store_menu_label'];
	$store_menu_enabled = ! empty( $raw_settings['store_menu_enabled'] ) ? 1 : 0;

	if ( '' === $slug ) {
		$slug = $defaults['creator_slug'];
	}

	if ( '' === $title ) {
		$title = $defaults['creator_title'];
	}

	if ( '' === $store_slug ) {
		$store_slug = $defaults['store_slug'];
	}

	if ( '' === $store_title ) {
		$store_title = $defaults['store_title'];
	}

	if ( '' === $store_menu_label ) {
		$store_menu_label = $defaults['store_menu_label'];
	}

	return array(
		'creator_page_id' => isset( $raw_settings['creator_page_id'] ) ? absint( $raw_settings['creator_page_id'] ) : 0,
		'creator_slug'    => $slug,
		'creator_title'   => $title,
		'store_page_id'   => isset( $raw_settings['store_page_id'] ) ? absint( $raw_settings['store_page_id'] ) : 0,
		'store_slug'      => $store_slug,
		'store_title'     => $store_title,
		'store_menu_label'=> $store_menu_label,
		'store_menu_enabled' => $store_menu_enabled,
	);
}

function almaden_bookster_get_creator_page_id() {
	$settings = almaden_bookster_get_pages_settings();
	return isset( $settings['creator_page_id'] ) ? absint( $settings['creator_page_id'] ) : 0;
}

function almaden_bookster_get_creator_slug() {
	$settings = almaden_bookster_get_pages_settings();
	return isset( $settings['creator_slug'] ) && '' !== $settings['creator_slug'] ? $settings['creator_slug'] : 'almaden-booklist';
}

function almaden_bookster_get_creator_title() {
	$settings = almaden_bookster_get_pages_settings();
	return isset( $settings['creator_title'] ) && '' !== $settings['creator_title'] ? $settings['creator_title'] : 'Taller';
}

function almaden_bookster_get_store_page_id() {
	$settings = almaden_bookster_get_pages_settings();
	return isset( $settings['store_page_id'] ) ? absint( $settings['store_page_id'] ) : 0;
}

function almaden_bookster_get_store_slug() {
	$settings = almaden_bookster_get_pages_settings();
	return isset( $settings['store_slug'] ) && '' !== $settings['store_slug'] ? $settings['store_slug'] : 'bookshelf';
}

function almaden_bookster_get_store_title() {
	$settings = almaden_bookster_get_pages_settings();
	return isset( $settings['store_title'] ) && '' !== $settings['store_title'] ? $settings['store_title'] : 'Ebook Store';
}

function almaden_bookster_get_store_menu_label() {
	$settings = almaden_bookster_get_pages_settings();
	return isset( $settings['store_menu_label'] ) && '' !== $settings['store_menu_label'] ? $settings['store_menu_label'] : 'Ebook Store';
}

function almaden_bookster_is_store_menu_enabled() {
	$settings = almaden_bookster_get_pages_settings();
	return ! empty( $settings['store_menu_enabled'] );
}

function almaden_bookster_get_creator_page_url( $query_args = array() ) {
	$slug = trim( almaden_bookster_get_creator_slug(), '/' );
	$url  = home_url( '/' . $slug . '/' );

	if ( empty( $query_args ) ) {
		return $url;
	}

	return add_query_arg( $query_args, $url );
}

function almaden_bookster_get_store_page_url( $query_args = array() ) {
	$slug = trim( almaden_bookster_get_store_slug(), '/' );
	$url  = home_url( '/' . $slug . '/' );

	if ( empty( $query_args ) ) {
		return $url;
	}

	return add_query_arg( $query_args, $url );
}

function almaden_bookster_user_can_manage_books() {
	return current_user_can( 'almaden_manage_books' ) || current_user_can( 'manage_options' );
}

function almaden_bookster_sync_creator_page() {
	$settings = almaden_bookster_get_pages_settings();
	$slug     = almaden_bookster_get_creator_slug();
	$title    = almaden_bookster_get_creator_title();
	$page_id  = isset( $settings['creator_page_id'] ) ? absint( $settings['creator_page_id'] ) : 0;
	$page     = $page_id > 0 ? get_post( $page_id ) : null;

	if ( $page && 'page' !== $page->post_type ) {
		$page = null;
	}

	if ( ! $page ) {
		$page = get_page_by_path( $slug, OBJECT, 'page' );
	}

	if ( ! $page ) {
		$new_page_id = wp_insert_post(
			array(
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => '<!-- El contenido de esta página es generado dinámicamente por el plugin AlmadenBookster -->',
			)
		);

		if ( ! is_wp_error( $new_page_id ) && $new_page_id ) {
			$settings['creator_page_id'] = absint( $new_page_id );
			$settings['creator_slug']    = $slug;
			$settings['creator_title']   = $title;
			update_option( 'almaden_bookster_pages_settings', $settings );
		}

		return;
	}

	$updates = array( 'ID' => $page->ID );

	if ( $page->post_name !== $slug ) {
		$updates['post_name'] = $slug;
	}

	if ( $page->post_title !== $title ) {
		$updates['post_title'] = $title;
	}

	if ( count( $updates ) > 1 ) {
		wp_update_post( $updates );
	}

	if ( $page_id !== (int) $page->ID ) {
		$settings['creator_page_id'] = (int) $page->ID;
		$settings['creator_slug']    = $slug;
		$settings['creator_title']   = $title;
		update_option( 'almaden_bookster_pages_settings', $settings );
	}
}

function almaden_bookster_sync_store_page() {
	$settings = almaden_bookster_get_pages_settings();
	$slug     = almaden_bookster_get_store_slug();
	$title    = almaden_bookster_get_store_title();
	$page_id  = isset( $settings['store_page_id'] ) ? absint( $settings['store_page_id'] ) : 0;
	$page     = $page_id > 0 ? get_post( $page_id ) : null;

	if ( $page && 'page' !== $page->post_type ) {
		$page = null;
	}

	if ( ! $page ) {
		$page = get_page_by_path( $slug, OBJECT, 'page' );
	}

	if ( ! $page ) {
		$new_page_id = wp_insert_post(
			array(
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => '<!-- El contenido de esta página es generado dinámicamente por el plugin AlmadenBookster -->',
			)
		);

		if ( ! is_wp_error( $new_page_id ) && $new_page_id ) {
			$settings['store_page_id'] = absint( $new_page_id );
			$settings['store_slug']    = $slug;
			$settings['store_title']   = $title;
			update_option( 'almaden_bookster_pages_settings', $settings );
		}

		return;
	}

	$updates = array( 'ID' => $page->ID );

	if ( $page->post_name !== $slug ) {
		$updates['post_name'] = $slug;
	}

	if ( $page->post_title !== $title ) {
		$updates['post_title'] = $title;
	}

	if ( count( $updates ) > 1 ) {
		wp_update_post( $updates );
	}

	if ( $page_id !== (int) $page->ID ) {
		$settings['store_page_id'] = (int) $page->ID;
		$settings['store_slug']    = $slug;
		$settings['store_title']   = $title;
		update_option( 'almaden_bookster_pages_settings', $settings );
	}
}

function almaden_bookster_append_store_menu_item( $items, $args ) {
	if ( is_admin() || ! almaden_bookster_is_store_menu_enabled() ) {
		return $items;
	}

	$menu_label = almaden_bookster_get_store_menu_label();
	$menu_url   = almaden_bookster_get_store_page_url();

	if ( empty( $menu_label ) || empty( $menu_url ) ) {
		return $items;
	}

	$menu_item = sprintf(
		'<li class="menu-item menu-item-type-post_type menu-item-object-page almaden-menu-item-store"><a href="%1$s">%2$s</a></li>',
		esc_url( $menu_url ),
		esc_html( $menu_label )
	);

	return $items . $menu_item;
}
add_filter( 'wp_nav_menu_items', 'almaden_bookster_append_store_menu_item', 20, 2 );

function almaden_bookster_inject_store_into_navigation_block( $block_content, $block ) {
	if ( is_admin() || ! almaden_bookster_is_store_menu_enabled() ) {
		return $block_content;
	}

	if ( empty( $block['blockName'] ) || 'core/navigation' !== $block['blockName'] ) {
		return $block_content;
	}

	$menu_label = almaden_bookster_get_store_menu_label();
	$menu_url   = almaden_bookster_get_store_page_url();

	if ( empty( $menu_label ) || empty( $menu_url ) ) {
		return $block_content;
	}

	$menu_item = sprintf(
		'<li class="wp-block-navigation-item menu-item menu-item-type-post_type menu-item-object-page almaden-menu-item-store"><a class="wp-block-navigation-item__content" href="%1$s">%2$s</a></li>',
		esc_url( $menu_url ),
		esc_html( $menu_label )
	);

	if ( false !== strpos( $block_content, '</ul>' ) ) {
		return preg_replace( '/<\/ul>/', $menu_item . '</ul>', $block_content, 1 );
	}

	return $block_content . $menu_item;
}
add_filter( 'render_block_core/navigation', 'almaden_bookster_inject_store_into_navigation_block', 20, 2 );
