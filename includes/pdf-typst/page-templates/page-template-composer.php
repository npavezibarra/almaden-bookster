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

	$transition_entries = function_exists( 'almaden_bookster_typst_page_template_transition_report_entries' )
		? almaden_bookster_typst_page_template_transition_report_entries( $source, $context )
		: array();
	if ( $marker_index < 1 && empty( $transition_entries ) ) {
		return $source;
	}

	$report_entries = array();
	for ( $index = 1; $index <= $marker_index; ++$index ) {
		$marker = 'almaden-flow-' . $index;
		$report_entries[] = '(id: "' . $marker . '", page: if query(<' . $marker . '>).len() > 0 { query(<' . $marker . '>).first().location().page() } else { none }, x: if query(<' . $marker . '>).len() > 0 { query(<' . $marker . '>).first().location().position().x } else { none }, y: if query(<' . $marker . '>).len() > 0 { query(<' . $marker . '>).first().location().position().y } else { none })';
	}
	$report_entries = array_merge( $report_entries, $transition_entries );

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
	$page_ids = array_values( (array) $page_ids );
	$left_ids = array_values( (array) $left_ids );
	$page_ids = array_values( array_filter( $ordered_ids, static function ( $id ) use ( $page_ids ) {
		return in_array( $id, $page_ids, true );
	} ) );
	$left_ids = array_values( array_filter( $ordered_ids, static function ( $id ) use ( $left_ids ) {
		return in_array( $id, $left_ids, true );
	} ) );
	$layout_mode = strtolower( (string) ( $layout['mode'] ?? 'split' ) );
	$clipped_text_modes = array( 'split', 'image-left-split', 'upper-bottom-split', 'image-top-two-column-bottom' );
	if ( empty( $page_ids ) || ( in_array( $layout_mode, $clipped_text_modes, true ) && empty( $left_ids ) ) ) {
		$debug = array(
			'reason' => 'page_blocks_not_found_in_source',
			'mode'   => $layout_mode,
		);
		return (string) $source;
	}

	$first = $page_ids[0];
	$last  = $page_ids[ count( $page_ids ) - 1 ];
	$first_offset = $blocks[ $first ]['offset'];
	$last_end = $blocks[ $last ]['offset'] + strlen( $blocks[ $last ]['text'] );
	$pre_body = (string) ( $layout['pre_body'] ?? '' );
	if ( 'full' === $layout_mode ) {
		$left_body = '';
		$deferred_ids = array_values( (array) ( $layout['deferred_ids'] ?? $page_ids ) );
		$deferred_body = (string) ( $layout['deferred_body'] ?? implode( "\n", array_map( static function ( $id ) use ( $blocks ) {
			return $blocks[ $id ]['text'];
		}, $deferred_ids ) ) );
		$overflow_body = '';
	} elseif ( ! empty( $layout['left_body'] ) ) {
		$left_body = (string) $layout['left_body'];
		$deferred_body = (string) ( $layout['deferred_body'] ?? '' );
		$overflow_body = (string) ( $layout['overflow_body'] ?? '' );
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
		$overflow_body = '';
	}
	if ( in_array( $layout_mode, $clipped_text_modes, true ) && array_key_exists( 'measured', $layout ) && empty( $layout['measured'] ) ) {
		$debug = array(
			'reason'       => 'unmeasured_clipped_layout',
			'mode'         => $layout_mode,
			'page_ids'     => $page_ids,
			'left_ids'     => $left_ids,
			'deferred_ids' => $deferred_ids,
		);
		return (string) $source;
	}
	if ( 'upper-bottom-split' === $layout_mode && '' !== trim( $deferred_body ) && array_key_exists( 'right_measured', $layout ) && empty( $layout['right_measured'] ) ) {
		$debug = array(
			'reason'       => 'unmeasured_upper_bottom_right_layout',
			'mode'         => $layout_mode,
			'page_ids'     => $page_ids,
			'left_ids'     => $left_ids,
			'deferred_ids' => $deferred_ids,
		);
		return (string) $source;
	}
	$gap = round( (float) $context['columns_gap'], 4 ) . $context['unit'];
	$placeholder = almaden_bookster_typst_page_template_placeholder( $template, $context, $assets, $context['asset_mode'] ?? 'original' );

	if ( 'full' === $layout_mode ) {
		/*
		 * Full-page image templates occupy the whole content area and reflow
		 * every block that belonged to the selected physical page.
		 */
		$replacement = almaden_bookster_typst_page_template_render_full_replacement( $placeholder );
	} elseif ( 'upper-bottom-split' === $layout_mode ) {
		/*
		 * Upper image templates keep the image fixed on the upper-left area and
		 * let the remaining page text flow beneath it and in the right column.
		 */
		$replacement = almaden_bookster_typst_page_template_render_upper_bottom_replacement( $gap, $left_body, $placeholder, $deferred_body );
	} elseif ( 'image-top-two-column-bottom' === $layout_mode ) {
		$replacement = almaden_bookster_typst_page_template_render_image_top_two_column_bottom_replacement( $gap, $left_body, $placeholder );
	} elseif ( 'four-images-grid' === $layout_mode ) {
		$replacement = almaden_bookster_typst_page_template_render_four_images_replacement( $placeholder );
	} elseif ( 'image-left-split' === $layout_mode ) {
		$replacement = almaden_bookster_typst_page_template_render_image_left_replacement( $gap, $left_body, $placeholder );
	} else {
		/*
		 * The local #page override gives the template the complete content width.
		 * The right-column blocks are emitted afterwards in the book's regular
		 * multi-column flow.
		 */
		$replacement = almaden_bookster_typst_page_template_render_split_replacement( $gap, $left_body, $placeholder );
	}
	if ( '' !== $deferred_body && 'upper-bottom-split' !== $layout_mode ) {
		$replacement .= "\n$deferred_body";
	}
	if ( '' !== trim( (string) ( $overflow_body ?? '' ) ) ) {
		$replacement .= "\n$overflow_body";
	}
	if ( '' !== trim( $pre_body ) ) {
		$replacement = $pre_body . "\n" . $replacement;
	}
	$debug = array(
		'reason'       => 'applied',
		'mode'         => $layout_mode,
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

function almaden_bookster_typst_page_template_layout_mode( $template ) {
	$template_id = is_array( $template ) ? (string) ( $template['template_id'] ?? '' ) : '';
	$definition = '' !== $template_id ? almaden_bookster_typst_get_page_template_definition( $template_id ) : null;
	$mode = strtolower( (string) ( $definition['layout'] ?? 'split' ) );
	return in_array( $mode, array( 'split', 'image-left-split', 'upper-bottom-split', 'image-top-two-column-bottom', 'four-images-grid', 'full' ), true ) ? $mode : 'split';
}

function almaden_bookster_typst_page_template_render_split_replacement( $gap, $left_body, $placeholder ) {
	return "#page(columns: 1)[\n#box(width: 100%, height: 100%)[\n#grid(columns: (1fr, 1fr), rows: (1fr,), gutter: $gap)[\n#block(width: 100%, height: 100%, clip: true)[\n#almaden-page-styled(\"content\")[\n$left_body\n]\n]\n][\n#block(width: 100%, height: 100%)[\n$placeholder\n]\n]\n]\n]\n";
}

function almaden_bookster_typst_page_template_render_image_left_replacement( $gap, $left_body, $placeholder ) {
	return "#page(columns: 1)[\n#box(width: 100%, height: 100%)[\n#grid(columns: (1fr, 1fr), rows: (1fr,), gutter: $gap)[\n#block(width: 100%, height: 100%)[\n$placeholder\n]\n][\n#block(width: 100%, height: 100%, clip: true)[\n#almaden-page-styled(\"content\")[\n$left_body\n]\n]\n]\n]\n]\n";
}

function almaden_bookster_typst_page_template_render_full_replacement( $placeholder ) {
	return "#page(columns: 1)[\n#box(width: 100%, height: 100%)[\n#almaden-page-styled(\"content\")[\n$placeholder\n]\n]\n]\n";
}

function almaden_bookster_typst_page_template_render_four_images_replacement( $placeholder ) {
	return "#page(columns: 1)[\n#box(width: 100%, height: 100%)[\n$placeholder\n]\n]\n";
}

function almaden_bookster_typst_page_template_render_upper_bottom_replacement( $gap, $left_body, $placeholder, $deferred_body, $left_probe = false, $right_probe = false ) {
	$left_probe_marker = $left_probe ? "\n#place(bottom + left)[#metadata(\"almaden-template-probe-bottom\") <almaden-template-probe-bottom>]" : '';
	$right_probe_marker = $right_probe ? "\n#place(bottom + left)[#metadata(\"almaden-template-probe-bottom\") <almaden-template-probe-bottom>]" : '';
	return "#page(columns: 1)[\n#box(width: 100%, height: 100%)[\n#grid(columns: (2.15fr, 0.95fr), rows: (1fr,), gutter: $gap)[\n#block(width: 100%, height: 100%)[\n#grid(columns: (1fr,), rows: (42%, 1fr), gutter: $gap)[\n#block(width: 100%, height: 100%)[\n$placeholder\n]\n#block(width: 100%, height: 100%, clip: true)[$left_probe_marker\n#columns(2, gutter: $gap)[\n#almaden-page-styled(\"content\")[\n$left_body\n]\n]\n]\n]\n]\n][\n#block(width: 100%, height: 100%, clip: true)[$right_probe_marker\n#almaden-page-styled(\"content\")[\n$deferred_body\n]\n]\n]\n]\n]\n";
}

function almaden_bookster_typst_page_template_render_image_top_two_column_bottom_replacement( $gap, $left_body, $placeholder, $include_probe_marker = false ) {
	$probe_marker = $include_probe_marker ? "\n#place(bottom + left)[#metadata(\"almaden-template-probe-bottom\") <almaden-template-probe-bottom>]" : '';
	$clip = $include_probe_marker ? '' : ', clip: true';
	return "#page(columns: 1)[\n#box(width: 100%, height: 100%)[\n#grid(columns: (1fr,), rows: (42%, 1fr), gutter: $gap)[\n#block(width: 100%, height: 100%)[\n$placeholder\n]\n#block(width: 100%, height: 100%{$clip})[$probe_marker\n#columns(2, gutter: $gap)[\n#almaden-page-styled(\"content\")[\n$left_body\n]\n]\n]\n]\n]\n]\n";
}

function almaden_bookster_typst_apply_page_template_flow( $source, $context, $flow_map, $template = null, $word_probe = array(), &$assets = array() ) {
	$GLOBALS['almaden_bookster_typst_page_template_debug'] = array();
	$template = is_array( $template ) ? $template : ( $context['templates'][0] ?? null );
	if ( ! is_array( $template ) || ! almaden_bookster_typst_get_page_template_definition( $template['template_id'] ?? '' ) ) {
		$GLOBALS['almaden_bookster_typst_page_template_debug'] = array( 'reason' => 'unsupported_template' );
		return (string) $source;
	}
	$layout_mode = almaden_bookster_typst_page_template_layout_mode( $template );

	$target_page = (int) ( $template['page_number'] ?? 0 );
	$target_rows = almaden_bookster_typst_page_template_target_rows( $flow_map, $template );
	if ( $target_page < 1 || empty( $target_rows ) ) {
		$GLOBALS['almaden_bookster_typst_page_template_debug'] = array(
			'reason' => 'no_rows_for_page',
			'page'   => $target_page,
		);
		return (string) $source;
	}
	$anchor_id = (string) ( $template['anchor']['flow_id'] ?? '' );
	if ( function_exists( 'almaden_bookster_typst_page_template_is_blank_anchor' ) && almaden_bookster_typst_page_template_is_blank_anchor( $anchor_id ) ) {
		$debug = array();
		$updated_source = almaden_bookster_typst_apply_blank_page_template( $source, $context, $template, $assets, $debug );
		$GLOBALS['almaden_bookster_typst_page_template_debug'] = array_merge( array( 'page' => $target_page ), $debug );
		return $updated_source;
	}
	if ( function_exists( 'almaden_bookster_typst_page_template_is_transition_anchor' ) && almaden_bookster_typst_page_template_is_transition_anchor( $anchor_id ) ) {
		foreach ( $target_rows as $target_row ) {
			if ( $anchor_id === (string) ( $target_row['id'] ?? '' ) ) {
				$template['_transition_marker_page'] = (int) ( $target_row['marker_page'] ?? $target_page );
				break;
			}
		}
		$debug = array();
		$updated_source = almaden_bookster_typst_apply_transition_page_template( $source, $context, $template, $assets, $debug );
		$GLOBALS['almaden_bookster_typst_page_template_debug'] = array_merge(
			array( 'page' => $target_page ),
			$debug
		);
		return $updated_source;
	}

	$page_ids = array();
	$left_ids = array();
	foreach ( $target_rows as $row ) {
		$id = (string) ( $row['id'] ?? '' );
		if ( '' === $id ) {
			continue;
		}
		$page_ids[] = $id;
		if ( in_array( $layout_mode, array( 'split', 'image-left-split', 'upper-bottom-split', 'image-top-two-column-bottom' ), true ) ) {
			$left_ids[] = $id;
		}
	}
	$page_ids = array_values( array_unique( array_filter( $page_ids ) ) );
	$left_ids = array_values( array_unique( array_filter( $left_ids ) ) );
	if ( ! empty( $word_probe['extra_page_ids'] ) && in_array( $layout_mode, array( 'split', 'image-left-split', 'upper-bottom-split', 'image-top-two-column-bottom' ), true ) ) {
		$extra_ids = array_values( array_filter( (array) $word_probe['extra_page_ids'] ) );
		$page_ids = array_values( array_unique( array_merge( $extra_ids, $page_ids ) ) );
		$left_ids = array_values( array_unique( array_merge( $extra_ids, $left_ids ) ) );
	}
	if ( empty( $page_ids ) || ( in_array( $layout_mode, array( 'split', 'image-left-split', 'upper-bottom-split', 'image-top-two-column-bottom' ), true ) && empty( $left_ids ) ) ) {
		$GLOBALS['almaden_bookster_typst_page_template_debug'] = array(
			'reason'    => 'no_page_or_template_ids',
			'page'      => $target_page,
			'row_count' => count( $target_rows ),
			'mode'      => $layout_mode,
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
			'mode'         => $layout_mode,
		);
		return (string) $source;
	}
	$layout = array( 'mode' => $layout_mode );
	if ( in_array( $layout_mode, array( 'split', 'image-left-split', 'upper-bottom-split', 'image-top-two-column-bottom' ), true ) ) {
		if ( ! empty( $word_probe['layout'] ) && is_array( $word_probe['layout'] ) ) {
			$layout = $word_probe['layout'];
			$layout['mode'] = $layout_mode;
		} else {
			$left_x = min( array_map( static function ( $row ) { return (float) ( $row['x'] ?? 0 ); }, $target_rows ) );
			$left_ids = array_values( array_filter( $left_ids, static function ( $id ) use ( $target_rows, $left_x ) {
				foreach ( $target_rows as $row ) {
					if ( (string) ( $row['id'] ?? '' ) !== (string) $id ) {
						continue;
					}
					return abs( (float) ( $row['x'] ?? 0 ) - $left_x ) < 1;
				}
				return false;
			} ) );
			if ( ! empty( $word_probe['cut'] ) && function_exists( 'almaden_bookster_typst_page_template_fragment_layout' ) ) {
				$fragment_layout = almaden_bookster_typst_page_template_fragment_layout( $blocks, $page_blocks, $word_probe['cut'] );
				if ( ! empty( $fragment_layout ) ) {
					$fragment_layout['mode'] = $layout_mode;
				}
				$layout = $fragment_layout;
			}
		}
		if ( empty( $layout ) ) {
			/* Keep the safe single-block fallback when Typst cannot provide a word probe. */
			$left_ids = array_slice( $left_ids, 0, 1 );
		} else {
			$left_ids = array_values( (array) ( $layout['left_ids'] ?? $left_ids ) );
		}
	} else {
		$layout['deferred_ids'] = $page_ids;
		$layout['deferred_body'] = implode( "\n", array_map( static function ( $id ) use ( $blocks ) {
			return $blocks[ $id ]['text'];
		}, $page_ids ) );
	}

	$debug = array();
	$updated_source = almaden_bookster_typst_page_template_apply_blocks( $source, $context, $template, $blocks, $ordered_ids, $page_ids, $left_ids, $debug, $layout, $assets );
	if ( $updated_source !== $source ) {
		$selected_end_index = array_search( end( $page_blocks ), $ordered_ids, true );
		$GLOBALS['almaden_bookster_typst_page_template_debug'] = array(
			'page'         => $target_page,
			'reason'       => 'applied',
			'mode'         => $layout_mode,
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
