<?php
/**
 * Persists normalized page-style configuration as book metadata.
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'ALMADEN_TYPST_TESTING' ) ) {
	exit;
}

function almaden_bookster_typst_get_page_styles( $book_id ) {
	$book_id = (int) $book_id;
	if ( $book_id < 1 || ! function_exists( 'get_post_meta' ) ) {
		return array();
	}

	return almaden_bookster_typst_page_style_normalize(
		get_post_meta( $book_id, '_almaden_page_styles', true )
	);
}

function almaden_bookster_typst_save_page_styles( $book_id, $value ) {
	$book_id = (int) $book_id;
	$styles = almaden_bookster_typst_page_style_normalize( $value );
	if ( $book_id < 1 || ! function_exists( 'update_post_meta' ) ) {
		return $styles;
	}

	update_post_meta( $book_id, '_almaden_page_styles', wp_json_encode( $styles ) );
	return $styles;
}
