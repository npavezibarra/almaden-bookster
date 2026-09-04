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

function almaden_bookster_typst_page_template_blank_ids( $source ) {
	preg_match_all( '/<(almaden-blank-[a-z0-9-]+)>/', (string) $source, $matches );
	return array_values( array_unique( $matches[1] ?? array() ) );
}

function almaden_bookster_typst_page_template_is_blank_anchor( $flow_id ) {
	return 1 === preg_match( '/^almaden-blank-[a-z0-9-]+$/', (string) $flow_id );
}

/**
 * Add parity blanks to the same physical-page report used by content blocks.
 *
 * A transition exists only when the next chapter starts two pages after the
 * parity marker. The first-page exception mirrors almaden-is-chapter-transition-page().
 */
function almaden_bookster_typst_page_template_transition_report_entries( $source, $context = array() ) {
	$context = is_array( $context ) ? $context : array();
	$unit = in_array( $context['unit'] ?? 'cm', array( 'mm', 'cm', 'in', 'pt' ), true ) ? ( $context['unit'] ?? 'cm' ) : 'cm';
	$content_top = round( max( 0, (float) ( $context['content_top'] ?? 0 ) ), 4 ) . $unit;
	$inside = round( max( 0, (float) ( $context['margin_inside'] ?? 0 ) ), 4 ) . $unit;
	$outside = round( max( 0, (float) ( $context['margin_outside'] ?? 0 ) ), 4 ) . $unit;
	$entries = array();

	$get_position_logic = static function ( $selector ) use ( $inside, $outside, $content_top ) {
		return ' let break-mark = query(' . $selector . ').first();' .
			' let break-page = break-mark.location().page();' .
			' let break-pos = break-mark.location().position();' .
			' let page-start-x = if calc.odd(break-page) { ' . $inside . ' } else { ' . $outside . ' };' .
			' let at-page-start = break-pos.y <= ' . $content_top . ' + 0.5pt and (calc.abs(break-pos.x - page-start-x) <= 0.5pt or break-pos.x == 0pt);';
	};

	foreach ( almaden_bookster_typst_page_template_transition_ids( $source ) as $transition_id ) {
		$selector = '<' . $transition_id . '>';
		$page_expression = 'if query(' . $selector . ').len() > 0 {' .
			$get_position_logic( $selector ) .
			' let starts = query(<almaden-chapter-start>);' .
			' if break-page == 1 and starts.any(mark => mark.location().page() == 2) { 1 }' .
			' else if starts.any(mark => mark.location().page() == break-page + 2) { break-page + 1 }' .
			' else if at-page-start and starts.any(mark => mark.location().page() == break-page + 1) { break-page }' .
			' else { none }' .
		'} else { none }';
		$marker_page_expression = 'if query(' . $selector . ').len() > 0 { query(' . $selector . ').first().location().page() } else { none }';
		$entries[] = '(id: "' . $transition_id . '", page: ' . $page_expression . ', marker_page: ' . $marker_page_expression . ', x: 0pt, y: 0pt, kind: "transition")';
	}
	foreach ( almaden_bookster_typst_page_template_blank_ids( $source ) as $blank_id ) {
		$selector = '<' . $blank_id . '>';
		$page_expression = 'if query(' . $selector . ').len() > 0 {' .
			$get_position_logic( $selector ) .
			' if at-page-start { break-page } else { break-page + 1 }' .
		'} else { none }';
		$marker_page_expression = 'if query(' . $selector . ').len() > 0 { query(' . $selector . ').first().location().page() } else { none }';
		$entries[] = '(id: "' . $blank_id . '", page: ' . $page_expression . ', marker_page: ' . $marker_page_expression . ', x: 0pt, y: 0pt, kind: "blank")';
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
	} elseif ( 'image-left-split' === $mode ) {
		$replacement = almaden_bookster_typst_page_template_render_image_left_replacement( $gap, '', $placeholder );
	} else {
		$replacement = almaden_bookster_typst_page_template_render_split_replacement( $gap, '', $placeholder );
	}

	$prefix = "#page(columns: 1)[\n";
	if ( 0 === strpos( $replacement, $prefix ) && "]\n" === substr( $replacement, -2 ) ) {
		$body = substr( $replacement, strlen( $prefix ), -2 );
		return "#place(top + left)[\n" . $body . "\n]\n";
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

function almaden_bookster_typst_apply_blank_page_template( $source, $context, $template, &$assets = array(), &$debug = array() ) {
	$flow_id = (string) ( $template['anchor']['flow_id'] ?? '' );
	if ( ! almaden_bookster_typst_page_template_is_blank_anchor( $flow_id ) ) {
		$debug = array( 'reason' => 'not_blank_anchor' );
		return (string) $source;
	}

	$pattern = '/(#metadata\("' . preg_quote( $flow_id, '/' ) . '"\) <' . preg_quote( $flow_id, '/' ) . '>\R)/';
	if ( 1 !== preg_match( $pattern, (string) $source ) ) {
		$debug = array( 'reason' => 'blank_marker_not_found', 'anchor' => $flow_id );
		return (string) $source;
	}

	$body = almaden_bookster_typst_page_template_render_transition( $template, $context, $assets );
	$updated = preg_replace_callback(
		$pattern,
		static function ( $match ) use ( $body ) {
			return $match[1] . $body . "\n";
		},
		(string) $source,
		1
	);
	$debug = array(
		'reason' => $updated !== $source ? 'applied' : 'blank_source_unchanged',
		'mode'   => strtolower( (string) ( almaden_bookster_typst_get_page_template_definition( $template['template_id'] ?? '' )['layout'] ?? 'split' ) ),
		'anchor' => $flow_id,
	);
	return is_string( $updated ) ? $updated : (string) $source;
}
