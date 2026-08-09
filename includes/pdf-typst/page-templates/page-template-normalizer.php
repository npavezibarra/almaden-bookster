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
	$seen_instances = array();
	foreach ( $value as $entry ) {
		if ( ! is_array( $entry ) ) {
			continue;
		}

		$template_id = strtolower( preg_replace( '/[^A-Za-z0-9_-]/', '', (string) ( $entry['template_id'] ?? '' ) ) );
		$page_number = isset( $entry['page_number'] ) && is_numeric( $entry['page_number'] )
			? (int) $entry['page_number']
			: 0;
		$definition = almaden_bookster_typst_get_page_template_definition( $template_id );

		if ( ! $definition || $page_number < 1 ) {
			continue;
		}

		$instance_id = almaden_bookster_typst_page_template_instance_id( $entry, $template_id, $page_number );
		if ( isset( $seen_instances[ $instance_id ] ) ) {
			continue;
		}
		$seen_instances[ $instance_id ] = true;
		$resolved_page = isset( $entry['resolved_page'] ) && is_numeric( $entry['resolved_page'] )
			? max( 1, (int) $entry['resolved_page'] )
			: $page_number;
		$normalized[] = array(
			'id'          => $instance_id,
			'instance_id' => $instance_id,
			'page_number' => $page_number,
			'resolved_page' => $resolved_page,
			'anchor'      => almaden_bookster_typst_page_template_normalize_anchor( $entry['anchor'] ?? array() ),
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
			$left_page = (int) ( $left['page_number'] ?? $left['resolved_page'] ?? 0 );
			$right_page = (int) ( $right['page_number'] ?? $right['resolved_page'] ?? 0 );
			return $left_page === $right_page
				? strcmp( (string) ( $left['instance_id'] ?? '' ), (string) ( $right['instance_id'] ?? '' ) )
				: $left_page <=> $right_page;
		}
	);

	$seen_flow_ids = array();
	foreach ( $normalized as &$template ) {
		$flow_id = (string) ( $template['anchor']['flow_id'] ?? '' );
		if ( '' === $flow_id ) {
			continue;
		}
		if ( isset( $seen_flow_ids[ $flow_id ] ) ) {
			$template['anchor'] = array( 'flow_id' => '' );
			$template['resolved_page'] = (int) ( $template['page_number'] ?? $template['resolved_page'] ?? 0 );
			continue;
		}
		$seen_flow_ids[ $flow_id ] = true;
	}
	unset( $template );

	usort(
		$normalized,
		static function ( $left, $right ) {
			$left_order = almaden_bookster_typst_page_template_flow_order( $left['anchor']['flow_id'] ?? '' );
			$right_order = almaden_bookster_typst_page_template_flow_order( $right['anchor']['flow_id'] ?? '' );
			return $left_order === $right_order
				? $left['resolved_page'] <=> $right['resolved_page']
				: $left_order <=> $right_order;
		}
	);

	return $normalized;
}

function almaden_bookster_typst_page_templates_from_settings( $settings ) {
	$settings = is_array( $settings ) ? $settings : array();
	return almaden_bookster_typst_normalize_page_templates( $settings['page_templates'] ?? array() );
}
