<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
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

function almaden_bookster_should_strip_course_navigation_item( $block_content, $block = array() ) {
	if ( is_admin() ) {
		return false;
	}

	$course_urls = array_filter(
		array(
			function_exists( 'almaden_bookster_get_course_creator_page_url' ) ? almaden_bookster_get_course_creator_page_url() : '',
			function_exists( 'almaden_bookster_get_course_archive_page_url' ) ? almaden_bookster_get_course_archive_page_url() : '',
		)
	);

	foreach ( $course_urls as $course_url ) {
		if ( '' !== $course_url && false !== strpos( $block_content, esc_url( $course_url ) ) ) {
			return true;
		}
	}

	$blocked_labels = array_filter(
		array(
			function_exists( 'almaden_bookster_get_course_creator_title' ) ? almaden_bookster_get_course_creator_title() : 'Cursos',
			function_exists( 'almaden_bookster_get_course_archive_title' ) ? almaden_bookster_get_course_archive_title() : 'Sala de clases',
			'Cursos',
			'Sala de clases',
		)
	);

	foreach ( $blocked_labels as $blocked_label ) {
		if ( '' !== $blocked_label && false !== stripos( $block_content, $blocked_label ) ) {
			return true;
		}
	}

	return false;
}

function almaden_bookster_strip_course_navigation_item_from_menu_items( $items, $args ) {
	if ( is_admin() || '' === trim( (string) $items ) ) {
		return $items;
	}

	$course_urls = array_filter(
		array(
			function_exists( 'almaden_bookster_get_course_creator_page_url' ) ? almaden_bookster_get_course_creator_page_url() : '',
			function_exists( 'almaden_bookster_get_course_archive_page_url' ) ? almaden_bookster_get_course_archive_page_url() : '',
		)
	);

	foreach ( $course_urls as $course_url ) {
		if ( '' !== $course_url ) {
			$items = preg_replace(
				"#<li[^>]*>\\s*<a[^>]*href=(\"|')" . preg_quote( esc_url( $course_url ), '#' ) . "(\"|')[^>]*>.*?</a>\\s*</li>#is",
				'',
				$items
			);
		}
	}

	return $items;
}
add_filter( 'wp_nav_menu_items', 'almaden_bookster_strip_course_navigation_item_from_menu_items', 15, 2 );

function almaden_bookster_strip_course_navigation_from_block( $block_content, $block ) {
	if ( ! isset( $block['blockName'] ) || 0 !== strpos( $block['blockName'], 'core/navigation' ) ) {
		return $block_content;
	}

	return almaden_bookster_should_strip_course_navigation_item( $block_content, $block ) ? '' : $block_content;
}
add_filter( 'render_block_core/navigation-link', 'almaden_bookster_strip_course_navigation_from_block', 15, 2 );
add_filter( 'render_block_core/page-list-item', 'almaden_bookster_strip_course_navigation_from_block', 15, 2 );

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
add_filter( 'render_block_core/navigation', 'almaden_bookster_inject_store_into_navigation_block', 20, 2);

