<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$author_created = isset( $_GET['author_created'] ) && '1' === $_GET['author_created'];
$authors = get_users(
	array(
		'role'    => 'author',
		'orderby' => 'display_name',
		'order'   => 'ASC',
		'number'  => -1,
	)
);

$extra_head_html = sprintf(
	'%4$s<link href="%1$s" rel="stylesheet"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link rel="stylesheet" href="%2$s"><link rel="stylesheet" href="%3$s">',
	esc_url( almaden_get_thumbnail_fonts_url() ),
	esc_url( 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css' ),
	esc_url( plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'assets/css/authors/authors-app.css' ) . '?v=' . esc_attr( filemtime( dirname( __FILE__ ) . '/../../assets/css/authors/authors-app.css' ) ),
	function_exists( 'almaden_bookster_get_bundled_fonts_stylesheet_url' ) ? '<link rel="stylesheet" href="' . esc_url( almaden_bookster_get_bundled_fonts_stylesheet_url() ) . '">' : ''
);

almaden_bookster_render_app_shell_start(
	array(
		'title'           => almaden_bookster_get_authors_title() . ' - Almaden',
		'body_id'         => 'almaden-authors-app-body',
		'active_nav_key'  => 'authors',
		'extra_head_html' => $extra_head_html,
	)
);

require dirname( __FILE__ ) . '/authors-page.php';

almaden_bookster_render_app_shell_end();
