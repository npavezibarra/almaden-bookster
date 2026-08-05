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
	return array( $strip_markers( $left ), $strip_markers( $right ) );
}

function almaden_bookster_typst_page_template_probe_page( $context, $template, $body ) {
	$gap = round( (float) $context['columns_gap'], 4 ) . $context['unit'];
	$placeholder = almaden_bookster_typst_page_template_placeholder( $template );
	return "#page(columns: 1)[\n#box(width: 100%, height: 100%)[\n#grid(columns: (1fr, 1fr), rows: (1fr,), gutter: $gap, [\n#block(width: 100%, height: 100%)[\n$body\n]\n], [\n#block(width: 100%, height: 100%)[\n#place(bottom + left)[#metadata(\"almaden-template-probe-bottom\") <almaden-template-probe-bottom>]\n$placeholder\n]\n])\n]\n]\n";
}

function almaden_bookster_typst_page_template_prepare_word_probe( $source, $context, $flow_map, $template ) {
	$target_page = (int) ( $template['page_number'] ?? 0 );
	$target_ids = array();
	foreach ( (array) $flow_map as $row ) {
		if ( is_array( $row ) && $target_page === (int) ( $row['page'] ?? 0 ) && ! empty( $row['id'] ) ) {
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
				return '#metadata("' . $probe_id . '") <' . $probe_id . '> ' . $word;
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
		'source'     => $probe_source,
		'target_ids' => $target_ids,
		'word_map'   => $word_map,
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
	$cut = array();
	foreach ( $words as $word ) {
		$probe_id = (string) ( $word['id'] ?? '' );
		$position_y = (float) preg_replace( '/[^0-9.-]/', '', (string) ( $word['y'] ?? 0 ) );
		if ( (int) ( $word['page'] ?? 0 ) !== $bottom_page || $position_y >= $bottom_y - 0.5 || empty( $probe['word_map'][ $probe_id ] ) ) {
			continue;
		}
		$cut = $probe['word_map'][ $probe_id ];
	}
	return $cut;
}

function almaden_bookster_typst_page_template_fragment_layout( $blocks, $target_ids, $cut ) {
	$cut_id = (string) ( $cut['block_id'] ?? '' );
	$word_count = (int) ( $cut['word_count'] ?? 0 );
	if ( '' === $cut_id || $word_count < 1 || ! in_array( $cut_id, $target_ids, true ) ) {
		return array();
	}
	$left_segments = array();
	$deferred_segments = array();
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
				$deferred_ids[] = $id;
			}
			$after_cut = true;
			continue;
		}
		$deferred_segments[] = $block;
		$deferred_ids[] = $id;
	}
	return empty( $left_segments ) ? array() : array(
		'left_body'    => implode( "\n", $left_segments ),
		'deferred_body'=> implode( "\n", $deferred_segments ),
		'left_ids'     => $left_ids,
		'deferred_ids' => $deferred_ids,
	);
}
