<?php
/**
 * Stable identity and content-anchor helpers for physical page templates.
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'ALMADEN_TYPST_TESTING' ) ) {
	exit;
}

function almaden_bookster_typst_page_template_clean_instance_id( $value ) {
	return strtolower( preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $value ) );
}

function almaden_bookster_typst_page_template_instance_id( $entry, $template_id, $page_number ) {
	$entry = is_array( $entry ) ? $entry : array();
	$instance_id = almaden_bookster_typst_page_template_clean_instance_id(
		$entry['instance_id'] ?? $entry['id'] ?? ''
	);
	if ( '' !== $instance_id ) {
		return $instance_id;
	}

	$fingerprint_data = array( $template_id, (int) $page_number, $entry['slots'] ?? array() );
	$fingerprint = function_exists( 'wp_json_encode' )
		? wp_json_encode( $fingerprint_data )
		: json_encode( $fingerprint_data );
	return 'tpl-' . substr( hash( 'sha256', (string) $fingerprint ), 0, 20 );
}

function almaden_bookster_typst_page_template_normalize_anchor( $value ) {
	$value = is_array( $value ) ? $value : array();
	$flow_id = strtolower( preg_replace( '/[^A-Za-z0-9_-]/', '', (string) ( $value['flow_id'] ?? '' ) ) );
	if ( ! preg_match( '/^almaden-flow-[0-9]+$/', $flow_id ) ) {
		$flow_id = '';
	}

	return array( 'flow_id' => $flow_id );
}

function almaden_bookster_typst_page_template_flow_order( $flow_id ) {
	return preg_match( '/^almaden-flow-([0-9]+)$/', (string) $flow_id, $match )
		? (int) $match[1]
		: PHP_INT_MAX;
}

function almaden_bookster_typst_page_template_anchor_row( $flow_map, $anchor ) {
	$flow_id = (string) ( $anchor['flow_id'] ?? '' );
	if ( '' === $flow_id ) {
		return null;
	}
	foreach ( (array) $flow_map as $row ) {
		if ( is_array( $row ) && $flow_id === (string) ( $row['id'] ?? '' ) ) {
			return $row;
		}
	}
	return null;
}

function almaden_bookster_typst_page_template_first_row_on_page( $flow_map, $page_number ) {
	$rows = array_values( array_filter( (array) $flow_map, static function ( $row ) use ( $page_number ) {
		return is_array( $row ) && (int) ( $row['page'] ?? 0 ) === (int) $page_number;
	} ) );
	usort( $rows, static function ( $left, $right ) {
		return almaden_bookster_typst_page_template_flow_order( $left['id'] ?? '' )
			<=> almaden_bookster_typst_page_template_flow_order( $right['id'] ?? '' );
	} );
	return $rows[0] ?? null;
}

/**
 * Resolve a stored instance against the current Typst flow map.
 *
 * Legacy records without an anchor use their old physical page once and
 * capture its first flow marker. Anchored records never fall back to a stale
 * page number because that could silently attach them to unrelated content.
 */
function almaden_bookster_typst_resolve_page_template( $template, $flow_map ) {
	$template = is_array( $template ) ? $template : array();
	$anchor = almaden_bookster_typst_page_template_normalize_anchor( $template['anchor'] ?? array() );
	$row = almaden_bookster_typst_page_template_anchor_row( $flow_map, $anchor );
	$used_legacy_page = false;

	if ( ! $row && '' === $anchor['flow_id'] ) {
		$row = almaden_bookster_typst_page_template_first_row_on_page(
			$flow_map,
			(int) ( $template['resolved_page'] ?? $template['page_number'] ?? 0 )
		);
		$used_legacy_page = true;
	}

	if ( ! $row ) {
		return array(
			'template' => $template,
			'applied'  => false,
			'reason'   => '' === $anchor['flow_id'] ? 'no_rows_for_legacy_page' : 'anchor_not_found',
		);
	}

	$resolved = $template;
	$resolved['page_number'] = (int) ( $row['page'] ?? 0 );
	$resolved['resolved_page'] = $resolved['page_number'];
	$resolved['anchor'] = array( 'flow_id' => (string) ( $row['id'] ?? '' ) );

	return array(
		'template'        => $resolved,
		'applied'         => true,
		'reason'          => 'resolved',
		'legacy_migrated' => $used_legacy_page,
	);
}
