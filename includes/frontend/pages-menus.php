<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/pages-menus-rules.php';
require_once __DIR__ . '/pages-menus-blocks.php';
require_once __DIR__ . '/pages-menus-cleanup.php';
require_once __DIR__ . '/pages-menus-sync.php';

add_filter( 'wp_nav_menu_objects', 'almaden_bookster_remove_shell_pages_from_nav_menu_items', 5 );
add_filter( 'wp_list_pages_excludes', 'almaden_bookster_exclude_shell_pages_from_page_lists', 10 );
add_filter( 'wp_nav_menu_items', 'almaden_bookster_strip_shell_pages_from_menu_items_html', 5 );
add_filter( 'wp_nav_menu_items', 'almaden_bookster_prepend_shell_home_to_nav_menu_items_html', 10 );
add_filter( 'wp_nav_menu_items', 'almaden_bookster_prepend_bookshelf_to_nav_menu_items_html', 10 );
add_filter( 'render_block', 'almaden_bookster_strip_shell_pages_from_navigation_block', 5, 2 );
add_filter( 'render_block', 'almaden_bookster_inject_shell_home_into_navigation_block_html', 20, 2 );
add_filter( 'render_block', 'almaden_bookster_inject_bookshelf_into_navigation_block_html', 21, 2 );
add_filter( 'block_core_navigation_render_fallback', 'almaden_bookster_get_filtered_navigation_fallback_blocks', 10 );
add_filter( 'render_block_data', 'almaden_bookster_prune_shell_pages_from_block_tree', 10 );
