<?php
/**
 * Typst fragments for page-template image placeholders.
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'ALMADEN_TYPST_TESTING' ) ) {
	exit;
}

function almaden_bookster_typst_page_template_placeholder( $template, $context = array(), &$assets = array() ) {
	$template_id = is_array( $template ) ? ( $template['template_id'] ?? '' ) : '';
	if ( '' === $template_id ) {
		return '';
	}

	$slots = almaden_bookster_typst_page_template_normalize_slots( $template_id, $template['slots'] ?? array() );
	if ( empty( $slots ) ) {
		$slots = array(
			array(
				'id'    => 'image-1',
				'label' => 'Imagen 1',
				'kind'  => 'image',
			),
		);
	}

	if ( 1 === count( $slots ) ) {
		return almaden_bookster_typst_page_template_render_slot( $template, $slots[0], $assets );
	}

	$gap = round( (float) ( $context['columns_gap'] ?? 0.8 ), 4 ) . ( $context['unit'] ?? 'cm' );
	$row_sizes = array_fill( 0, count( $slots ), '1fr' );
	$rows = array();
	foreach ( $slots as $slot ) {
		$rows[] = "[\n" . almaden_bookster_typst_page_template_render_slot( $template, $slot, $assets ) . "\n]";
	}

	$output = '#grid(columns: (1fr,), rows: (' . implode( ', ', $row_sizes ) . '), gutter: ' . $gap . ')';
	foreach ( $rows as $row ) {
		$output .= "\n" . $row;
	}

	return $output . "\n";
}
