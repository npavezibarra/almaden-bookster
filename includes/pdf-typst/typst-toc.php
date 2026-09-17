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
			return '#repeat([.], gap: 0.2em)';
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

function almaden_bookster_typst_toc_styled_text( $value, $style, $hyphenate = null ) {
	$hyphen_param = null !== $hyphenate ? ', hyphenate: ' . ( $hyphenate ? 'true' : 'false' ) : '';
	return '#text(font: "' . almaden_bookster_typst_escape_string( $style['family'] )
		. '", size: ' . $style['size'] . 'pt, weight: ' . $style['weight']
		. ', style: "' . almaden_bookster_typst_escape_string( $style['style'] )
		. '", tracking: ' . $style['tracking'] . 'pt' . $hyphen_param . ')['
		. almaden_bookster_typst_escape_markup( $value ) . ']';
}

function almaden_bookster_typst_toc_level_config( $toc_levels, $level ) {
	if ( ! is_array( $toc_levels ) ) {
		return array();
	}
	$level_int = (int) $level;
	$key_map = array(
		0 => 'chapter',
		1 => 'h1',
		2 => 'h2',
		3 => 'h3',
		4 => 'h4',
	);
	$name_key = $key_map[ $level_int ] ?? ( 'h' . $level_int );
	if ( isset( $toc_levels[ $name_key ] ) && is_array( $toc_levels[ $name_key ] ) ) {
		return $toc_levels[ $name_key ];
	}
	if ( isset( $toc_levels[ (string) $level_int ] ) && is_array( $toc_levels[ (string) $level_int ] ) ) {
		return $toc_levels[ (string) $level_int ];
	}
	return array();
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
	$leader_style = strtolower( trim( (string) ( $chapter['toc_leader_style'] ?? 'dotted' ) ) );
	$leader_fill = almaden_bookster_typst_toc_leader_fill( $leader_style, $chapter['toc_leader_thickness'] ?? 0.35 );
	$grid_gutter_pt = round( 0.35 * (float) $item_style['size'], 3 );
	$item_align = in_array( $item_style['align'], array( 'left', 'center', 'right' ), true ) ? $item_style['align'] : 'left';
	$enumerate = strtolower( trim( (string) ( $chapter['toc_enumerate'] ?? 'none' ) ) );
	$visible_chapters = array();
	$running_index = 0;
	foreach ( $chapters as $toc_index => $toc_chapter ) {
		if ( ! is_array( $toc_chapter ) || '1' === (string) ( $toc_chapter['is_toc'] ?? '' ) || '1' === (string) ( $toc_chapter['is_credits'] ?? '' ) ) {
			continue;
		}
		$is_numbered = '1' !== (string) ( $toc_chapter['exclude_from_numbering'] ?? '0' );
		$chapter_id = trim( (string) ( $toc_chapter['id'] ?? (string) ( $toc_index + 1 ) ) );
		$prefix = '';
		if ( $is_numbered ) {
			++$running_index;
			if ( 'decimal' === $enumerate ) {
				$prefix = $running_index . '.';
			} elseif ( 'roman' === $enumerate ) {
				$prefix = almaden_bookster_typst_toc_roman( $running_index ) . '.';
			} elseif ( 'bullet' === $enumerate ) {
				$prefix = '•';
			}
		} elseif ( 'bullet' === $enumerate ) {
			$prefix = '•';
		}
		$chapter_excluded = false;
		$excluded_items = array();
		if ( ! empty( $chapter['toc_excluded_items'] ) ) {
			$decoded = json_decode( $chapter['toc_excluded_items'], true );
			if ( is_array( $decoded ) ) {
				$excluded_items = $decoded;
			}
		}
		
		if ( in_array( 'chapter_' . $chapter_id, $excluded_items, true ) ) {
			$chapter_excluded = true;
		}

		if ( ! $chapter_excluded ) {
			$visible_chapters[] = array(
				'label' => 'almaden-chapter-start-' . preg_replace( '/[^0-9A-Za-z_-]/', '', $chapter_id ),
				'title' => trim( (string) ( $toc_chapter['title'] ?? 'Capítulo' ) ),
				'number' => $prefix,
				'level'  => 0,
			);
		}

		// Parse markdown headings (HTML headings are currently not fully supported by Typst compiler for TOC tags)
		$content = $toc_chapter['content'] ?? '';
		$lines = explode( "\n", $content );
		$h_idx = 0;
		foreach ( $lines as $line ) {
			if ( preg_match( '/^(#{1,4})\s+(.+)$/', trim( $line ), $match ) ) {
				$hKey = 'heading_' . $chapter_id . '_' . $h_idx;
				if ( ! in_array( $hKey, $excluded_items, true ) ) {
					$h_level = strlen( $match[1] );
					$visible_chapters[] = array(
						'label'  => 'almaden-heading-' . preg_replace( '/[^0-9A-Za-z_-]/', '', $chapter_id ) . '-h_' . $h_idx,
						'title'  => trim( strip_tags( $match[2] ) ),
						'number' => '', // Sub-headings generally don't get chapter numbering prefix
						'level'  => $h_level,
					);
				}
				$h_idx++;
			}
		}
	}
	if ( empty( $visible_chapters ) ) {
		return '';
	}

	$toc_levels = array();
	$raw_levels = ! empty( $chapter['toc_levels'] ) ? $chapter['toc_levels'] : ( $settings['toc_levels'] ?? null );
	if ( ! empty( $raw_levels ) ) {
		if ( is_array( $raw_levels ) ) {
			$toc_levels = $raw_levels;
		} elseif ( is_string( $raw_levels ) ) {
			$decoded = json_decode( $raw_levels, true );
			if ( is_array( $decoded ) ) {
				$toc_levels = $decoded;
			}
		}
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
		$entry_level = (int) ( $entry['level'] ?? 0 );
		$lvl_cfg = almaden_bookster_typst_toc_level_config( $toc_levels, $entry_level );

		// 1. Text Transform
		$raw_transform = $lvl_cfg['text_transform'] ?? '';
		$entry_transform = ( '' !== $raw_transform && 'inherit' !== $raw_transform ) ? $raw_transform : ( $chapter['toc_text_transform'] ?? 'none' );
		$transformed_title = almaden_bookster_typst_transform_title( $entry['title'], $entry_transform );

		// 2. Weight, Style, Tracking, Hyphenate, Size
		$entry_weight = ( ! empty( $lvl_cfg['font_weight'] ) && 'inherit' !== $lvl_cfg['font_weight'] ) ? $lvl_cfg['font_weight'] : $item_style['weight'];
		$entry_style_val = ( ! empty( $lvl_cfg['font_style'] ) && 'inherit' !== $lvl_cfg['font_style'] ) ? $lvl_cfg['font_style'] : $item_style['style'];
		$entry_size = ( isset( $lvl_cfg['font_size'] ) && '' !== trim( (string) $lvl_cfg['font_size'] ) && is_numeric( $lvl_cfg['font_size'] ) && (float) $lvl_cfg['font_size'] > 0 )
			? (float) $lvl_cfg['font_size']
			: $item_style['size'];
		$entry_tracking = ( isset( $lvl_cfg['letter_spacing'] ) && '' !== trim( (string) $lvl_cfg['letter_spacing'] ) && is_numeric( $lvl_cfg['letter_spacing'] ) )
			? round( (float) $lvl_cfg['letter_spacing'] * 0.75, 3 )
			: $item_style['tracking'];
		$entry_hyphenate = isset( $lvl_cfg['hyphenate'] ) && '' !== trim( (string) $lvl_cfg['hyphenate'] )
			? ( almaden_bookster_typst_bool( $lvl_cfg['hyphenate'] ) ? true : false )
			: null;

		$entry_text_style = array(
			'family'   => $item_style['family'],
			'size'     => $entry_size,
			'weight'   => $entry_weight,
			'style'    => $entry_style_val,
			'tracking' => $entry_tracking,
		);

		// 3. Indent
		if ( isset( $lvl_cfg['indent'] ) && '' !== trim( (string) $lvl_cfg['indent'] ) && is_numeric( $lvl_cfg['indent'] ) ) {
			$indent_pt = round( (float) $lvl_cfg['indent'] * 2.83465, 3 ) . 'pt';
		} else {
			$indent_pt = $entry_level > 0 ? ( $entry_level * 12 ) . 'pt' : '0pt';
		}

		// 4. Alignment
		$entry_align_raw = strtolower( trim( (string) ( $lvl_cfg['align'] ?? '' ) ) );
		if ( '' === $entry_align_raw || 'inherit' === $entry_align_raw ) {
			$entry_align = $item_align;
		} elseif ( in_array( $entry_align_raw, array( 'left', 'center', 'right', 'justify', 'justify-left', 'justify-right' ), true ) ) {
			$entry_align = $entry_align_raw;
		} else {
			$entry_align = $item_align;
		}

		$align_directive = 'left';
		$justify_directive = '';
		if ( 'center' === $entry_align ) {
			$align_directive = 'center';
		} elseif ( 'right' === $entry_align ) {
			$align_directive = 'right';
		} elseif ( 'justify' === $entry_align || 'justify-left' === $entry_align ) {
			$align_directive = 'left';
			$justify_directive = '#set par(justify: true);';
		} elseif ( 'justify-right' === $entry_align ) {
			$align_directive = 'right';
			$justify_directive = '#set par(justify: true);';
		}

		// 5. Line Height (Leading)
		$leading_override = ( isset( $lvl_cfg['line_height'] ) && is_numeric( $lvl_cfg['line_height'] ) && (float) $lvl_cfg['line_height'] > 0 )
			? '#set par(leading: ' . round( max( 0, (float) $lvl_cfg['line_height'] - 1 ), 4 ) . 'em);'
			: '';

		$number = trim( (string) ( $entry['number'] ?? '' ) );
		$number_content = '' !== $number ? almaden_bookster_typst_toc_styled_text( $number, $number_style ) : '';
		$title_content = almaden_bookster_typst_toc_styled_text( $transformed_title, $entry_text_style, $entry_hyphenate );
		$leader_box_options = 'width: 1fr, inset: 0pt';
		if ( 'solid' === $leader_style ) {
			$leader_box_options .= ', baseline: bottom';
		}
		$leader_content = '' !== $leader_fill ? '#box(' . $leader_box_options . ')[' . $leader_fill . ']' : '';
		$page_expr = '#context { let marks = query(<' . $entry['label'] . '>); if marks.len() > 0 { str(marks.last().location().page()) } else { "" } }';
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
		$pad_open  = '0pt' !== $indent_pt ? '#pad(left: ' . $indent_pt . ')[' : '';
		$pad_close = '0pt' !== $indent_pt ? ']' : '';
		$output .= '' !== $leader_content
			? '  let toc-main = [' . $pad_open . $justify_directive . $leading_override . '#align(' . $align_directive . ')[#toc-title#h(toc-gutter)#toc-leader]' . $pad_close . ']' . "\n"
			: '  let toc-main = [' . $pad_open . $justify_directive . $leading_override . '#align(' . $align_directive . ')[#toc-title]' . $pad_close . ']' . "\n";
		if ( $has_number_column ) {
			$output .= '  grid(columns: (number-width, 1fr, page-width), gutter: toc-gutter, row-gutter: 0pt, align: (left + top, left + top, right + bottom), toc-number, toc-main, toc-page)' . "\n";
		} else {
			$output .= '  grid(columns: (1fr, page-width), gutter: toc-gutter, row-gutter: 0pt, align: (left + top, right + bottom), toc-main, toc-page)' . "\n";
		}
		$output .= '})]' . "\n";
		$output .= $entry_index < count( $visible_chapters ) - 1 ? '#v(' . $item_spacing_pt . 'pt)' . "\n" : '';
	}
	$output .= ']' . "\n";
	return $output;
}
