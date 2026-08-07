<?php
/**
 * Normalizes the persisted page-style collection.
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'ALMADEN_TYPST_TESTING' ) ) {
	exit;
}

function almaden_bookster_typst_page_style_clean_instance_id( $value ) {
	return strtolower( preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $value ) );
}

function almaden_bookster_typst_page_style_default_background() {
	return array(
		'type' => 'color',
		'color' => '#ffffff',
		'gradient' => array(
			'kind' => 'linear',
			'angle' => 135,
			'stops' => array(
				array(
					'color' => '#ffffff',
					'position' => 0,
				),
				array(
					'color' => '#f3f4f6',
					'position' => 100,
				),
			),
		),
		'image' => array(
			'attachment_id' => 0,
			'url' => '',
			'preview_url' => '',
			'original_url' => '',
			'fit' => 'cover',
			'position' => 'center',
		),
		'overlay' => array(
			'color' => '#000000',
			'opacity' => 0.35,
		),
	);
}

function almaden_bookster_typst_page_style_default_text_colors() {
	return array(
		'content' => '#111111',
		'header' => '#111111',
		'footer' => '#111111',
		'opening' => '#111111',
	);
}

function almaden_bookster_typst_page_style_normalize_color( $value, $fallback ) {
	$value = strtolower( trim( (string) $value ) );
	if ( preg_match( '/^#(?:[0-9a-f]{3}|[0-9a-f]{6}|[0-9a-f]{8})$/', $value ) ) {
		return $value;
	}

	return strtolower( (string) $fallback );
}

function almaden_bookster_typst_page_style_normalize_background( $value ) {
	$value = is_array( $value ) ? $value : array();
	$defaults = almaden_bookster_typst_page_style_default_background();
	$type = strtolower( trim( (string) ( $value['type'] ?? $defaults['type'] ) ) );
	$type = in_array( $type, array( 'color', 'gradient', 'image' ), true ) ? $type : $defaults['type'];

	$gradient = is_array( $value['gradient'] ?? null ) ? $value['gradient'] : array();
	$stops = array();
	foreach ( array_values( (array) ( $gradient['stops'] ?? $defaults['gradient']['stops'] ) ) as $stop ) {
		$stop = is_array( $stop ) ? $stop : array();
		$stops[] = array(
			'color' => almaden_bookster_typst_page_style_normalize_color( $stop['color'] ?? '', '#000000' ),
			'position' => max( 0, min( 100, (float) ( $stop['position'] ?? 0 ) ) ),
		);
	}
	if ( empty( $stops ) ) {
		$stops = $defaults['gradient']['stops'];
	}

	$image = is_array( $value['image'] ?? null ) ? $value['image'] : array();
	$overlay = is_array( $value['overlay'] ?? null ) ? $value['overlay'] : array();
	return array(
		'type' => $type,
		'color' => almaden_bookster_typst_page_style_normalize_color( $value['color'] ?? $defaults['color'], $defaults['color'] ),
		'gradient' => array(
			'kind' => 'linear' === strtolower( (string) ( $gradient['kind'] ?? $defaults['gradient']['kind'] ) ) ? 'linear' : 'linear',
			'angle' => max( 0, min( 360, (float) ( $gradient['angle'] ?? $defaults['gradient']['angle'] ) ) ),
			'stops' => $stops,
		),
		'image' => array(
			'attachment_id' => max( 0, (int) ( $image['attachment_id'] ?? $defaults['image']['attachment_id'] ) ),
			'url' => function_exists( 'esc_url_raw' )
				? esc_url_raw( (string) ( $image['url'] ?? $defaults['image']['url'] ) )
				: sanitize_text_field( (string) ( $image['url'] ?? $defaults['image']['url'] ) ),
			'preview_url' => function_exists( 'esc_url_raw' )
				? esc_url_raw( (string) ( $image['preview_url'] ?? $defaults['image']['preview_url'] ) )
				: sanitize_text_field( (string) ( $image['preview_url'] ?? $defaults['image']['preview_url'] ) ),
			'original_url' => function_exists( 'esc_url_raw' )
				? esc_url_raw( (string) ( $image['original_url'] ?? $defaults['image']['original_url'] ) )
				: sanitize_text_field( (string) ( $image['original_url'] ?? $defaults['image']['original_url'] ) ),
			'fit' => in_array( (string) ( $image['fit'] ?? $defaults['image']['fit'] ), array( 'cover', 'contain', 'fill', 'none' ), true ) ? (string) ( $image['fit'] ?? $defaults['image']['fit'] ) : $defaults['image']['fit'],
			'position' => sanitize_text_field( (string) ( $image['position'] ?? $defaults['image']['position'] ) ),
		),
		'overlay' => array(
			'color' => almaden_bookster_typst_page_style_normalize_color( $overlay['color'] ?? $defaults['overlay']['color'], $defaults['overlay']['color'] ),
			'opacity' => max( 0, min( 1, (float) ( $overlay['opacity'] ?? $defaults['overlay']['opacity'] ) ) ),
		),
	);
}

function almaden_bookster_typst_page_style_instance_id( $entry, $page_number ) {
	$entry = is_array( $entry ) ? $entry : array();
	$instance_id = almaden_bookster_typst_page_style_clean_instance_id(
		$entry['instance_id'] ?? $entry['id'] ?? ''
	);
	if ( '' !== $instance_id ) {
		return $instance_id;
	}

	$fingerprint_data = array( (int) $page_number, $entry['style'] ?? array() );
	$fingerprint = function_exists( 'wp_json_encode' )
		? wp_json_encode( $fingerprint_data )
		: json_encode( $fingerprint_data );

	return 'sty-' . substr( hash( 'sha256', (string) $fingerprint ), 0, 20 );
}

function almaden_bookster_typst_page_style_normalize( $value ) {
	if ( is_string( $value ) && '' !== trim( $value ) ) {
		$decoded = json_decode( $value, true );
		$value   = is_array( $decoded ) ? $decoded : array();
	}

	if ( ! is_array( $value ) ) {
		return array();
	}

	$normalized = array();
	$seen_instances = array();
	$text_defaults = almaden_bookster_typst_page_style_default_text_colors();
	foreach ( $value as $entry ) {
		if ( ! is_array( $entry ) ) {
			continue;
		}

		$page_number = isset( $entry['page_number'] ) && is_numeric( $entry['page_number'] )
			? (int) $entry['page_number']
			: 0;
		if ( $page_number < 1 ) {
			continue;
		}

		$instance_id = almaden_bookster_typst_page_style_instance_id( $entry, $page_number );
		if ( isset( $seen_instances[ $instance_id ] ) ) {
			continue;
		}
		$seen_instances[ $instance_id ] = true;

		$resolved_page = isset( $entry['resolved_page'] ) && is_numeric( $entry['resolved_page'] )
			? max( 1, (int) $entry['resolved_page'] )
			: $page_number;
		$text_colors = isset( $entry['style']['text_colors'] ) && is_array( $entry['style']['text_colors'] )
			? $entry['style']['text_colors']
			: array();

		$normalized[] = array(
			'id' => $instance_id,
			'instance_id' => $instance_id,
			'page_number' => $page_number,
			'resolved_page' => $resolved_page,
			'anchor' => function_exists( 'almaden_bookster_typst_page_template_normalize_anchor' )
				? almaden_bookster_typst_page_template_normalize_anchor( $entry['anchor'] ?? array() )
				: array( 'flow_id' => '' ),
			'style' => array(
					'background' => almaden_bookster_typst_page_style_normalize_background( $entry['style']['background'] ?? array() ),
					'text_colors' => array(
						'content' => almaden_bookster_typst_page_style_normalize_color( $text_colors['content'] ?? $text_defaults['content'], $text_defaults['content'] ),
						'header' => almaden_bookster_typst_page_style_normalize_color( $text_colors['header'] ?? $text_defaults['header'], $text_defaults['header'] ),
						'footer' => almaden_bookster_typst_page_style_normalize_color( $text_colors['footer'] ?? $text_defaults['footer'], $text_defaults['footer'] ),
						'opening' => almaden_bookster_typst_page_style_normalize_color( $text_colors['opening'] ?? $text_defaults['opening'], $text_defaults['opening'] ),
					),
				),
			);
	}

	usort(
		$normalized,
		static function ( $left, $right ) {
			$left_order = function_exists( 'almaden_bookster_typst_page_template_flow_order' )
				? almaden_bookster_typst_page_template_flow_order( $left['anchor']['flow_id'] ?? '' )
				: PHP_INT_MAX;
			$right_order = function_exists( 'almaden_bookster_typst_page_template_flow_order' )
				? almaden_bookster_typst_page_template_flow_order( $right['anchor']['flow_id'] ?? '' )
				: PHP_INT_MAX;

			return $left_order === $right_order
				? $left['resolved_page'] <=> $right['resolved_page']
				: $left_order <=> $right_order;
		}
	);

	return $normalized;
}

function almaden_bookster_typst_page_styles_from_settings( $settings ) {
	$settings = is_array( $settings ) ? $settings : array();
	return almaden_bookster_typst_page_style_normalize( $settings['page_styles'] ?? array() );
}
