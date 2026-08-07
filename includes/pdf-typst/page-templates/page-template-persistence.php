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

function almaden_bookster_typst_reconcile_page_template_results( $book_id, $results ) {
	$templates = almaden_bookster_typst_get_page_templates( $book_id );
	$result_map = array();
	foreach ( (array) $results as $result ) {
		$instance_id = almaden_bookster_typst_page_template_clean_instance_id( $result['instance_id'] ?? '' );
		if ( '' !== $instance_id ) {
			$result_map[ $instance_id ] = $result;
		}
	}

	$changed = false;
	foreach ( $templates as &$template ) {
		$instance_id = (string) ( $template['instance_id'] ?? $template['id'] ?? '' );
		$result = $result_map[ $instance_id ] ?? null;
		if ( ! is_array( $result ) || empty( $result['applied'] ) ) {
			continue;
		}
		$resolved_page = max( 1, (int) ( $result['resolved_page'] ?? $result['page'] ?? 0 ) );
		$anchor = almaden_bookster_typst_page_template_normalize_anchor( $result['anchor'] ?? array() );
		if ( $resolved_page !== (int) ( $template['resolved_page'] ?? 0 ) || $anchor !== ( $template['anchor'] ?? array() ) ) {
			$template['resolved_page'] = $resolved_page;
			$template['anchor'] = $anchor;
			$changed = true;
		}
	}
	unset( $template );

	return $changed ? almaden_bookster_typst_save_page_templates( $book_id, $templates ) : $templates;
}
