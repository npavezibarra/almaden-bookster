<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function almaden_bookster_get_book_media_subdir_meta_key() {
	return '_almaden_book_media_subdir';
}

function almaden_bookster_is_book_media_upload_request() {
	return function_exists( 'almaden_bookster_is_book_image_upload_request' )
		? almaden_bookster_is_book_image_upload_request()
		: false;
}

function almaden_bookster_normalize_book_media_folder_slug( $value ) {
	$value = sanitize_title( (string) $value );
	return '' !== $value ? $value : '';
}

function almaden_bookster_get_book_media_folder_base_slug( $book_id ) {
	$book_id = absint( $book_id );
	if ( $book_id <= 0 ) {
		return '';
	}

	$book = get_post( $book_id );
	if ( ! $book || 'almaden-books' !== $book->post_type ) {
		return '';
	}

	$user_slug = '';
	$user = get_userdata( (int) $book->post_author );
	if ( $user ) {
		$user_slug = trim( (string) ( $user->user_nicename ?: $user->display_name ?: $user->user_login ) );
	}
	$user_slug = almaden_bookster_normalize_book_media_folder_slug( $user_slug );
	if ( '' === $user_slug ) {
		$user_slug = 'user-' . $book_id;
	}

	$book_slug = almaden_bookster_normalize_book_media_folder_slug( $book->post_title );
	if ( '' === $book_slug ) {
		$book_slug = 'book-' . $book_id;
	}

	return trim( $user_slug . '-' . $book_slug, '-' );
}

function almaden_bookster_is_book_media_subdir_taken( $subdir, $exclude_book_id = 0 ) {
	$subdir = almaden_bookster_normalize_book_media_folder_slug( $subdir );
	if ( '' === $subdir ) {
		return false;
	}

	$exclude_book_id = absint( $exclude_book_id );
	$query = new WP_Query(
		array(
			'post_type'              => 'almaden-books',
			'post_status'            => 'any',
			'fields'                 => 'ids',
			'posts_per_page'         => 1,
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'post__not_in'           => $exclude_book_id > 0 ? array( $exclude_book_id ) : array(),
			'meta_query'             => array(
				array(
					'key'   => almaden_bookster_get_book_media_subdir_meta_key(),
					'value' => $subdir,
				),
			),
		)
	);

	return ! empty( $query->posts );
}

function almaden_bookster_get_unique_book_media_subdir( $book_id ) {
	$base = almaden_bookster_get_book_media_folder_base_slug( $book_id );
	if ( '' === $base ) {
		return '';
	}

	$subdir = $base;
	$suffix = 2;
	while ( almaden_bookster_is_book_media_subdir_taken( $subdir, $book_id ) ) {
		$subdir = $base . '-' . $suffix;
		++$suffix;
	}

	return $subdir;
}

function almaden_bookster_get_book_media_subdir( $book_id ) {
	$book_id = absint( $book_id );
	if ( $book_id <= 0 ) {
		return '';
	}

	$saved = get_post_meta( $book_id, almaden_bookster_get_book_media_subdir_meta_key(), true );
	$saved = almaden_bookster_normalize_book_media_folder_slug( $saved );
	if ( '' !== $saved ) {
		return $saved;
	}

	return almaden_bookster_get_unique_book_media_subdir( $book_id );
}

function almaden_bookster_ensure_book_media_directory( $book_id ) {
	$book_id = absint( $book_id );
	if ( $book_id <= 0 ) {
		return '';
	}

	$book = get_post( $book_id );
	if ( ! $book || 'almaden-books' !== $book->post_type ) {
		return '';
	}

	$subdir = almaden_bookster_get_book_media_subdir( $book_id );
	if ( '' === $subdir ) {
		return '';
	}

	$saved = almaden_bookster_normalize_book_media_folder_slug( get_post_meta( $book_id, almaden_bookster_get_book_media_subdir_meta_key(), true ) );
	if ( '' === $saved ) {
		update_post_meta( $book_id, almaden_bookster_get_book_media_subdir_meta_key(), $subdir );
	}

	$uploads = wp_upload_dir();
	if ( empty( $uploads['basedir'] ) ) {
		return $subdir;
	}

	$target_dir = trailingslashit( $uploads['basedir'] ) . $subdir;
	wp_mkdir_p( $target_dir );

	return $subdir;
}

function almaden_bookster_filter_book_upload_dir( $uploads ) {
	if ( ! function_exists( 'almaden_bookster_is_book_media_upload_request' ) || ! almaden_bookster_is_book_media_upload_request() ) {
		return $uploads;
	}

	$book_id = isset( $_REQUEST['post_id'] ) ? absint( $_REQUEST['post_id'] ) : 0;
	if ( $book_id <= 0 || 'almaden-books' !== get_post_type( $book_id ) ) {
		return $uploads;
	}

	$subdir = almaden_bookster_ensure_book_media_directory( $book_id );
	if ( '' === $subdir ) {
		return $uploads;
	}

	$uploads['subdir'] = '/' . ltrim( $subdir, '/' );
	$uploads['path']   = trailingslashit( $uploads['basedir'] ) . ltrim( $subdir, '/' );
	$uploads['url']    = trailingslashit( $uploads['baseurl'] ) . ltrim( $subdir, '/' );

	wp_mkdir_p( $uploads['path'] );

	return $uploads;
}

add_filter( 'upload_dir', 'almaden_bookster_filter_book_upload_dir', 20 );

function almaden_bookster_ensure_book_media_directory_on_save( $post_id, $post, $update ) {
	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}

	if ( ! $post instanceof WP_Post || 'almaden-books' !== $post->post_type ) {
		return;
	}

	almaden_bookster_ensure_book_media_directory( $post_id );
}

add_action( 'save_post_almaden-books', 'almaden_bookster_ensure_book_media_directory_on_save', 20, 3 );
