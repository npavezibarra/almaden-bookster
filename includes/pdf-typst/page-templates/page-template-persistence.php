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

	$encoded_templates = function_exists( 'wp_json_encode' ) ? wp_json_encode( $templates ) : json_encode( $templates );
	update_post_meta( $book_id, '_almaden_page_templates', $encoded_templates );
	return $templates;
}

function almaden_bookster_typst_reconcile_page_template_results( $book_id, $results ) {
	$raw_templates = function_exists( 'get_post_meta' )
		? get_post_meta( (int) $book_id, '_almaden_page_templates', true )
		: array();
	$raw_templates_array = $raw_templates;
	if ( is_string( $raw_templates_array ) && '' !== trim( $raw_templates_array ) ) {
		$decoded = json_decode( $raw_templates_array, true );
		$raw_templates_array = is_array( $decoded ) ? $decoded : array();
	} elseif ( ! is_array( $raw_templates_array ) ) {
		$raw_templates_array = array();
	}
	$templates = almaden_bookster_typst_normalize_page_templates( $raw_templates );
	$result_map = array();
	foreach ( (array) $results as $result ) {
		$instance_id = almaden_bookster_typst_page_template_clean_instance_id( $result['instance_id'] ?? '' );
		if ( '' !== $instance_id ) {
			$result_map[ $instance_id ] = $result;
		}
	}

	$raw_snapshot = function_exists( 'wp_json_encode' )
		? wp_json_encode( $raw_templates_array )
		: json_encode( $raw_templates_array );
	$normalized_snapshot = function_exists( 'wp_json_encode' )
		? wp_json_encode( $templates )
		: json_encode( $templates );
	$changed = $raw_snapshot !== $normalized_snapshot;
	$removed = false;
	foreach ( $templates as $index => &$template ) {
		$instance_id = (string) ( $template['instance_id'] ?? $template['id'] ?? '' );
		$result = $result_map[ $instance_id ] ?? null;
		if ( ! is_array( $result ) || empty( $result['applied'] ) ) {
			$reason = (string) ( $result['debug']['reason'] ?? '' );
			if ( '' === (string) ( $template['anchor']['flow_id'] ?? '' ) && 'no_rows_for_legacy_page' === $reason ) {
				unset( $templates[ $index ] );
				$removed = true;
			}
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
	if ( $removed ) {
		$templates = array_values( $templates );
	}

	return ( $changed || $removed ) ? almaden_bookster_typst_save_page_templates( $book_id, $templates ) : $templates;
}
