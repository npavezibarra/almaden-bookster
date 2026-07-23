<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function almaden_bookster_get_author_page_settings_defaults() {
	return array(
		'page_id' => 0,
		'slug'    => 'autor',
		'title'   => 'Autor',
	);
}

function almaden_bookster_get_author_page_settings() {
	$saved_settings = get_option( 'almaden_bookster_author_page_settings', array() );

	if ( ! is_array( $saved_settings ) ) {
		$saved_settings = array();
	}

	return wp_parse_args( $saved_settings, almaden_bookster_get_author_page_settings_defaults() );
}

function almaden_bookster_get_author_page_slug() {
	$settings = almaden_bookster_get_author_page_settings();
	return isset( $settings['slug'] ) && '' !== $settings['slug'] ? $settings['slug'] : 'autor';
}

function almaden_bookster_get_author_page_title() {
	$settings = almaden_bookster_get_author_page_settings();
	return isset( $settings['title'] ) && '' !== $settings['title'] ? $settings['title'] : 'Autor';
}

function almaden_bookster_get_author_page_id() {
	$settings = almaden_bookster_get_author_page_settings();
	return isset( $settings['page_id'] ) ? absint( $settings['page_id'] ) : 0;
}

function almaden_bookster_get_author_page_url( $author_slug = '' ) {
	$base_slug = trim( almaden_bookster_get_author_page_slug(), '/' );
	$base_url  = home_url( '/' . $base_slug . '/' );

	if ( '' === trim( (string) $author_slug ) ) {
		return $base_url;
	}

	return trailingslashit( $base_url . sanitize_title( $author_slug ) );
}

function almaden_bookster_resolve_unique_author_slug( $base_slug ) {
	$slug = sanitize_title( (string) $base_slug );

	if ( '' === $slug ) {
		$slug = 'autor';
	}

	$original_slug = $slug;
	$suffix        = 2;

	while ( almaden_bookster_get_author_by_slug( $slug ) ) {
		$slug = $original_slug . '-' . $suffix;
		$suffix++;
	}

	return $slug;
}

function almaden_bookster_get_author_user_slug( $user_id ) {
	$user_id = absint( $user_id );
	if ( $user_id <= 0 ) {
		return '';
	}

	$saved_slug = trim( (string) get_user_meta( $user_id, almaden_bookster_get_author_slug_meta_key(), true ) );
	if ( '' !== $saved_slug ) {
		return sanitize_title( $saved_slug );
	}

	$user = get_user_by( 'id', $user_id );
	if ( ! $user ) {
		return '';
	}

	return sanitize_title( $user->display_name ? $user->display_name : $user->user_login );
}

function almaden_bookster_get_author_by_slug( $slug ) {
	$slug = sanitize_title( (string) $slug );
	if ( '' === $slug ) {
		return null;
	}

	$users = get_users(
		array(
			'role'           => 'author',
			'number'         => 1,
			'fields'         => 'all',
			'meta_key'       => almaden_bookster_get_author_slug_meta_key(),
			'meta_value'     => $slug,
			'meta_compare'   => '=',
		)
	);

	if ( ! empty( $users ) ) {
		return $users[0];
	}

	$users = get_users(
		array(
			'role'           => 'author',
			'number'         => 50,
			'fields'         => 'all',
		)
	);

	foreach ( $users as $user ) {
		$user_slug = almaden_bookster_get_author_user_slug( $user->ID );
		if ( $user_slug === $slug ) {
			return $user;
		}
	}

	return null;
}

function almaden_bookster_sync_author_page() {
	$settings = almaden_bookster_get_author_page_settings();
	$slug     = almaden_bookster_get_author_page_slug();
	$title    = almaden_bookster_get_author_page_title();
	$page_id  = isset( $settings['page_id'] ) ? absint( $settings['page_id'] ) : 0;
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
			$settings['page_id'] = absint( $new_page_id );
			$settings['slug']    = $slug;
			$settings['title']   = $title;
			update_option( 'almaden_bookster_author_page_settings', $settings );
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
		$settings['page_id'] = (int) $page->ID;
		$settings['slug']    = $slug;
		$settings['title']   = $title;
		update_option( 'almaden_bookster_author_page_settings', $settings );
	}
}

function almaden_bookster_get_authors_page_settings() {
	return almaden_bookster_get_pages_settings();
}
