<?php
/**
 * Page-template support for blank pages inserted by chapter parity.
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'ALMADEN_TYPST_TESTING' ) ) {
	exit;
}

function almaden_bookster_typst_page_template_is_transition_anchor( $flow_id ) {
	return 1 === preg_match( '/^almaden-transition-[0-9]+$/', (string) $flow_id );
}

function almaden_bookster_typst_page_template_transition_ids( $source ) {
	preg_match_all( '/<((?:almaden-transition-)[0-9]+)>/', (string) $source, $matches );
	return array_values( array_unique( $matches[1] ?? array() ) );
}

/**
 * Add parity blanks to the same physical-page report used by content blocks.
 *
 * A transition exists only when the next chapter starts two pages after the
 * parity marker. The first-page exception mirrors almaden-is-chapter-transition-page().
 */
function almaden_bookster_typst_page_template_transition_report_entries( $source ) {
	$entries = array();
	foreach ( almaden_bookster_typst_page_template_transition_ids( $source ) as $transition_id ) {
		$selector = '<' . $transition_id . '>';
		$page_expression = 'if query(' . $selector . ').len() > 0 {' .
			' let break-page = query(' . $selector . ').first().location().page();' .
			' let starts = query(<almaden-chapter-start>);' .
			' if break-page == 1 and starts.any(mark => mark.location().page() == 2) { 1 }' .
			' else if starts.any(mark => mark.location().page() == break-page + 2) { break-page + 1 }' .
			' else { none }' .
		'} else { none }';
		$marker_page_expression = 'if query(' . $selector . ').len() > 0 { query(' . $selector . ').first().location().page() } else { none }';
		$entries[] = '(id: "' . $transition_id . '", page: ' . $page_expression . ', marker_page: ' . $marker_page_expression . ', x: 0pt, y: 0pt, kind: "transition")';
	}
	return $entries;
}

function almaden_bookster_typst_page_template_render_transition( $template, $context, &$assets = array() ) {
	$definition = almaden_bookster_typst_get_page_template_definition( $template['template_id'] ?? '' );
	$mode = strtolower( (string) ( $definition['layout'] ?? 'split' ) );
	$gap = round( (float) ( $context['columns_gap'] ?? 0.8 ), 4 ) . ( $context['unit'] ?? 'cm' );
	$placeholder = almaden_bookster_typst_page_template_placeholder(
		$template,
		$context,
		$assets,
		$context['asset_mode'] ?? 'original'
	);

	if ( 'full' === $mode ) {
		$replacement = almaden_bookster_typst_page_template_render_full_replacement( $placeholder );
	} elseif ( 'upper-bottom-split' === $mode ) {
		$replacement = almaden_bookster_typst_page_template_render_upper_bottom_replacement( $gap, '', $placeholder, '' );
	} else {
		$replacement = almaden_bookster_typst_page_template_render_split_replacement( $gap, '', $placeholder );
	}

	$prefix = "#page(columns: 1)[\n";
	if ( 0 === strpos( $replacement, $prefix ) && "]\n" === substr( $replacement, -2 ) ) {
		return substr( $replacement, strlen( $prefix ), -2 ) . "\n";
	}
	return $replacement;
}

function almaden_bookster_typst_apply_transition_page_template( $source, $context, $template, &$assets = array(), &$debug = array() ) {
	$flow_id = (string) ( $template['anchor']['flow_id'] ?? '' );
	if ( ! almaden_bookster_typst_page_template_is_transition_anchor( $flow_id ) ) {
		$debug = array( 'reason' => 'not_transition_anchor' );
		return (string) $source;
	}

	$pattern = '/(#metadata\("[^"\r\n]*"\) <' . preg_quote( $flow_id, '/' ) . '>\R)(#pagebreak\(to: "even"\))/';
	if ( 1 !== preg_match( $pattern, (string) $source ) ) {
		$debug = array( 'reason' => 'transition_marker_not_found', 'anchor' => $flow_id );
		return (string) $source;
	}

	$target_page = (int) ( $template['page_number'] ?? 0 );
	$marker_page = (int) ( $template['_transition_marker_page'] ?? $target_page );
	$leading_break = $target_page > $marker_page ? "#pagebreak(to: \"odd\")\n" : '';
	$replacement = '$1' . $leading_break . almaden_bookster_typst_page_template_render_transition( $template, $context, $assets ) . "\n" . '$2';
	$updated = preg_replace( $pattern, $replacement, (string) $source, 1 );
	$debug = array(
		'reason' => $updated !== $source ? 'applied' : 'transition_source_unchanged',
		'mode'   => strtolower( (string) ( almaden_bookster_typst_get_page_template_definition( $template['template_id'] ?? '' )['layout'] ?? 'split' ) ),
		'anchor' => $flow_id,
		'marker_page' => $marker_page,
	);
	return is_string( $updated ) ? $updated : (string) $source;
}
