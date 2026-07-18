<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function almaden_bookster_migrate_book_authors() {
	$books = get_posts(
		array(
			'post_type'      => 'almaden-books',
			'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);

	foreach ( $books as $book_id ) {
		$book_id = absint( $book_id );
		if ( $book_id <= 0 ) {
			continue;
		}

		$existing_authors = function_exists( 'almaden_bookster_get_book_authors' ) ? almaden_bookster_get_book_authors( $book_id ) : array();
		if ( ! empty( $existing_authors ) ) {
			continue;
		}

		$raw_authors = get_post_meta( $book_id, 'book_author', true );
		if ( '' === trim( (string) $raw_authors ) ) {
			$raw_authors = get_post_meta( $book_id, '_almaden_book_author', true );
		}

		if ( '' !== trim( (string) $raw_authors ) && function_exists( 'almaden_bookster_sync_book_authors_from_input' ) ) {
			almaden_bookster_sync_book_authors_from_input( $book_id, $raw_authors );
			continue;
		}

		$post_author_id = absint( get_post_field( 'post_author', $book_id ) );
		if ( $post_author_id > 0 && function_exists( 'almaden_bookster_set_book_authors' ) ) {
			$author = get_user_by( 'id', $post_author_id );
			almaden_bookster_set_book_authors(
				$book_id,
				array(
					array(
						'book_id'      => $book_id,
						'user_id'      => $post_author_id,
						'role'         => 'author',
						'sort_order'   => 1,
						'status'       => 'active',
						'source_label' => $author && ! empty( $author->display_name ) ? $author->display_name : '',
						'display_name' => $author && ! empty( $author->display_name ) ? $author->display_name : '',
						'user_login'   => $author && ! empty( $author->user_login ) ? $author->user_login : '',
						'user_email'   => $author && ! empty( $author->user_email ) ? $author->user_email : '',
					),
				)
			);
		}
	}
}

function almaden_bookster_handle_book_authors_save_on_post( $post_id, $post, $update ) {
	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}

	if ( ! $post || 'almaden-books' !== $post->post_type ) {
		return;
	}

	$raw_authors = get_post_meta( $post_id, 'book_author', true );
	if ( '' === trim( (string) $raw_authors ) ) {
		$raw_authors = get_post_meta( $post_id, '_almaden_book_author', true );
	}

	if ( '' === trim( (string) $raw_authors ) ) {
		return;
	}

	$current_label = function_exists( 'almaden_bookster_get_book_author_display_label' ) ? almaden_bookster_get_book_author_display_label( $post_id, '' ) : '';
	if ( '' !== trim( $current_label ) && trim( (string) $raw_authors ) === trim( $current_label ) && ! empty( function_exists( 'almaden_bookster_get_book_authors' ) ? almaden_bookster_get_book_authors( $post_id ) : array() ) ) {
		return;
	}

	if ( function_exists( 'almaden_bookster_sync_book_authors_from_input' ) ) {
		almaden_bookster_sync_book_authors_from_input( $post_id, $raw_authors );
	}
}
add_action( 'save_post', 'almaden_bookster_handle_book_authors_save_on_post', 20, 3 );

