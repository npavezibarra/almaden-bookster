<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function almaden_bookster_load_authors_page() {
	if ( ! is_page( almaden_bookster_get_authors_slug() ) || ! is_main_query() ) {
		return;
	}

	if ( ! is_user_logged_in() ) {
		auth_redirect();
	}

	if ( ! current_user_can( 'almaden_manage_books' ) && ! current_user_can( 'manage_options' ) ) {
		wp_die( 'No tienes permisos para acceder a la página de autores.' );
	}

	show_admin_bar( false );
	wp_enqueue_media();

	$template_path = dirname( __FILE__ ) . '/../../templates/authors/authors-app.php';
	if ( file_exists( $template_path ) ) {
		require_once $template_path;
		exit;
	}

	wp_die( 'Plantilla de autores no encontrada.' );
}
add_action( 'template_redirect', 'almaden_bookster_load_authors_page', 5 );

function almaden_bookster_load_author_detail_page() {
	if ( ! is_page( almaden_bookster_get_author_page_slug() ) || ! is_main_query() ) {
		return;
	}

	show_admin_bar( false );

	$template_path = dirname( __FILE__ ) . '/../../templates/authors/author-app.php';
	if ( file_exists( $template_path ) ) {
		require_once $template_path;
		exit;
	}

	wp_die( 'Plantilla de autor no encontrada.' );
}
add_action( 'template_redirect', 'almaden_bookster_load_author_detail_page', 5 );

function almaden_bookster_register_author_routes() {
	$base_slug = trim( almaden_bookster_get_author_page_slug(), '/' );
	if ( '' === $base_slug ) {
		$base_slug = 'autor';
	}

	add_rewrite_rule(
		'^' . preg_quote( $base_slug, '/' ) . '/([^/]+)/?$',
		'index.php?pagename=' . $base_slug . '&almaden_author_slug=$matches[1]',
		'top'
	);
}
add_action( 'init', 'almaden_bookster_register_author_routes', 20 );

function almaden_bookster_maybe_flush_author_rewrite_rules() {
	$rewrite_version_option = 'almaden_bookster_author_rewrite_version';
	$rewrite_version        = '1.0.0';

	if ( get_option( $rewrite_version_option ) === $rewrite_version ) {
		return;
	}

	flush_rewrite_rules( false );
	update_option( $rewrite_version_option, $rewrite_version );
}
add_action( 'init', 'almaden_bookster_maybe_flush_author_rewrite_rules', 99 );

function almaden_bookster_register_author_query_vars( $vars ) {
	$vars[] = 'almaden_author_slug';
	return $vars;
}
add_filter( 'query_vars', 'almaden_bookster_register_author_query_vars' );

