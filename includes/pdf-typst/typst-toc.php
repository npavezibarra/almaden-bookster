<?php
/**
 * Typst table-of-contents renderer.
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'ALMADEN_TYPST_TESTING' ) ) {
	exit;
}

require_once __DIR__ . '/typst-document-helpers.php';

function almaden_bookster_typst_toc_roman( $num ) {
	$roman = array(
		'M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90,
		'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1,
	);
	$out = '';
	$num = max( 1, (int) $num );
	foreach ( $roman as $symbol => $value ) {
		$q = (int) floor( $num / $value );
		$num -= $q * $value;
		$out .= str_repeat( $symbol, $q );
	}
	return $out;
}

function almaden_bookster_typst_toc_leader_fill( $leader_style, $thickness = 0.35 ) {
	$thickness = is_numeric( $thickness ) ? max( 0.1, min( 3.0, (float) $thickness ) ) : 0.35;
	switch ( strtolower( trim( (string) $leader_style ) ) ) {
		case 'solid':
			return '#line(length: 100%, stroke: ' . round( $thickness, 3 ) . 'pt)';
		case 'dashed':
			return '#repeat([-], gap: 0.3em)';
		case 'none':
			return '';
		case 'dotted':
		default:
			return '#repeat([·], gap: 0.2em)';
	}
}

function almaden_bookster_typst_toc_title_text( $chapter ) {
	$chapter = is_array( $chapter ) ? $chapter : array();
	$title = trim( (string) ( $chapter['toc_title_text'] ?? '' ) );
	if ( '' === $title ) {
		$title = trim( (string) ( $chapter['title'] ?? 'Índice' ) );
	}
	return '' !== $title ? $title : 'Índice';
}

function almaden_bookster_typst_toc_styled_text( $value, $style ) {
	return '#text(font: "' . almaden_bookster_typst_escape_string( $style['family'] )
		. '", size: ' . $style['size'] . 'pt, weight: ' . $style['weight']
		. ', style: "' . almaden_bookster_typst_escape_string( $style['style'] )
		. '", tracking: ' . $style['tracking'] . 'pt)['
		. almaden_bookster_typst_escape_markup( $value ) . ']';
}

function almaden_bookster_typst_toc_number_samples( $entries, $number_style ) {
	$samples = array();
	foreach ( $entries as $entry ) {
		$number = trim( (string) ( $entry['number'] ?? '' ) );
		if ( '' !== $number ) {
			$samples[] = '[' . almaden_bookster_typst_toc_styled_text( $number, $number_style ) . ']';
		}
	}
	return empty( $samples ) ? '()' : '(' . implode( ', ', $samples ) . ',)';
}

function almaden_bookster_typst_render_toc( $chapter, $chapters, $settings, $fallbacks, &$assets, $resolve_font, $show_title = true, $page_number_offset = 0 ) {
	$chapter = is_array( $chapter ) ? $chapter : array();
	$chapters = is_array( $chapters ) ? $chapters : array();
	$show_title = $show_title && ! almaden_bookster_typst_bool( $chapter['toc_hide_title'] ?? false );

	$title_style = almaden_bookster_typst_credits_text_style(
		array(
			'font_family' => $chapter['toc_title_font_family'] ?? '',
			'font_size' => $chapter['toc_title_font_size'] ?? ( $settings['chapter_title_font_size'] ?? 24 ),
			'font_weight' => $chapter['toc_title_font_weight'] ?? ( $settings['chapter_title_font_weight'] ?? 'bold' ),
			'font_style' => $chapter['toc_title_font_style'] ?? ( $settings['chapter_title_font_style'] ?? 'normal' ),
			'line_height' => $chapter['toc_title_line_height'] ?? ( $settings['chapter_title_line_height'] ?? 1.2 ),
			'text_align' => $chapter['toc_title_align'] ?? ( $settings['chapter_title_align'] ?? 'center' ),
			'letter_spacing' => $chapter['toc_title_letter_spacing'] ?? ( $settings['chapter_title_letter_spacing'] ?? 0 ),
		),
		$fallbacks['title_family'],
		$fallbacks['title_size'],
		$fallbacks['title_weight'],
		$fallbacks['title_line_height'],
		$resolve_font
	);
	$title_style['tracking'] = is_numeric( $chapter['toc_title_letter_spacing'] ?? null )
		? round( (float) $chapter['toc_title_letter_spacing'] * 0.75, 3 )
		: ( is_numeric( $settings['chapter_title_letter_spacing'] ?? null ) ? round( (float) $settings['chapter_title_letter_spacing'] * 0.75, 3 ) : $title_style['tracking'] );

	$item_style = almaden_bookster_typst_credits_text_style(
		array(
			'font_family' => $chapter['toc_font_family'] ?? '',
			'font_size' => $chapter['toc_font_size'] ?? ( $settings['font_size_content'] ?? 11.5 ),
			'font_weight' => $chapter['toc_font_weight'] ?? 'normal',
			'font_style' => $chapter['toc_font_style'] ?? 'normal',
			'line_height' => $chapter['toc_line_height'] ?? 1.8,
			'letter_spacing' => $chapter['toc_letter_spacing'] ?? 0,
			'text_align' => $chapter['toc_item_align'] ?? 'left',
		),
		$fallbacks['item_family'],
		$fallbacks['item_size'],
		$fallbacks['item_weight'],
		$fallbacks['item_line_height'],
		$resolve_font
	);
	$number_style = almaden_bookster_typst_credits_text_style(
		array_filter(
			array(
				'font_family' => $chapter['toc_number_font_family'] ?? '',
				'font_size' => $chapter['toc_number_font_size'] ?? '',
				'font_weight' => $chapter['toc_number_font_weight'] ?? '',
				'font_style' => $chapter['toc_number_font_style'] ?? $item_style['style'],
				'letter_spacing' => $chapter['toc_number_letter_spacing'] ?? '',
			),
			static function ( $value ) { return '' !== trim( (string) $value ); }
		),
		$item_style['family'], $item_style['size'], $item_style['weight'], $item_style['leading'] + 1,
		$resolve_font, $item_style['tracking'] / 0.75
	);
	$page_style = almaden_bookster_typst_credits_text_style(
		array_filter(
			array(
				'font_family' => $chapter['toc_page_font_family'] ?? '',
				'font_size' => $chapter['toc_page_font_size'] ?? '',
				'font_weight' => $chapter['toc_page_font_weight'] ?? '',
				'font_style' => $chapter['toc_page_font_style'] ?? $item_style['style'],
				'letter_spacing' => $chapter['toc_page_letter_spacing'] ?? '',
			),
			static function ( $value ) { return '' !== trim( (string) $value ); }
		),
		$item_style['family'], $item_style['size'], $item_style['weight'], $item_style['leading'] + 1,
		$resolve_font, $item_style['tracking'] / 0.75
	);

	$title_text = almaden_bookster_typst_transform_title( almaden_bookster_typst_toc_title_text( $chapter ), $chapter['toc_title_text_transform'] ?? 'none' );
	$title_padding_top = is_numeric( $chapter['toc_title_padding_top'] ?? null ) ? (float) $chapter['toc_title_padding_top'] : 0.0;
	$title_padding_bottom = is_numeric( $chapter['toc_title_padding_bottom'] ?? null ) ? (float) $chapter['toc_title_padding_bottom'] : 1.5;
	$item_spacing_pt = is_numeric( $chapter['toc_item_spacing'] ?? null ) ? round( (float) $chapter['toc_item_spacing'] * 0.75, 3 ) : 0.0;
	$page_number_offset_pt = is_numeric( $page_number_offset ) ? (float) $page_number_offset : 0.0;
	$leader_fill = almaden_bookster_typst_toc_leader_fill( $chapter['toc_leader_style'] ?? 'dotted', $chapter['toc_leader_thickness'] ?? 0.35 );
	$leader_align = 'bottom' === strtolower( trim( (string) ( $chapter['toc_leader_position'] ?? 'bottom' ) ) ) ? 'bottom' : 'horizon';
	$leader_min_width_em = is_numeric( $chapter['toc_leader_min_width'] ?? null ) ? max( 1.0, min( 12.0, (float) $chapter['toc_leader_min_width'] ) ) : 4.0;
	$leader_min_width_pt = round( $leader_min_width_em * (float) $item_style['size'], 3 );
	$grid_gutter_pt = round( 0.35 * (float) $item_style['size'], 3 );
	$item_align = in_array( $item_style['align'], array( 'left', 'center', 'right' ), true ) ? $item_style['align'] : 'left';
	$enumerate = strtolower( trim( (string) ( $chapter['toc_enumerate'] ?? 'none' ) ) );
	$visible_chapters = array();
	$running_index = 0;
	foreach ( $chapters as $toc_index => $toc_chapter ) {
		if ( ! is_array( $toc_chapter ) || '1' === (string) ( $toc_chapter['is_toc'] ?? '' ) || '1' === (string) ( $toc_chapter['is_credits'] ?? '' ) || '1' === (string) ( $toc_chapter['exclude_from_numbering'] ?? '' ) ) {
			continue;
		}
		++$running_index;
		$chapter_id = trim( (string) ( $toc_chapter['id'] ?? (string) ( $toc_index + 1 ) ) );
		$prefix = '';
		if ( 'decimal' === $enumerate ) {
			$prefix = $running_index . '.';
		} elseif ( 'roman' === $enumerate ) {
			$prefix = almaden_bookster_typst_toc_roman( $running_index ) . '.';
		} elseif ( 'bullet' === $enumerate ) {
			$prefix = '•';
		}
		$visible_chapters[] = array(
			'label' => 'almaden-chapter-start-' . preg_replace( '/[^0-9A-Za-z_-]/', '', $chapter_id ),
			'title' => almaden_bookster_typst_transform_title( trim( (string) ( $toc_chapter['title'] ?? 'Capítulo' ) ), $chapter['toc_text_transform'] ?? 'none' ),
			'number' => $prefix,
		);
	}
	if ( empty( $visible_chapters ) ) {
		return '';
	}

	$output = '';
	if ( $show_title ) {
		$output .= $title_padding_top > 0 ? '#v(' . round( $title_padding_top, 4 ) . 'cm)' . "\n" : '';
		$output .= '#block(width: 100%, breakable: false)[' . "\n";
		$output .= '#set text(font: "' . almaden_bookster_typst_escape_string( $title_style['family'] ) . '", size: ' . $title_style['size'] . 'pt, weight: ' . $title_style['weight'] . ', style: "' . almaden_bookster_typst_escape_string( $title_style['style'] ) . '", tracking: ' . $title_style['tracking'] . 'pt)' . "\n";
		$output .= '#set par(leading: ' . $title_style['leading'] . 'em, spacing: 0pt)' . "\n";
		$output .= '#align(' . $title_style['align'] . ')[ ' . almaden_bookster_typst_escape_markup( $title_text ) . ' ]' . "\n]" . "\n";
		$output .= $title_padding_bottom > 0 ? '#v(' . round( $title_padding_bottom, 4 ) . 'cm)' . "\n" : '';
	}
	$number_samples = almaden_bookster_typst_toc_number_samples( $visible_chapters, $number_style );
	$has_number_column = '()' !== $number_samples;
	$output .= '#block(width: 100%)[' . "\n";
	$output .= '#set text(font: "' . almaden_bookster_typst_escape_string( $item_style['family'] ) . '", size: ' . $item_style['size'] . 'pt, weight: ' . $item_style['weight'] . ', style: "' . almaden_bookster_typst_escape_string( $item_style['style'] ) . '", tracking: ' . $item_style['tracking'] . 'pt)' . "\n";
	$output .= '#set par(leading: ' . $item_style['leading'] . 'em, spacing: ' . $item_spacing_pt . 'pt)' . "\n";
	$output .= '#let toc-number-samples = ' . $number_samples . "\n";
	foreach ( $visible_chapters as $entry_index => $entry ) {
		$number = trim( (string) ( $entry['number'] ?? '' ) );
		$number_content = '' !== $number ? almaden_bookster_typst_toc_styled_text( $number, $number_style ) : '';
		$title_content = almaden_bookster_typst_toc_styled_text( $entry['title'], $item_style );
		$leader_content = '' !== $leader_fill ? '#box(width: 100%, baseline: ' . $leader_align . ')[' . $leader_fill . ']' : '';
		$page_expr = '#context { let marks = query(<' . $entry['label'] . '>); if marks.len() > 0 { counter(page).display(at: marks.last().location()) } else { "" } }';
		$page_expr = '#text(font: "' . almaden_bookster_typst_escape_string( $page_style['family'] ) . '", size: ' . $page_style['size'] . 'pt, weight: ' . $page_style['weight'] . ', style: "' . almaden_bookster_typst_escape_string( $page_style['style'] ) . '", tracking: ' . $page_style['tracking'] . 'pt)[' . $page_expr . ']';
		$page_expr = 0.0 !== $page_number_offset_pt ? '#move(dy: ' . round( $page_number_offset_pt, 3 ) . 'pt)[' . $page_expr . ']' : $page_expr;
		$output .= '#block(width: 100%, breakable: false)[#layout(size => context {' . "\n";
		$output .= '  let toc-number = [' . $number_content . ']' . "\n";
		$output .= '  let toc-title = [' . $title_content . ']' . "\n";
		$output .= '  let toc-leader = [' . $leader_content . ']' . "\n";
		$output .= '  let toc-page = [' . $page_expr . ']' . "\n";
		$output .= '  let toc-gutter = ' . $grid_gutter_pt . 'pt' . "\n";
		$output .= '  let number-width = toc-number-samples.fold(0pt, (current, sample) => calc.max(current, measure(sample).width))' . "\n";
		$output .= '  let page-width = measure(toc-page).width' . "\n";
		$output .= '  let natural-title-width = measure(toc-title).width' . "\n";
		$output .= '  let max-title-width = calc.max(' . $item_style['size'] . 'pt, size.width - number-width - page-width - ' . $leader_min_width_pt . 'pt - ' . ( $has_number_column ? '3' : '2' ) . ' * toc-gutter)' . "\n";
		$output .= '  let title-width = calc.min(natural-title-width, max-title-width)' . "\n";
		if ( $has_number_column ) {
			$output .= '  grid(columns: (number-width, title-width, 1fr, page-width), gutter: toc-gutter, row-gutter: 0pt, align: (left + top, ' . $item_align . ' + top, left + bottom, right + bottom), toc-number, toc-title, toc-leader, toc-page)' . "\n";
		} else {
			$output .= '  grid(columns: (title-width, 1fr, page-width), gutter: toc-gutter, row-gutter: 0pt, align: (' . $item_align . ' + top, left + bottom, right + bottom), toc-title, toc-leader, toc-page)' . "\n";
		}
		$output .= '})]' . "\n";
		$output .= $entry_index < count( $visible_chapters ) - 1 ? '#v(' . $item_spacing_pt . 'pt)' . "\n" : '';
	}
	$output .= ']' . "\n";
	return $output;
}
