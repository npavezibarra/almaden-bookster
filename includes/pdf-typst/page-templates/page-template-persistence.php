<?php
/**
 * Persists normalized page-template configuration as book metadata.
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'ALMADEN_TYPST_TESTING' ) ) {
	exit;
}

function almaden_bookster_typst_get_page_templates( $book_id ) {
	$book_id = (int) $book_id;
	if ( $book_id < 1 || ! function_exists( 'get_post_meta' ) ) {
		return array();
	}

	return almaden_bookster_typst_normalize_page_templates(
		get_post_meta( $book_id, '_almaden_page_templates', true )
	);
}

function almaden_bookster_typst_save_page_templates( $book_id, $value ) {
	$book_id   = (int) $book_id;
	$templates = almaden_bookster_typst_normalize_page_templates( $value );
	if ( $book_id < 1 || ! function_exists( 'update_post_meta' ) ) {
		return $templates;
	}

	update_post_meta( $book_id, '_almaden_page_templates', wp_json_encode( $templates ) );
	return $templates;
}
