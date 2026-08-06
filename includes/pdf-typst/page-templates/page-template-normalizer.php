<?php
/**
 * Validates the persisted page-template collection before Typst consumes it.
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'ALMADEN_TYPST_TESTING' ) ) {
	exit;
}

function almaden_bookster_typst_normalize_page_templates( $value ) {
	if ( is_string( $value ) && '' !== trim( $value ) ) {
		$decoded = json_decode( $value, true );
		$value   = is_array( $decoded ) ? $decoded : array();
	}

	if ( ! is_array( $value ) ) {
		return array();
	}

	$normalized = array();
	$seen_pages = array();
	foreach ( $value as $entry ) {
		if ( ! is_array( $entry ) ) {
			continue;
		}

		$template_id = strtolower( preg_replace( '/[^A-Za-z0-9_-]/', '', (string) ( $entry['template_id'] ?? '' ) ) );
		$page_number = isset( $entry['page_number'] ) && is_numeric( $entry['page_number'] )
			? (int) $entry['page_number']
			: 0;
		$definition = almaden_bookster_typst_get_page_template_definition( $template_id );

		if ( ! $definition || $page_number < 1 || isset( $seen_pages[ $page_number ] ) ) {
			continue;
		}

		$seen_pages[ $page_number ] = true;
		$template_entry_id = preg_replace( '/[^A-Za-z0-9_-]/', '', (string) ( $entry['id'] ?? '' ) );
		if ( '' === $template_entry_id ) {
			$template_entry_id = 'page-' . $page_number . '-' . $template_id;
		}
		$normalized[] = array(
			'id'          => $template_entry_id,
			'page_number' => $page_number,
			'template_id' => $template_id,
			'placeholder' => array(
				'enabled' => ! isset( $entry['placeholder']['enabled'] ) || ! empty( $entry['placeholder']['enabled'] ),
			),
			'slots'      => almaden_bookster_typst_page_template_normalize_slots( $template_id, $entry['slots'] ?? array() ),
		);
	}

	usort(
		$normalized,
		static function ( $left, $right ) {
			return $left['page_number'] <=> $right['page_number'];
		}
	);

	return $normalized;
}

function almaden_bookster_typst_page_templates_from_settings( $settings ) {
	$settings = is_array( $settings ) ? $settings : array();
	return almaden_bookster_typst_normalize_page_templates( $settings['page_templates'] ?? array() );
}
