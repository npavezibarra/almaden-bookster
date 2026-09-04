<?php
/**
 * Capacity probing for page-start layouts assembled from paragraph fragments.
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'ALMADEN_TYPST_TESTING' ) ) {
	exit;
}

function almaden_bookster_typst_page_template_segments_body( $segments ) {
	$body = array();
	foreach ( (array) $segments as $segment ) {
		$content = (string) ( $segment['body'] ?? '' );
		if ( '' === trim( $content ) ) {
			continue;
		}
		$body[] = (string) ( $segment['prefix'] ?? '' ) . '#par[' . $content . ']';
	}
	return implode( "\n", $body );
}

function almaden_bookster_typst_page_template_probe_bottom_safety( $context ) {
	$font_size = isset( $context['font_size'] ) && is_numeric( $context['font_size'] ) ? (float) $context['font_size'] : 10.0;
	$line_height = isset( $context['line_height'] ) && is_numeric( $context['line_height'] ) ? (float) $context['line_height'] : 1.2;
	return max( 4.0, round( $font_size * $line_height, 3 ) );
}

function almaden_bookster_typst_page_template_prepare_layout_probe( $source, $context, $template, $layout ) {
	$segments = array_values( array_filter( (array) ( $layout['segments'] ?? array() ), 'is_array' ) );
	$page_ids = array_values( array_filter( (array) ( $layout['page_ids'] ?? array() ) ) );
	if ( empty( $segments ) || empty( $page_ids ) ) {
		return array();
	}

	list( $blocks, $ordered_ids ) = almaden_bookster_typst_page_template_source_blocks( $source );
	$page_ids = array_values( array_filter( $ordered_ids, static function ( $id ) use ( $page_ids ) {
		return in_array( $id, $page_ids, true );
	} ) );
	if ( empty( $page_ids ) ) {
		return array();
	}

	$word_map = array();
	$probe_segments = array();
	$counter = 0;
	foreach ( $segments as $segment_index => $segment ) {
		$local_word = 0;
		$body = almaden_bookster_typst_page_template_transform_words(
			$segment['body'] ?? '',
			static function ( $word ) use ( &$counter, &$local_word, &$word_map, $segment_index ) {
				++$counter;
				++$local_word;
				$probe_id = 'almaden-template-layout-word-' . $counter;
				$word_map[ $probe_id ] = array(
					'segment_index' => $segment_index,
					'block_id'      => (string) $segment_index,
					'word_count'    => $local_word,
				);
				return $word . '#metadata("' . $probe_id . '") <' . $probe_id . '>';
			}
		);
		$probe_segments[] = array(
			'prefix' => (string) ( $segment['prefix'] ?? '' ),
			'body'   => $body,
		);
	}
	if ( empty( $word_map ) ) {
		return array();
	}

	$entries = array();
	foreach ( array_keys( $word_map ) as $probe_id ) {
		$entries[] = '(id: "' . $probe_id . '", page: if query(<' . $probe_id . '>).len() > 0 { query(<' . $probe_id . '>).first().location().page() } else { none }, y: if query(<' . $probe_id . '>).len() > 0 { query(<' . $probe_id . '>).first().location().position().y } else { none })';
	}
	$bottom = '(page: if query(<almaden-template-probe-bottom>).len() > 0 { query(<almaden-template-probe-bottom>).first().location().page() } else { none }, y: if query(<almaden-template-probe-bottom>).len() > 0 { query(<almaden-template-probe-bottom>).first().location().position().y } else { none })';
	$first = $page_ids[0];
	$last = $page_ids[ count( $page_ids ) - 1 ];
	$first_offset = (int) ( $blocks[ $first ]['offset'] ?? -1 );
	$last_end = (int) ( $blocks[ $last ]['offset'] ?? -1 ) + strlen( (string) ( $blocks[ $last ]['text'] ?? '' ) );
	if ( $first_offset < 0 || $last_end <= $first_offset ) {
		return array();
	}
	$replacement = (string) ( $layout['pre_body'] ?? '' ) . "\n";
	$replacement .= almaden_bookster_typst_page_template_probe_page(
		$context,
		$template,
		almaden_bookster_typst_page_template_segments_body( $probe_segments )
	);
	$probe_source = substr( (string) $source, 0, $first_offset ) . $replacement . substr( (string) $source, $last_end );
	$probe_source .= "\n#context [#metadata((words: (" . implode( ', ', $entries ) . '), bottom: ' . $bottom . ")) <almaden-template-probe-report>]\n";
	return array(
		'source'           => $probe_source,
		'word_map'         => $word_map,
		'layout'           => $layout,
		'bottom_safety_pt' => almaden_bookster_typst_page_template_probe_bottom_safety( $context ),
	);
}

function almaden_bookster_typst_page_template_refine_layout( $layout, $probe, $report ) {
	$cut = almaden_bookster_typst_page_template_probe_cut( $probe, $report );
	$cut_index = isset( $cut['segment_index'] ) ? (int) $cut['segment_index'] : -1;
	$word_count = (int) ( $cut['word_count'] ?? 0 );
	$segments = array_values( (array) ( $layout['segments'] ?? array() ) );
	if ( $cut_index < 0 || $word_count < 1 || ! isset( $segments[ $cut_index ] ) ) {
		$layout['measured'] = false;
		$layout['measurement_failed'] = true;
		return $layout;
	}

	$left = array();
	$deferred = array();
	$deferred_segments = array();
	$left_ids = array();
	$deferred_ids = array();
	foreach ( $segments as $index => $segment ) {
		$id = (string) ( $segment['id'] ?? '' );
		$prefix = (string) ( $segment['prefix'] ?? '' );
		$body = (string) ( $segment['body'] ?? '' );
		if ( $index < $cut_index ) {
			$left[] = $prefix . '#par[' . $body . ']';
			$left_ids[] = $id;
			continue;
		}
		if ( $index > $cut_index ) {
			$deferred[] = $prefix . '#par[' . $body . ']';
			$deferred_segments[] = array( 'id' => $id, 'prefix' => $prefix, 'body' => $body );
			$deferred_ids[] = $id;
			continue;
		}
		list( $visible, $remainder ) = almaden_bookster_typst_page_template_split_body_at_word( $body, $word_count );
		if ( '' !== trim( $visible ) ) {
			$left[] = ( '' === trim( $remainder ) ? $prefix : '' ) . '#par[' . $visible . ']';
			$left_ids[] = $id;
		}
		if ( '' !== trim( $remainder ) ) {
			$deferred[] = $prefix . '#par[' . $remainder . ']';
			$deferred_segments[] = array( 'id' => $id, 'prefix' => $prefix, 'body' => $remainder );
			$deferred_ids[] = $id;
		}
	}
	$layout['left_body'] = implode( "\n", $left );
	$layout['deferred_body'] = implode( "\n", $deferred );
	$layout['left_ids'] = array_values( array_unique( array_filter( $left_ids ) ) );
	$layout['deferred_ids'] = array_values( array_unique( array_filter( $deferred_ids ) ) );
	$layout['deferred_segments'] = $deferred_segments;
	$layout['measured'] = true;
	$layout['measurement_failed'] = false;
	return $layout;
}

function almaden_bookster_typst_page_template_prepare_upper_bottom_right_layout_probe( $source, $context, $template, $layout ) {
	if ( 'upper-bottom-split' !== almaden_bookster_typst_page_template_layout_mode( $template ) ) {
		return array();
	}
	$segments = array_values( array_filter( (array) ( $layout['deferred_segments'] ?? array() ), 'is_array' ) );
	if ( empty( $segments ) ) {
		return array( 'layout' => $layout );
	}
	list( $blocks, $ordered_ids ) = almaden_bookster_typst_page_template_source_blocks( $source );
	$target_ids = array_values( array_filter( (array) ( $layout['page_ids'] ?? array_merge( (array) ( $layout['left_ids'] ?? array() ), (array) ( $layout['deferred_ids'] ?? array() ) ) ) ) );
	$target_ids = array_values( array_filter( $ordered_ids, static function ( $id ) use ( $target_ids ) {
		return in_array( $id, $target_ids, true );
	} ) );
	if ( empty( $target_ids ) ) {
		return array( 'layout' => $layout );
	}
	$word_map = array();
	$probe_body = array();
	$counter = 0;
	foreach ( $segments as $segment_index => $segment ) {
		$id = (string) ( $segment['id'] ?? '' );
		$local_word = 0;
		$body = almaden_bookster_typst_page_template_transform_words(
			(string) ( $segment['body'] ?? '' ),
			static function ( $word ) use ( &$counter, &$local_word, &$word_map, $id, $segment_index ) {
				++$counter;
				++$local_word;
				$probe_id = 'almaden-template-upper-right-word-' . $counter;
				$word_map[ $probe_id ] = array( 'block_id' => $id, 'segment_index' => $segment_index, 'word_count' => $local_word );
				return $word . '#metadata("' . $probe_id . '") <' . $probe_id . '>';
			}
		);
		$probe_body[] = (string) ( $segment['prefix'] ?? '' ) . '#par[' . $body . ']';
	}
	if ( empty( $word_map ) ) {
		return array( 'layout' => $layout );
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
	$gap = round( (float) $context['columns_gap'], 4 ) . $context['unit'];
	$assets = array();
	$placeholder = almaden_bookster_typst_page_template_placeholder( $template, $context, $assets, $context['asset_mode'] ?? 'original' );
	$replacement = (string) ( $layout['pre_body'] ?? '' ) . "\n";
	$replacement .= almaden_bookster_typst_page_template_render_upper_bottom_replacement( $gap, $layout['left_body'], $placeholder, implode( "\n", $probe_body ), false, true );
	$probe_source = substr( (string) $source, 0, $first_offset ) . $replacement . substr( (string) $source, $last_end );
	$probe_source .= "\n#context [#metadata((words: (" . implode( ', ', $entries ) . '), bottom: ' . $bottom . ")) <almaden-template-probe-report>]\n";
	return array(
		'source'           => $probe_source,
		'word_map'         => $word_map,
		'layout'           => $layout,
		'segments'         => $segments,
		'bottom_safety_pt' => almaden_bookster_typst_page_template_probe_bottom_safety( $context ),
	);
}

function almaden_bookster_typst_page_template_prepare_upper_bottom_right_probe( $source, $context, $flow_map, $template, $cut ) {
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
	$layout = almaden_bookster_typst_page_template_fragment_layout( $blocks, $target_ids, $cut );
	$layout['page_ids'] = $target_ids;
	return almaden_bookster_typst_page_template_prepare_upper_bottom_right_layout_probe( $source, $context, $template, $layout );
}

function almaden_bookster_typst_page_template_refine_upper_bottom_right_layout( $probe, $report ) {
	$layout = is_array( $probe['layout'] ?? null ) ? $probe['layout'] : array();
	$segments = array_values( array_filter( (array) ( $probe['segments'] ?? array() ), 'is_array' ) );
	$cut = almaden_bookster_typst_page_template_probe_cut( $probe, $report );
	$cut_index = isset( $cut['segment_index'] ) ? (int) $cut['segment_index'] : -1;
	$word_count = (int) ( $cut['word_count'] ?? 0 );
	if ( empty( $segments ) || $cut_index < 0 || $word_count < 1 || ! isset( $segments[ $cut_index ] ) ) {
		$layout['right_measured'] = false;
		$layout['right_measurement_failed'] = true;
		return $layout;
	}
	$visible = array();
	$overflow = array();
	$visible_ids = array();
	$overflow_ids = array();
	foreach ( $segments as $index => $segment ) {
		$id = (string) ( $segment['id'] ?? '' );
		$prefix = (string) ( $segment['prefix'] ?? '' );
		$body = (string) ( $segment['body'] ?? '' );
		if ( $index < $cut_index ) {
			$visible[] = $prefix . '#par[' . $body . ']';
			$visible_ids[] = $id;
			continue;
		}
		if ( $index > $cut_index ) {
			$overflow[] = $prefix . '#par[' . $body . ']';
			$overflow_ids[] = $id;
			continue;
		}
		list( $inside, $outside ) = almaden_bookster_typst_page_template_split_body_at_word( $body, $word_count );
		if ( '' !== trim( $inside ) ) {
			$visible[] = ( '' === trim( $outside ) ? $prefix : '' ) . '#par[' . $inside . ']';
			$visible_ids[] = $id;
		}
		if ( '' !== trim( $outside ) ) {
			$overflow[] = $prefix . '#par[' . $outside . ']';
			$overflow_ids[] = $id;
		}
	}
	$layout['deferred_body'] = implode( "\n", $visible );
	$layout['overflow_body'] = implode( "\n", $overflow );
	$layout['deferred_ids'] = array_values( array_unique( array_filter( $visible_ids ) ) );
	$layout['overflow_ids'] = array_values( array_unique( array_filter( $overflow_ids ) ) );
	$layout['right_measured'] = true;
	$layout['right_measurement_failed'] = false;
	return $layout;
}
