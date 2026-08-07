<?php
/**
 * Composition boundary for physical page templates in Typst documents.
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'ALMADEN_TYPST_TESTING' ) ) {
	exit;
}

function almaden_bookster_typst_page_template_context( $settings ) {
	$settings = is_array( $settings ) ? $settings : array();
	return array(
		'templates' => almaden_bookster_typst_page_templates_from_settings( $settings ),
		'columns_count' => max( 1, min( 4, (int) ( $settings['page_columns_count'] ?? 2 ) ) ),
		'columns_gap' => max( 0, min( 20, (float) ( $settings['page_columns_gap'] ?? 0.8 ) ) ),
		'unit' => in_array( $settings['unit'] ?? 'cm', array( 'mm', 'cm', 'in', 'pt' ), true ) ? $settings['unit'] : 'cm',
	);
}

function almaden_bookster_typst_compose_page_templates( $source, $context, &$assets = array() ) {
	$source = (string) $source;
	if ( empty( $context['templates'] ) ) {
		return $source;
	}

	$marker_index = 0;
	$source = preg_replace_callback(
		'/^(\s*)#par\[/m',
		static function ( $match ) use ( &$marker_index ) {
			++$marker_index;
			$marker = 'almaden-flow-' . $marker_index;
			return $match[1] . '#metadata("' . $marker . '") <' . $marker . ">\n" . $match[1] . '#par[';
		},
		$source
	);

	if ( $marker_index < 1 ) {
		return $source;
	}

	$report_entries = array();
	for ( $index = 1; $index <= $marker_index; ++$index ) {
		$marker = 'almaden-flow-' . $index;
		$report_entries[] = '(id: "' . $marker . '", page: if query(<' . $marker . '>).len() > 0 { query(<' . $marker . '>).first().location().page() } else { none }, x: if query(<' . $marker . '>).len() > 0 { query(<' . $marker . '>).first().location().position().x } else { none }, y: if query(<' . $marker . '>).len() > 0 { query(<' . $marker . '>).first().location().position().y } else { none })';
	}

	return $source . "\n#context [#metadata((" . implode( ', ', $report_entries ) . ")) <almaden-flow-report>]\n";
}

function almaden_bookster_typst_page_template_source_blocks( $source ) {
	$source = (string) $source;
	$lines = explode( "\n", $source );
	$blocks = array();
	$ordered_ids = array();
	$line_offsets = array();
	$offset = 0;
	foreach ( $lines as $line ) {
		$line_offsets[] = $offset;
		$offset += strlen( $line ) + 1;
	}

	for ( $index = 0, $count = count( $lines ); $index < $count; ++$index ) {
		$metadata_line = rtrim( $lines[ $index ], "\r" );
		if ( ! preg_match( '/^[ \t]*#metadata\("(almaden-flow-[0-9]+)"\) <\\1>$/', trim( $metadata_line ), $metadata_match ) ) {
			continue;
		}

		$paragraph_index = $index + 1;
		while ( $paragraph_index < $count && '' === trim( $lines[ $paragraph_index ] ) ) {
			++$paragraph_index;
		}
		if ( $paragraph_index >= $count || ! preg_match( '/^[ \t]*#par\[/', $lines[ $paragraph_index ], $paragraph_match, PREG_OFFSET_CAPTURE ) ) {
			continue;
		}

		$paragraph_start = $line_offsets[ $paragraph_index ] + $paragraph_match[0][1];
		$open_bracket = strpos( $source, '[', $paragraph_start );
		$depth = 0;
		$escaped = false;
		$paragraph_end = null;
		for ( $cursor = $open_bracket, $source_length = strlen( $source ); $cursor < $source_length; ++$cursor ) {
			$character = $source[ $cursor ];
			if ( $escaped ) {
				$escaped = false;
				continue;
			}
			if ( '\\' === $character ) {
				$escaped = true;
				continue;
			}
			if ( '[' === $character ) {
				++$depth;
			} elseif ( ']' === $character && 0 === --$depth ) {
				$paragraph_end = $cursor + 1;
				break;
			}
		}
		if ( null === $paragraph_end ) {
			continue;
		}

		$block_id = $metadata_match[1];
		$blocks[ $block_id ] = array(
			'text'   => substr( $source, $line_offsets[ $index ], $paragraph_end - $line_offsets[ $index ] ),
			'offset' => $line_offsets[ $index ],
		);
		$ordered_ids[] = $block_id;
		while ( $index + 1 < $count && $line_offsets[ $index + 1 ] < $paragraph_end ) {
			++$index;
		}
	}
	return array( $blocks, $ordered_ids );
}

function almaden_bookster_typst_page_template_apply_blocks( $source, $context, $template, $blocks, $ordered_ids, $page_ids, $left_ids, &$debug = array(), $layout = array(), &$assets = array() ) {
	$page_ids = array_values( array_filter( $ordered_ids, static function ( $id ) use ( $page_ids ) {
		return in_array( $id, $page_ids, true );
	} ) );
	$left_ids = array_values( array_filter( $ordered_ids, static function ( $id ) use ( $left_ids ) {
		return in_array( $id, $left_ids, true );
	} ) );
	if ( empty( $page_ids ) || empty( $left_ids ) ) {
		$debug = array(
			'reason' => 'page_blocks_not_found_in_source',
		);
		return (string) $source;
	}

	$first = $page_ids[0];
	$last  = $page_ids[ count( $page_ids ) - 1 ];
	$first_offset = $blocks[ $first ]['offset'];
	$last_end = $blocks[ $last ]['offset'] + strlen( $blocks[ $last ]['text'] );
	if ( ! empty( $layout['left_body'] ) ) {
		$left_body = (string) $layout['left_body'];
		$deferred_body = (string) ( $layout['deferred_body'] ?? '' );
		$left_ids = array_values( (array) ( $layout['left_ids'] ?? $left_ids ) );
		$deferred_ids = array_values( (array) ( $layout['deferred_ids'] ?? array() ) );
	} else {
		$left_body = implode( "\n", array_map( static function ( $id ) use ( $blocks ) {
			return $blocks[ $id ]['text'];
		}, $left_ids ) );
		$deferred_ids = array_values( array_diff( $page_ids, $left_ids ) );
		$deferred_body = implode( "\n", array_map( static function ( $id ) use ( $blocks ) {
			return $blocks[ $id ]['text'];
		}, $deferred_ids ) );
	}
	$gap = round( (float) $context['columns_gap'], 4 ) . $context['unit'];
	$placeholder = almaden_bookster_typst_page_template_placeholder( $template, $context, $assets );

	/*
	 * #page creates one physical page outside the book's normal multi-column
	 * flow. The right-column blocks are deliberately emitted afterwards so
	 * Typst paginates them on following regular pages instead of overlaying
	 * them under the image placeholder.
	 */
	$replacement = "#page(columns: 1)[\n#box(width: 100%, height: 100%)[\n#grid(columns: (1fr, 1fr), rows: (1fr,), gutter: $gap, [\n#block(width: 100%)[\n#almaden-page-styled(\"content\")[\n$left_body\n]\n]\n], [\n#block(width: 100%, height: 100%)[\n$placeholder\n]\n])\n]\n]\n";
	if ( '' !== $deferred_body ) {
		$replacement .= "\n$deferred_body";
	}
	$debug = array(
		'reason'       => 'applied',
		'page_ids'     => $page_ids,
		'left_ids'     => $left_ids,
		'deferred_ids' => $deferred_ids,
		'body_preview' => substr( $left_body, 0, 180 ),
	);

	return substr( $source, 0, $first_offset ) . $replacement . substr( $source, $last_end );
}

function almaden_bookster_typst_page_template_take_slice( $ordered_ids, $start_index, $length ) {
	$ordered_ids = array_values( (array) $ordered_ids );
	$start_index = max( 0, (int) $start_index );
	$length = max( 0, (int) $length );
	if ( 0 === $length || $start_index >= count( $ordered_ids ) ) {
		return array();
	}
	return array_slice( $ordered_ids, $start_index, $length );
}

function almaden_bookster_typst_apply_page_template_flow( $source, $context, $flow_map, $template = null, $word_probe = array(), &$assets = array() ) {
	$GLOBALS['almaden_bookster_typst_page_template_debug'] = array();
	$template = is_array( $template ) ? $template : ( $context['templates'][0] ?? null );
	if ( ! is_array( $template ) || 'one-column-one-image' !== ( $template['template_id'] ?? '' ) ) {
		$GLOBALS['almaden_bookster_typst_page_template_debug'] = array( 'reason' => 'unsupported_template' );
		return (string) $source;
	}

	$target_page = (int) ( $template['page_number'] ?? 0 );
	$target_rows = array_values( array_filter( (array) $flow_map, static function ( $row ) use ( $target_page ) {
		return is_array( $row ) && $target_page === (int) ( $row['page'] ?? 0 );
	} ) );
	if ( $target_page < 1 || empty( $target_rows ) ) {
		$GLOBALS['almaden_bookster_typst_page_template_debug'] = array(
			'reason' => 'no_rows_for_page',
			'page'   => $target_page,
		);
		return (string) $source;
	}

	$left_x = min( array_map( static function ( $row ) { return (float) ( $row['x'] ?? 0 ); }, $target_rows ) );
	$page_ids = array();
	$left_ids = array();
	foreach ( $target_rows as $row ) {
		$id = (string) ( $row['id'] ?? '' );
		if ( '' === $id ) {
			continue;
		}
		$page_ids[] = $id;
		if ( abs( (float) ( $row['x'] ?? 0 ) - $left_x ) < 1 ) {
			$left_ids[] = $id;
		}
	}
	$page_ids = array_values( array_unique( array_filter( $page_ids ) ) );
	$left_ids = array_values( array_unique( array_filter( $left_ids ) ) );
	if ( empty( $page_ids ) || empty( $left_ids ) ) {
		$GLOBALS['almaden_bookster_typst_page_template_debug'] = array(
			'reason'    => 'no_page_or_left_column_ids',
			'page'      => $target_page,
			'left_x'    => $left_x,
			'row_count' => count( $target_rows ),
		);
		return (string) $source;
	}
	list( $blocks, $ordered_ids ) = almaden_bookster_typst_page_template_source_blocks( $source );
	$page_blocks = array_values( array_filter( $ordered_ids, static function ( $id ) use ( $page_ids ) {
		return in_array( $id, $page_ids, true );
	} ) );
	if ( count( $page_blocks ) !== count( $page_ids ) ) {
		$GLOBALS['almaden_bookster_typst_page_template_debug'] = array(
			'reason'       => 'selected_ids_not_found_in_source',
			'page'         => $target_page,
			'selected_ids' => $page_ids,
			'block_ids'    => array_keys( $blocks ),
		);
		return (string) $source;
	}
	$layout = array();
	if ( ! empty( $word_probe['cut'] ) && function_exists( 'almaden_bookster_typst_page_template_fragment_layout' ) ) {
		$layout = almaden_bookster_typst_page_template_fragment_layout( $blocks, $page_blocks, $word_probe['cut'] );
	}
	if ( empty( $layout ) ) {
		/* Keep the safe single-block fallback when Typst cannot provide a word probe. */
		$left_ids = array_slice( $left_ids, 0, 1 );
	} else {
		$left_ids = $layout['left_ids'];
	}

	$debug = array();
	$updated_source = almaden_bookster_typst_page_template_apply_blocks( $source, $context, $template, $blocks, $ordered_ids, $page_ids, $left_ids, $debug, $layout, $assets );
	if ( $updated_source !== $source ) {
		$selected_end_index = array_search( end( $page_blocks ), $ordered_ids, true );
		$GLOBALS['almaden_bookster_typst_page_template_debug'] = array(
			'page'         => $target_page,
			'reason'       => 'applied',
			'page_ids'     => $debug['page_ids'] ?? $page_blocks,
			'left_ids'     => $debug['left_ids'] ?? $left_ids,
			'deferred_ids' => $debug['deferred_ids'] ?? array(),
			'body_preview' => $debug['body_preview'] ?? '',
			'end_index'    => false === $selected_end_index ? null : $selected_end_index,
		);
		return $updated_source;
	}

	$GLOBALS['almaden_bookster_typst_page_template_debug'] = array_merge(
		array( 'page' => $target_page ),
		$debug
	);
	return $source;
}
