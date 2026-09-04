<?php
/**
 * Word-level probing for fragmenting text around physical page templates.
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'ALMADEN_TYPST_TESTING' ) ) {
	exit;
}

function almaden_bookster_typst_page_template_block_parts( $block ) {
	$block = (string) $block;
	$start = strpos( $block, '#par[' );
	if ( false === $start ) {
		return null;
	}
	$open = $start + 4;
	$depth = 0;
	$escaped = false;
	for ( $cursor = $open, $length = strlen( $block ); $cursor < $length; ++$cursor ) {
		$character = $block[ $cursor ];
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
			return array(
				'prefix' => substr( $block, 0, $start ),
				'body'   => substr( $block, $open + 1, $cursor - $open - 1 ),
			);
		}
	}
	return null;
}

function almaden_bookster_typst_page_template_transform_words( $body, $callback ) {
	$protected = array();
	$body = preg_replace_callback(
		'/#[A-Za-z][A-Za-z0-9_-]*(?:\((?:[^()"\\\\]|\\\\.|"(?:[^"\\\\]|\\\\.)*")*\))?/u',
		static function ( $match ) use ( &$protected ) {
			$key = "\x1A" . count( $protected ) . "\x1B";
			$protected[ $key ] = $match[0];
			return $key;
		},
		(string) $body
	);
	$word_index = 0;
	$body = preg_replace_callback(
		'/\p{L}[\p{L}\p{M}\p{N}\'’-]*/u',
		static function ( $match ) use ( &$word_index, $callback ) {
			++$word_index;
			return (string) call_user_func( $callback, $match[0], $word_index );
		},
		$body
	);
	return strtr( $body, $protected );
}

function almaden_bookster_typst_page_template_inline_opener( $body, $bracket_offset ) {
	$name_end = $bracket_offset - 1;
	if ( $name_end < 0 ) {
		return '[';
	}
	if ( ')' === $body[ $name_end ] ) {
		$depth = 0;
		$quoted = false;
		$escaped = false;
		$open_parenthesis = -1;
		for ( $cursor = $name_end; $cursor >= 0; --$cursor ) {
			$character = $body[ $cursor ];
			if ( $escaped ) {
				$escaped = false;
				continue;
			}
			if ( '\\' === $character ) {
				$escaped = true;
				continue;
			}
			if ( '"' === $character ) {
				$quoted = ! $quoted;
				continue;
			}
			if ( $quoted ) {
				continue;
			}
			if ( ')' === $character ) {
				++$depth;
			} elseif ( '(' === $character && 0 === --$depth ) {
				$open_parenthesis = $cursor;
				break;
			}
		}
		if ( $open_parenthesis < 1 ) {
			return '[';
		}
		$name_end = $open_parenthesis - 1;
	}
	$name_start = $name_end;
	while ( $name_start >= 0 && preg_match( '/[A-Za-z0-9_.-]/', $body[ $name_start ] ) ) {
		--$name_start;
	}
	if ( $name_start < 0 || '#' !== $body[ $name_start ] || $name_start === $name_end ) {
		return '[';
	}
	return substr( $body, $name_start, $bracket_offset - $name_start + 1 );
}

function almaden_bookster_typst_page_template_balance_split_markup( $left, $right ) {
	if ( '' === trim( (string) $right ) ) {
		return array( (string) $left, (string) $right );
	}
	$stack = array();
	$escaped = false;
	$quoted = false;
	for ( $cursor = 0, $length = strlen( (string) $left ); $cursor < $length; ++$cursor ) {
		$character = $left[ $cursor ];
		if ( $escaped ) {
			$escaped = false;
			continue;
		}
		if ( '\\' === $character ) {
			$escaped = true;
			continue;
		}
		if ( '"' === $character ) {
			$quoted = ! $quoted;
			continue;
		}
		if ( $quoted ) {
			continue;
		}
		if ( '[' === $character ) {
			$stack[] = almaden_bookster_typst_page_template_inline_opener( $left, $cursor );
		} elseif ( ']' === $character && ! empty( $stack ) ) {
			array_pop( $stack );
		}
	}
	if ( empty( $stack ) ) {
		return array( (string) $left, (string) $right );
	}
	return array(
		(string) $left . str_repeat( ']', count( $stack ) ),
		implode( '', $stack ) . (string) $right,
	);
}

function almaden_bookster_typst_page_template_split_body_at_word( $body, $word_count ) {
	$word_count = max( 0, (int) $word_count );
	$marked = almaden_bookster_typst_page_template_transform_words(
		$body,
		static function ( $word, $index ) {
			return "\x1E" . $index . "\x1F" . $word;
		}
	);
	$next_marker = "\x1E" . ( $word_count + 1 ) . "\x1F";
	$offset = strpos( $marked, $next_marker );
	if ( false === $offset ) {
		$left = $marked;
		$right = '';
	} else {
		$left = substr( $marked, 0, $offset );
		$right = substr( $marked, $offset + strlen( $next_marker ) );
	}
	$strip_markers = static function ( $value ) {
		return preg_replace( '/\x1E[0-9]+\x1F/', '', (string) $value );
	};
	return almaden_bookster_typst_page_template_balance_split_markup(
		$strip_markers( $left ),
		$strip_markers( $right )
	);
}

function almaden_bookster_typst_page_template_probe_page( $context, $template, $body ) {
	$gap = round( (float) $context['columns_gap'], 4 ) . $context['unit'];
	$assets = array();
	$placeholder = almaden_bookster_typst_page_template_placeholder( $template, $context, $assets, $context['asset_mode'] ?? 'original' );
	$text = "#block(width: 100%, height: 100%)[\n#place(bottom + left)[#metadata(\"almaden-template-probe-bottom\") <almaden-template-probe-bottom>]\n#almaden-page-styled(\"content\")[\n$body\n]\n]";
	$image = "#block(width: 100%, height: 100%)[\n$placeholder\n]";
	if ( 'upper-bottom-split' === almaden_bookster_typst_page_template_layout_mode( $template ) ) {
		return almaden_bookster_typst_page_template_render_upper_bottom_replacement( $gap, $body, $placeholder, '', true, false );
	}
	if ( 'image-top-two-column-bottom' === almaden_bookster_typst_page_template_layout_mode( $template ) ) {
		return almaden_bookster_typst_page_template_render_image_top_two_column_bottom_replacement( $gap, $body, $placeholder, true );
	}
	$image_first = 'image-left-split' === almaden_bookster_typst_page_template_layout_mode( $template );
	$first = $image_first ? $image : $text;
	$second = $image_first ? $text : $image;
	return "#page(columns: 1)[\n#box(width: 100%, height: 100%)[\n#grid(columns: (1fr, 1fr), rows: (1fr,), gutter: $gap)[\n$first\n][\n$second\n]\n]\n]\n";
}

function almaden_bookster_typst_page_template_prepare_word_probe( $source, $context, $flow_map, $template ) {
	$target_ids = array();
	foreach ( almaden_bookster_typst_page_template_target_rows( $flow_map, $template ) as $row ) {
		if ( ! empty( $row['id'] ) ) {
			$target_ids[] = (string) $row['id'];
		}
	}
	list( $blocks, $ordered_ids ) = almaden_bookster_typst_page_template_source_blocks( $source );
	$target_ids = array_values( array_filter( $ordered_ids, static function ( $id ) use ( $target_ids ) {
		return in_array( $id, $target_ids, true );
	} ) );
	if ( empty( $target_ids ) ) {
		return array();
	}

	$word_map = array();
	$probe_body = array();
	$counter = 0;
	foreach ( $target_ids as $id ) {
		$parts = almaden_bookster_typst_page_template_block_parts( $blocks[ $id ]['text'] ?? '' );
		if ( ! is_array( $parts ) ) {
			continue;
		}
		$local_word = 0;
		$body = almaden_bookster_typst_page_template_transform_words(
			$parts['body'],
			static function ( $word ) use ( &$counter, &$local_word, &$word_map, $id ) {
				++$counter;
				++$local_word;
				$probe_id = 'almaden-template-probe-word-' . $counter;
				$word_map[ $probe_id ] = array( 'block_id' => $id, 'word_count' => $local_word );
				return $word . '#metadata("' . $probe_id . '") <' . $probe_id . '>';
			}
		);
		$probe_body[] = $parts['prefix'] . '#par[' . $body . ']';
	}
	if ( empty( $word_map ) || empty( $probe_body ) ) {
		return array();
	}
	$entries = array();
	foreach ( array_keys( $word_map ) as $probe_id ) {
		$entries[] = '(id: "' . $probe_id . '", page: if query(<' . $probe_id . '>).len() > 0 { query(<' . $probe_id . '>).first().location().page() } else { none }, y: if query(<' . $probe_id . '>).len() > 0 { query(<' . $probe_id . '>).first().location().position().y } else { none })';
	}
	$bottom = '(page: if query(<almaden-template-probe-bottom>).len() > 0 { query(<almaden-template-probe-bottom>).first().location().page() } else { none }, y: if query(<almaden-template-probe-bottom>).len() > 0 { query(<almaden-template-probe-bottom>).first().location().position().y } else { none })';
	$first = $target_ids[0];
	$last = $target_ids[ count( $target_ids ) - 1 ];
	$first_offset = $blocks[ $first ]['offset'];
	$last_end = $blocks[ $last ]['offset'] + strlen( $blocks[ $last ]['text'] );
	$probe_source = substr( $source, 0, $first_offset ) . almaden_bookster_typst_page_template_probe_page( $context, $template, implode( "\n", $probe_body ) ) . substr( $source, $last_end );
	$probe_source .= "\n#context [#metadata((words: (" . implode( ', ', $entries ) . '), bottom: ' . $bottom . ")) <almaden-template-probe-report>]\n";
	return array(
		'source'           => $probe_source,
		'target_ids'       => $target_ids,
		'word_map'         => $word_map,
		'bottom_safety_pt' => function_exists( 'almaden_bookster_typst_page_template_probe_bottom_safety' )
			? almaden_bookster_typst_page_template_probe_bottom_safety( $context )
			: 12.0,
	);
}

function almaden_bookster_typst_page_template_probe_cut( $probe, $report ) {
	$words = is_array( $report['words'] ?? null ) ? $report['words'] : array();
	$bottom = is_array( $report['bottom'] ?? null ) ? $report['bottom'] : array();
	$bottom_page = (int) ( $bottom['page'] ?? 0 );
	$bottom_y = (float) preg_replace( '/[^0-9.-]/', '', (string) ( $bottom['y'] ?? 0 ) );
	if ( $bottom_page < 1 || $bottom_y <= 0 ) {
		return array();
	}
	$bottom_safety = isset( $probe['bottom_safety_pt'] ) && is_numeric( $probe['bottom_safety_pt'] )
		? max( 12.0, (float) $probe['bottom_safety_pt'] )
		: 14.0;
	$cut = array();
	foreach ( $words as $word ) {
		$probe_id = (string) ( $word['id'] ?? '' );
		$position_y = (float) preg_replace( '/[^0-9.-]/', '', (string) ( $word['y'] ?? 0 ) );
		if ( (int) ( $word['page'] ?? 0 ) !== $bottom_page || $position_y >= $bottom_y - $bottom_safety || empty( $probe['word_map'][ $probe_id ] ) ) {
			continue;
		}
		$cut = $probe['word_map'][ $probe_id ];
	}
	return $cut;
}

function almaden_bookster_typst_page_template_prepare_page_start_probe( $source, $flow_map, $template ) {
	$target_page = (int) ( $template['page_number'] ?? 0 );
	$anchor_order = almaden_bookster_typst_page_template_flow_order( $template['anchor']['flow_id'] ?? '' );
	$previous_id = '';
	foreach ( (array) $flow_map as $row ) {
		$id = (string) ( $row['id'] ?? '' );
		$order = almaden_bookster_typst_page_template_flow_order( $id );
		if ( PHP_INT_MAX === $order || $order >= $anchor_order || (int) ( $row['page'] ?? 0 ) >= $target_page ) {
			continue;
		}
		$previous_id = $id;
	}
	if ( '' === $previous_id ) {
		return array();
	}
	list( $blocks ) = almaden_bookster_typst_page_template_source_blocks( $source );
	$parts = almaden_bookster_typst_page_template_block_parts( $blocks[ $previous_id ]['text'] ?? '' );
	if ( ! is_array( $parts ) ) {
		return array();
	}
	$word_map = array();
	$body = almaden_bookster_typst_page_template_transform_words(
		$parts['body'],
		static function ( $word, $index ) use ( &$word_map ) {
			$probe_id = 'almaden-template-page-start-word-' . $index;
			$word_map[ $probe_id ] = $index;
			return $word . '#metadata("' . $probe_id . '") <' . $probe_id . '>';
		}
	);
	if ( empty( $word_map ) ) {
		return array();
	}
	$block = $parts['prefix'] . '#par[' . $body . ']';
	$offset = (int) ( $blocks[ $previous_id ]['offset'] ?? -1 );
	if ( $offset < 0 ) {
		return array();
	}
	$probe_source = substr( (string) $source, 0, $offset ) . $block . substr( (string) $source, $offset + strlen( $blocks[ $previous_id ]['text'] ) );
	$entries = array();
	foreach ( $word_map as $probe_id => $word_count ) {
		$entries[] = '(id: "' . $probe_id . '", word_count: ' . (int) $word_count . ', page: if query(<' . $probe_id . '>).len() > 0 { query(<' . $probe_id . '>).first().location().page() } else { none })';
	}
	$probe_source .= "\n#context [#metadata((block_id: \"" . $previous_id . '", target_page: ' . $target_page . ', words: (' . implode( ', ', $entries ) . "))) <almaden-template-page-start-report>]\n";
	return array( 'source' => $probe_source, 'block_id' => $previous_id );
}

function almaden_bookster_typst_page_template_page_start_layout( $source, $flow_map, $template, $report ) {
	$target_page = (int) ( $template['page_number'] ?? 0 );
	$block_id = (string) ( $report['block_id'] ?? '' );
	$words = is_array( $report['words'] ?? null ) ? $report['words'] : array();
	if ( $target_page < 1 || '' === $block_id || empty( $words ) ) {
		return array();
	}
	$first_word = null;
	foreach ( $words as $word ) {
		if ( (int) ( $word['page'] ?? 0 ) >= $target_page ) {
			$first_word = $word;
			break;
		}
	}
	if ( ! is_array( $first_word ) ) {
		return array();
	}
	list( $blocks ) = almaden_bookster_typst_page_template_source_blocks( $source );
	$parts = almaden_bookster_typst_page_template_block_parts( $blocks[ $block_id ]['text'] ?? '' );
	if ( ! is_array( $parts ) ) {
		return array();
	}
	list( $pre_body, $template_body ) = almaden_bookster_typst_page_template_split_body_at_word(
		$parts['body'],
		max( 0, (int) ( $first_word['word_count'] ?? 1 ) - 1 )
	);
	if ( '' === trim( $pre_body ) || '' === trim( $template_body ) ) {
		return array();
	}
	$target_ids = array( $block_id );
	$segments = array(
		array( 'id' => $block_id, 'prefix' => '', 'body' => $template_body ),
	);
	foreach ( almaden_bookster_typst_page_template_target_rows( $flow_map, $template ) as $row ) {
		$id = (string) ( $row['id'] ?? '' );
		if ( '' !== $id && $id !== $block_id && ! in_array( $id, $target_ids, true ) ) {
			$segment_parts = almaden_bookster_typst_page_template_block_parts( $blocks[ $id ]['text'] ?? '' );
			if ( ! is_array( $segment_parts ) ) {
				continue;
			}
			$target_ids[] = $id;
			$segments[] = array( 'id' => $id, 'prefix' => $segment_parts['prefix'], 'body' => $segment_parts['body'] );
		}
	}
	return array(
		'pre_body'       => '#par[' . $pre_body . ']',
		'left_body'      => almaden_bookster_typst_page_template_segments_body( $segments ),
		'deferred_body'  => '',
		'left_ids'       => $target_ids,
		'deferred_ids'   => array(),
		'extra_page_ids' => array( $block_id ),
		'page_ids'       => $target_ids,
		'segments'       => $segments,
		'measured'       => false,
	);
}

function almaden_bookster_typst_page_template_fragment_layout( $blocks, $target_ids, $cut ) {
	$cut_id = (string) ( $cut['block_id'] ?? '' );
	$word_count = (int) ( $cut['word_count'] ?? 0 );
	if ( '' === $cut_id || $word_count < 1 || ! in_array( $cut_id, $target_ids, true ) ) {
		return array();
	}
	$left_segments = array();
	$deferred_segments = array();
	$deferred_parts = array();
	$left_ids = array();
	$deferred_ids = array();
	$after_cut = false;
	foreach ( $target_ids as $id ) {
		$block = $blocks[ $id ]['text'] ?? '';
		if ( $id !== $cut_id && ! $after_cut ) {
			$left_segments[] = $block;
			$left_ids[] = $id;
			continue;
		}
		if ( $id === $cut_id && ! $after_cut ) {
			$parts = almaden_bookster_typst_page_template_block_parts( $block );
			if ( ! is_array( $parts ) ) {
				return array();
			}
			list( $left, $remainder ) = almaden_bookster_typst_page_template_split_body_at_word( $parts['body'], $word_count );
			if ( '' !== trim( $left ) ) {
				$left_segments[] = '#par[' . $left . ']';
				$left_ids[] = $id;
			}
			if ( '' !== trim( $remainder ) ) {
				$deferred_segments[] = $parts['prefix'] . '#par[' . $remainder . ']';
				$deferred_parts[] = array( 'id' => $id, 'prefix' => $parts['prefix'], 'body' => $remainder );
				$deferred_ids[] = $id;
			}
			$after_cut = true;
			continue;
		}
		$deferred_segments[] = $block;
		$parts = almaden_bookster_typst_page_template_block_parts( $block );
		if ( is_array( $parts ) ) {
			$deferred_parts[] = array( 'id' => $id, 'prefix' => $parts['prefix'], 'body' => $parts['body'] );
		}
		$deferred_ids[] = $id;
	}
	return empty( $left_segments ) ? array() : array(
		'left_body'    => implode( "\n", $left_segments ),
		'deferred_body'=> implode( "\n", $deferred_segments ),
		'left_ids'     => $left_ids,
		'deferred_ids' => $deferred_ids,
		'deferred_segments' => $deferred_parts,
		'measured'     => true,
	);
}
