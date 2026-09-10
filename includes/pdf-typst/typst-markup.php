<?php
/**
 * Deterministic RAW-to-Typst renderer.
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'ALMADEN_TYPST_TESTING' ) ) {
	exit;
}

require_once __DIR__ . '/typst-image-block.php';

/**
 * Escape plain text for Typst markup mode.
 */
function almaden_bookster_typst_escape_markup( $text ) {
	$text = html_entity_decode( (string) $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	return preg_replace( '/([\\\\#\[\]\$\*_<>@`])/', '\\\\$1', $text );
}

/**
 * Render supported inline RAW syntax without ever discarding its inner text.
 */
function almaden_bookster_typst_exception_key( $word ) {
	$word = str_replace( "\xC2\xAD", '', trim( (string) $word ) );
	return function_exists( 'mb_strtolower' ) ? mb_strtolower( $word, 'UTF-8' ) : strtolower( $word );
}

function almaden_bookster_typst_escape_with_exceptions( $text, $exceptions ) {
	if ( empty( $exceptions ) ) {
		return almaden_bookster_typst_escape_markup( $text );
	}
	$lookup = array();
	foreach ( $exceptions as $exception ) {
		$key = almaden_bookster_typst_exception_key( $exception );
		if ( '' !== $key ) {
			$lookup[ $key ] = true;
		}
	}
	$pattern = '/[\p{L}\p{N}]+(?:[\'’][\p{L}\p{N}]+)*/u';
	preg_match_all( $pattern, (string) $text, $matches, PREG_OFFSET_CAPTURE );
	$output = '';
	$cursor = 0;
	foreach ( $matches[0] as $match ) {
		$word   = $match[0];
		$offset = $match[1];
		$output .= almaden_bookster_typst_escape_markup( substr( $text, $cursor, $offset - $cursor ) );
		$escaped = almaden_bookster_typst_escape_markup( $word );
		$output .= isset( $lookup[ almaden_bookster_typst_exception_key( $word ) ] )
			? '#text(hyphenate: false)[' . $escaped . ']'
			: $escaped;
		$cursor = $offset + strlen( $word );
	}
	return $output . almaden_bookster_typst_escape_markup( substr( $text, $cursor ) );
}

function almaden_bookster_typst_render_inline( $text, $footnotes = array(), $depth = 0, $exceptions = array(), $footnote_mode = 'page', $footnote_numbers = array() ) {
	if ( $depth > 12 || '' === $text ) {
		return almaden_bookster_typst_escape_with_exceptions( $text, $exceptions );
	}
	$text = preg_replace(
		'/\[size=([0-9]+(?:\.[0-9]+)?(?:px|pt|em|rem)?)\]\s*\[size=\1\]([\s\S]*?)\[\/size\]\s*\[\/size\]/i',
		'[size=$1]$2[/size]',
		(string) $text
	);

	$patterns = array(
		'/<foreign\s+lang=(?:"|\')([a-zA-Z-]{2,10})(?:"|\')\s*>([\s\S]*?)<\/foreign>/i',
		'/<u>([\s\S]*?)<\/u>/i',
		'/<strong\b[^>]*>([\s\S]*?)<\/strong>/i',
		'/<b\b[^>]*>([\s\S]*?)<\/b>/i',
		'/<em\b[^>]*>([\s\S]*?)<\/em>/i',
		'/<i\b[^>]*>([\s\S]*?)<\/i>/i',
		'/<br\s*\/?>/i',
		'/\*\*([\s\S]*?)\*\*/',
		'/(?<!\*)\*([^*\n]+)\*(?!\*)/',
		'/\[size=([0-9]+(?:\.[0-9]+)?)(px|pt|em|rem)?\]([\s\S]*?)\[\/size\]/i',
		'/\[font=(?:"|\')([^\]]+?)(?:"|\')\]([\s\S]*?)\[\/font\]/i',
		'/\[\^([^\]]+)\]/',
	);

	$best = null;
	foreach ( $patterns as $index => $pattern ) {
		if ( preg_match( $pattern, $text, $match, PREG_OFFSET_CAPTURE ) ) {
			if ( null === $best || $match[0][1] < $best['offset'] ) {
				$best = array(
					'index'  => $index,
					'match'  => $match,
					'offset' => $match[0][1],
				);
			}
		}
	}

	if ( null === $best ) {
		return almaden_bookster_typst_escape_with_exceptions( $text, $exceptions );
	}

	$match  = $best['match'];
	$offset = $best['offset'];
	$before = substr( $text, 0, $offset );
	$after  = substr( $text, $offset + strlen( $match[0][0] ) );
	$output = almaden_bookster_typst_escape_with_exceptions( $before, $exceptions );

	switch ( $best['index'] ) {
		case 0:
			$lang    = strtolower( $match[1][0] );
			$content = almaden_bookster_typst_render_inline( $match[2][0], $footnotes, $depth + 1, $exceptions, $footnote_mode, $footnote_numbers );
			$output .= '#text(lang: "' . almaden_bookster_typst_escape_string( $lang ) . '")[' . $content . ']';
			break;
		case 1:
			$output .= '#underline[' . almaden_bookster_typst_render_inline( $match[1][0], $footnotes, $depth + 1, $exceptions, $footnote_mode, $footnote_numbers ) . ']';
			break;
		case 2:
		case 3:
			$output .= '#strong[' . almaden_bookster_typst_render_inline( $match[1][0], $footnotes, $depth + 1, $exceptions, $footnote_mode, $footnote_numbers ) . ']';
			break;
		case 4:
		case 5:
			$output .= '#emph[' . almaden_bookster_typst_render_inline( $match[1][0], $footnotes, $depth + 1, $exceptions, $footnote_mode, $footnote_numbers ) . ']';
			break;
		case 6:
			$output .= '#linebreak()';
			break;
		case 7:
			$output .= '#strong[' . almaden_bookster_typst_render_inline( $match[1][0], $footnotes, $depth + 1, $exceptions, $footnote_mode, $footnote_numbers ) . ']';
			break;
		case 8:
			$output .= '#emph[' . almaden_bookster_typst_render_inline( $match[1][0], $footnotes, $depth + 1, $exceptions, $footnote_mode, $footnote_numbers ) . ']';
			break;
		case 9:
			$size_pt = almaden_bookster_typst_size_to_pt( $match[1][0], $match[2][0] );
			$output .= '#text(size: ' . $size_pt . 'pt)[' .
				almaden_bookster_typst_render_inline( $match[3][0], $footnotes, $depth + 1, $exceptions, $footnote_mode, $footnote_numbers ) . ']';
			break;
		case 10:
			$family = function_exists( 'almaden_bookster_typst_font_family' )
				? almaden_bookster_typst_font_family( $match[1][0], '' )
				: trim( (string) $match[1][0] );
			if ( '' === $family ) {
				$output .= almaden_bookster_typst_render_inline( $match[2][0], $footnotes, $depth + 1, $exceptions, $footnote_mode, $footnote_numbers );
				break;
			}
			$output .= '#text(font: "' . almaden_bookster_typst_escape_string( $family ) . '")[' .
				almaden_bookster_typst_render_inline( $match[2][0], $footnotes, $depth + 1, $exceptions, $footnote_mode, $footnote_numbers ) . ']';
			break;
		case 11:
			$id = $match[1][0];
			if ( isset( $footnotes[ $id ] ) ) {
				if ( 'page' === $footnote_mode ) {
					$output .= '#footnote[' .
						almaden_bookster_typst_render_inline( $footnotes[ $id ], $footnotes, $depth + 1, $exceptions, $footnote_mode, $footnote_numbers ) . ']';
				} else {
					$number = isset( $footnote_numbers[ $id ] ) ? (int) $footnote_numbers[ $id ] : 0;
					$output .= $number > 0 ? '#super[' . $number . ']' : almaden_bookster_typst_escape_markup( $match[0][0] );
				}
			} else {
				$output .= almaden_bookster_typst_escape_markup( $match[0][0] );
			}
			break;
	}

	return $output . almaden_bookster_typst_render_inline( $after, $footnotes, $depth + 1, $exceptions, $footnote_mode, $footnote_numbers );
}

function almaden_bookster_typst_parse_style_declarations( $style ) {
	$declarations = array();
	foreach ( explode( ';', (string) $style ) as $declaration ) {
		$parts = explode( ':', $declaration, 2 );
		if ( 2 !== count( $parts ) ) {
			continue;
		}
		$key = strtolower( trim( $parts[0] ) );
		$value = trim( $parts[1] );
		if ( '' !== $key && '' !== $value ) {
			$declarations[ $key ] = $value;
		}
	}

	return $declarations;
}

function almaden_bookster_typst_css_color( $value, $fallback = '' ) {
	$value = strtolower( trim( (string) $value ) );
	$named = array(
		'black' => '000000',
		'white' => 'ffffff',
		'transparent' => '',
	);
	if ( isset( $named[ $value ] ) ) {
		return '' === $named[ $value ] ? $fallback : 'rgb("' . $named[ $value ] . '")';
	}
	if ( preg_match( '/^#?([0-9a-f]{3}|[0-9a-f]{6})$/i', $value, $match ) ) {
		$hex = $match[1];
		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		return 'rgb("' . strtolower( $hex ) . '")';
	}

	return $fallback;
}

function almaden_bookster_typst_css_length_pt( $value, $fallback = 0, $min = 0, $max = 200 ) {
	if ( ! preg_match( '/(-?[0-9]+(?:\.[0-9]+)?)(px|pt|mm|cm|in|em|rem)?/i', (string) $value, $match ) ) {
		return $fallback;
	}
	$pt = almaden_bookster_typst_size_to_pt( $match[1], $match[2] ?? 'pt' );
	return max( $min, min( $max, (float) $pt ) );
}

function almaden_bookster_typst_unescape_html_tag_markers( $html ) {
	return preg_replace( '/\\\\(?=<\/?[a-z][^>]*>)/i', '', (string) $html );
}

function almaden_bookster_typst_table_text_setup( $table_style ) {
	$table_style = is_array( $table_style ) ? $table_style : array();
	$font_family = almaden_bookster_typst_escape_string( $table_style['font_family'] ?? '' );
	$font_size = isset( $table_style['font_size'] ) ? max( 5, min( 100, (float) $table_style['font_size'] ) ) : 10;
	$raw_font_weight = strtolower( trim( (string) ( $table_style['font_weight'] ?? 'normal' ) ) );
	$font_weight = function_exists( 'almaden_bookster_typst_font_weight' )
		? almaden_bookster_typst_font_weight( $raw_font_weight )
		: ( 'bold' === $raw_font_weight ? 700 : ( 'normal' === $raw_font_weight ? 400 : max( 100, min( 900, (int) $raw_font_weight ) ) ) );
	$font_style = isset( $table_style['font_style'] ) && in_array( $table_style['font_style'], array( 'normal', 'italic', 'oblique' ), true ) ? $table_style['font_style'] : 'normal';
	$line_height = isset( $table_style['line_height'] ) ? max( 0.8, min( 4, (float) $table_style['line_height'] ) ) : 1.35;
	$text_align = isset( $table_style['align'] ) && in_array( $table_style['align'], array( 'left', 'center', 'right', 'justify' ), true ) ? $table_style['align'] : 'left';
	$letter_spacing = isset( $table_style['letter_spacing'] ) ? max( -20, min( 20, (float) $table_style['letter_spacing'] ) ) : 0;

	$setup = '#set par(justify: ' . ( 'justify' === $text_align ? 'true' : 'false' ) . ', leading: ' . round( max( 0, $line_height - 1 ), 4 ) . 'em)' . "\n";
	$setup .= '#set align(' . ( 'justify' === $text_align ? 'left' : $text_align ) . ')' . "\n";
	$setup .= '#set text(' . ( '' !== $font_family ? 'font: "' . $font_family . '", ' : '' ) . 'size: ' . round( $font_size, 3 ) . 'pt, weight: ' . $font_weight . ', style: "' . almaden_bookster_typst_escape_string( $font_style ) . '", tracking: ' . round( $letter_spacing, 3 ) . 'pt)' . "\n";

	return $setup;
}

function almaden_bookster_typst_html_cell_text( $html ) {
	$text = preg_replace( '/<br\s*\/?>/i', "\n", (string) $html );
	$text = preg_replace( '/<\/p>\s*<p\b[^>]*>/i', "\n", $text );
	$text = preg_replace( '/<\/?(?:p|div|span)\b[^>]*>/i', '', $text );
	return trim( preg_replace( '/[ \t\r\n]+/', ' ', $text ) );
}

function almaden_bookster_typst_extract_html_table_rows( $html ) {
	$rows = array();
	if ( ! preg_match_all( '/<tr\b[^>]*>([\s\S]*?)<\/tr>/i', (string) $html, $row_matches ) ) {
		return $rows;
	}

	foreach ( $row_matches[1] as $row_html ) {
		if ( ! preg_match_all( '/<(td|th)\b([^>]*)>([\s\S]*?)<\/\1>/i', $row_html, $cell_matches, PREG_SET_ORDER ) ) {
			continue;
		}
		$cells = array();
		foreach ( $cell_matches as $cell ) {
			$cells[] = array(
				'header' => 'th' === strtolower( $cell[1] ),
				'attrs'  => almaden_bookster_typst_parse_html_attributes( $cell[2] ?? '' ),
				'text'   => almaden_bookster_typst_html_cell_text( $cell[3] ?? '' ),
			);
		}
		if ( ! empty( $cells ) ) {
			$rows[] = $cells;
		}
	}

	return $rows;
}

function almaden_bookster_typst_render_html_table( $html, $style, $footnotes, $exceptions, $footnote_mode, $footnote_numbers, $table_style = array() ) {
	$rows = almaden_bookster_typst_extract_html_table_rows( $html );
	if ( empty( $rows ) ) {
		return '';
	}

	$column_count = 0;
	foreach ( $rows as $row ) {
		$column_count = max( $column_count, count( $row ) );
	}
	if ( $column_count < 1 ) {
		return '';
	}

	$table_style = is_array( $table_style ) ? $table_style : array();
	$border_color = almaden_bookster_typst_css_color( $table_style['border_color'] ?? '', 'rgb("d9d9d9")' );
	$border_width = (float) ( $table_style['border_width'] ?? 0.75 );
	$cell_padding = (float) ( $table_style['cell_padding'] ?? 6 );
	$header_fill_color = almaden_bookster_typst_css_color( $table_style['header_bg_color'] ?? '', 'rgb("eeeeee")' );
	$cell_fill_color = almaden_bookster_typst_css_color( $table_style['cell_bg_color'] ?? '', 'none' );
	$table_text_prefix = almaden_bookster_typst_table_text_setup( $table_style );
	$columns = implode( ', ', array_fill( 0, $column_count, '1fr' ) );
	$parts = array();

	foreach ( $rows as $row_index => $row ) {
		$is_header_row = 0 === $row_index;
		foreach ( $row as $cell ) {
			$is_header_row = $is_header_row || ! empty( $cell['header'] );
		}

		foreach ( $row as $cell ) {
			$text = almaden_bookster_typst_render_inline( $cell['text'], $footnotes, 0, $exceptions, $footnote_mode, $footnote_numbers );
			if ( $is_header_row || ! empty( $cell['header'] ) ) {
				$text = '#strong[' . $text . ']';
			}
			$fill_color = $is_header_row || ! empty( $cell['header'] ) ? $header_fill_color : $cell_fill_color;
			$fill = 'none' !== $fill_color ? ', fill: ' . $fill_color : '';
			$parts[] = 'table.cell(inset: ' . round( $cell_padding, 3 ) . 'pt' . $fill . ')[' . $text . ']';
		}

		for ( $missing = count( $row ); $missing < $column_count; ++$missing ) {
			$parts[] = '[]';
		}
	}

	return '#block(width: 100%)[' . "\n" .
		$table_text_prefix .
		'#table(columns: (' . $columns . '), stroke: ' . round( $border_width, 3 ) . 'pt + ' . $border_color . ', ' . "\n" .
		implode( ",\n", $parts ) . "\n" .
		')' . "\n" .
		']';
}

function almaden_bookster_typst_render_box_block( $raw, $attrs, $footnotes, $exceptions, $footnote_mode, $footnote_numbers, $table_style = array() ) {
	$style = almaden_bookster_typst_parse_style_declarations( $attrs['style'] ?? '' );
	$table_style = is_array( $table_style ) ? $table_style : array();
	$body = preg_replace( '/^\s*\[html\]\s*/i', '', (string) $raw );
	$body = preg_replace( '/\s*\[\/html\]\s*$/i', '', $body );
	$body = almaden_bookster_typst_unescape_html_tag_markers( $body );
	if ( preg_match( '/<div\b([^>]*)>([\s\S]*?)<\/div>/i', $body, $div ) ) {
		$attrs = array_merge( $attrs, almaden_bookster_typst_parse_html_attributes( $div[1] ) );
		$style = array_merge( $style, almaden_bookster_typst_parse_style_declarations( $attrs['style'] ?? '' ) );
		$body = $div[2];
	}

	$fill = almaden_bookster_typst_css_color( $style['background'] ?? ( $style['background-color'] ?? '' ), 'rgb("f3f3f3")' );
	$border_color = almaden_bookster_typst_css_color( preg_replace( '/\b(?:solid|dashed|dotted|double|none)\b/i', '', (string) ( $style['border'] ?? '' ) ), 'rgb("d9d9d9")' );
	$border_width = isset( $style['border'] ) ? almaden_bookster_typst_css_length_pt( $style['border'], 1, 0, 20 ) : 1;
	$padding = isset( $style['padding'] ) ? almaden_bookster_typst_css_length_pt( $style['padding'], 12, 0, 80 ) : 12;
	$radius = isset( $style['border-radius'] ) ? almaden_bookster_typst_css_length_pt( $style['border-radius'], 0, 0, 80 ) : 0;
	$leading = isset( $style['line-height'] ) && is_numeric( $style['line-height'] ) ? max( 0, min( 4, (float) $style['line-height'] - 1 ) ) : null;

	if ( false !== stripos( $body, '<table' ) ) {
		$table = almaden_bookster_typst_render_html_table( $body, $style, $footnotes, $exceptions, $footnote_mode, $footnote_numbers, $table_style );
		if ( '' !== $table ) {
			$par_setup = null === $leading ? '' : '#set par(leading: ' . round( $leading, 4 ) . "em)\n";
			return '#block(width: 100%, breakable: true, fill: ' . $fill . ', stroke: ' . round( $border_width, 3 ) . 'pt + ' . $border_color . ', radius: ' . round( $radius, 3 ) . 'pt, inset: 0pt)[' . "\n" .
				$par_setup .
				$table . "\n" .
				']';
		}
	}

	$body = preg_replace( '/<\/p>\s*<p\b[^>]*>/i', "\n\n", $body );
	$body = preg_replace( '/^\s*<p\b[^>]*>/i', '', $body );
	$body = preg_replace( '/<\/p>\s*$/i', '', $body );
	$paragraphs = preg_split( "/\n{2,}/", trim( $body ) );
	$rendered = array();
	foreach ( $paragraphs as $paragraph ) {
		$paragraph = trim( preg_replace( '/\s+/', ' ', $paragraph ) );
		if ( '' === $paragraph ) {
			continue;
		}
		$rendered[] = '#par[' . almaden_bookster_typst_render_inline( $paragraph, $footnotes, 0, $exceptions, $footnote_mode, $footnote_numbers ) . ']';
	}

	$par_setup = null === $leading ? '' : '#set par(leading: ' . round( $leading, 4 ) . "em)\n";
	return '#block(width: 100%, breakable: true, fill: ' . $fill . ', stroke: ' . round( $border_width, 3 ) . 'pt + ' . $border_color . ', radius: ' . round( $radius, 3 ) . 'pt, inset: ' . round( $padding, 3 ) . 'pt)[' . "\n" .
		$par_setup .
		almaden_bookster_typst_table_text_setup( $table_style ) .
		implode( "\n\n", $rendered ) . "\n" .
		']';
}

function almaden_bookster_typst_render_quote_block( $text, $style, $footnotes, $exceptions, $footnote_mode, $footnote_numbers ) {
	$style = is_array( $style ) ? $style : array();
	$font_family = trim( (string) ( $style['font_family'] ?? '' ) );
	$font_size = isset( $style['font_size'] ) && is_numeric( $style['font_size'] ) ? max( 5, min( 100, (float) $style['font_size'] ) ) : 11.5;
	$font_weight = function_exists( 'almaden_bookster_typst_font_weight' )
		? almaden_bookster_typst_font_weight( $style['font_weight'] ?? 'bold' )
		: ( 'bold' === (string) ( $style['font_weight'] ?? 'bold' ) ? 700 : 400 );
	$font_style = isset( $style['font_style'] ) ? strtolower( trim( (string) $style['font_style'] ) ) : 'normal';
	if ( ! in_array( $font_style, array( 'normal', 'italic', 'oblique' ), true ) ) {
		$font_style = 'normal';
	}
	$align = isset( $style['align'] ) && in_array( $style['align'], array( 'left', 'center', 'right' ), true ) ? $style['align'] : 'left';
	$leading = isset( $style['line_height'] ) && is_numeric( $style['line_height'] ) ? max( 0, min( 4, (float) $style['line_height'] - 1 ) ) : 0.4;
	$margin_top = isset( $style['margin_top'] ) && is_numeric( $style['margin_top'] ) ? max( 0, min( 200, (float) $style['margin_top'] ) ) : 10;
	$margin_bottom = isset( $style['margin_bottom'] ) && is_numeric( $style['margin_bottom'] ) ? max( 0, min( 200, (float) $style['margin_bottom'] ) ) : 10;
	$indent = isset( $style['indent'] ) && is_numeric( $style['indent'] ) ? max( 0, min( 200, (float) $style['indent'] ) ) : 14;
	$text_options = 'size: ' . round( $font_size, 3 ) . 'pt, weight: ' . $font_weight . ', style: "' . almaden_bookster_typst_escape_string( $font_style ) . '"';
	if ( '' !== $font_family ) {
		$text_options = 'font: "' . almaden_bookster_typst_escape_string( $font_family ) . '", ' . $text_options;
	}

	return '#block(width: 100%, breakable: true, above: ' . round( $margin_top, 3 ) . 'pt, below: ' . round( $margin_bottom, 3 ) . 'pt, inset: (left: ' . round( $indent, 3 ) . 'pt, right: 0pt))[' . "\n" .
		'#set par(justify: false, first-line-indent: 0pt, leading: ' . round( $leading, 4 ) . 'em)' . "\n" .
		'#set text(' . $text_options . ')' . "\n" .
		'#align(' . $align . ')[#quote(block: true)[' . almaden_bookster_typst_render_inline( $text, $footnotes, 0, $exceptions, $footnote_mode, $footnote_numbers ) . ']]' . "\n" .
		']';
}

/**
 * Return the font families requested by inline [font="..."] shortcodes.
 */
function almaden_bookster_typst_inline_font_families( $text ) {
	$matches = array();
	preg_match_all( '/\[font=(?:"|\')([^\]]+?)(?:"|\')\]/i', (string) $text, $matches );
	$families = array();
	foreach ( $matches[1] ?? array() as $family ) {
		$family = function_exists( 'almaden_bookster_typst_font_family' )
			? almaden_bookster_typst_font_family( $family, '' )
			: trim( (string) $family );
		if ( '' !== $family ) {
			$families[ strtolower( $family ) ] = $family;
		}
	}

	return array_values( $families );
}

function almaden_bookster_typst_parse_html_attributes( $tag ) {
	$attributes = array();
	if ( ! preg_match_all( '/([a-zA-Z_:][-a-zA-Z0-9_:.]*)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\'=<>`]+))/i', (string) $tag, $matches, PREG_SET_ORDER ) ) {
		return $attributes;
	}

	foreach ( $matches as $match ) {
		$key = strtolower( trim( (string) $match[1] ) );
		if ( '' === $key ) {
			continue;
		}

		$value = '';
		for ( $index = 2; $index <= 4; ++$index ) {
			if ( isset( $match[ $index ] ) && '' !== $match[ $index ] ) {
				$value = $match[ $index ];
				break;
			}
		}
		$attributes[ $key ] = html_entity_decode( $value, ENT_QUOTES, 'UTF-8' );
	}

	return $attributes;
}

/**
 * Render RAW block syntax.
 */
function almaden_bookster_typst_heading_keep_options( $options ) {
	$config = isset( $options['heading_keep_with_next'] ) && is_array( $options['heading_keep_with_next'] )
		? $options['heading_keep_with_next']
		: array();
	$enabled = ! empty( $config['enabled'] );
	$levels  = isset( $config['levels'] ) && is_array( $config['levels'] ) ? array_map( 'intval', $config['levels'] ) : array();
	$levels  = array_values( array_intersect( array( 1, 2, 3, 4, 5, 6 ), $levels ) );
	$min_lines = isset( $config['min_lines'] ) ? max( 1, min( 8, (int) $config['min_lines'] ) ) : 3;
	$reserve_pt = isset( $config['reserve_pt'] ) && is_numeric( $config['reserve_pt'] )
		? max( 0, min( 400, (float) $config['reserve_pt'] ) )
		: 0;

	return array(
		'enabled'   => $enabled,
		'levels'    => $levels,
		'min_lines' => $min_lines,
		'reserve_pt' => $reserve_pt,
	);
}

function almaden_bookster_typst_output_heading_level( $block ) {
	if ( preg_match( '/#heading\(level:\s*([1-6])/', (string) $block, $match ) ) {
		return (int) $match[1];
	}

	return 0;
}

function almaden_bookster_typst_output_is_heading( $block ) {
	return almaden_bookster_typst_output_heading_level( $block ) > 0;
}

function almaden_bookster_typst_output_is_keepable_after_heading( $block ) {
	$trimmed = trim( (string) $block );
	if ( '' === $trimmed ) {
		return false;
	}
	if ( almaden_bookster_typst_output_is_heading( $trimmed ) ) {
		return false;
	}
	if ( '#pagebreak' === substr( $trimmed, 0, 10 ) ) {
		return false;
	}
	if ( '#set ' === substr( $trimmed, 0, 5 ) ) {
		return false;
	}
	if ( ']' === $trimmed || ')' === $trimmed ) {
		return false;
	}

	return true;
}

function almaden_bookster_typst_apply_heading_keep_with_next( $output, $options ) {
	$config = almaden_bookster_typst_heading_keep_options( $options );
	if ( empty( $config['enabled'] ) || empty( $config['levels'] ) || count( $output ) < 2 ) {
		return $output;
	}

	$protected = array();
	$count     = count( $output );
	for ( $index = 0; $index < $count; ++$index ) {
		$current = $output[ $index ];
		$level   = almaden_bookster_typst_output_heading_level( $current );
		if ( ! $level || ! in_array( $level, $config['levels'], true ) || $index + 1 >= $count ) {
			$protected[] = $current;
			continue;
		}

		if ( ! almaden_bookster_typst_output_is_keepable_after_heading( $output[ $index + 1 ] ) ) {
			$protected[] = $current;
			continue;
		}

		$reserve = $config['reserve_pt'];
		if ( $reserve <= 0 ) {
			$reserve = $config['min_lines'] * 18;
		}
		$reserve = round( $reserve, 3 );
		$protected[] = '#block(width: 100%, breakable: false, sticky: true)[' . "\n" . $current . "\n#v(" . $reserve . "pt)\n]\n#v(-" . $reserve . 'pt)';
	}

	return $protected;
}

function almaden_bookster_typst_render_blocks( $raw, $options = array(), &$assets = null ) {
	$raw = str_replace( array( "\r\n", "\r" ), "\n", (string) $raw );
	$footnote_data = isset( $options['footnotes'] ) && is_array( $options['footnotes'] )
		? $options['footnotes']
		: ( function_exists( 'almaden_bookster_typst_collect_footnote_data' ) ? almaden_bookster_typst_collect_footnote_data( $raw ) : array( 'raw' => $raw, 'definitions' => array(), 'numbers' => array() ) );
	$footnote_mode = 'page';
	if ( isset( $options['footnote_mode'] ) && function_exists( 'almaden_bookster_typst_footnote_mode' ) ) {
		$footnote_mode = almaden_bookster_typst_footnote_mode( array( 'footnote_mode' => $options['footnote_mode'] ) );
	}

	return almaden_bookster_typst_render_blocks_with_footnotes(
		$footnote_data['raw'] ?? $raw,
		$footnote_data['definitions'] ?? array(),
		false,
		array_merge(
			$options,
			array(
				'footnote_mode'    => $footnote_mode,
				'footnote_numbers' => $footnote_data['numbers'] ?? array(),
			)
		),
		$assets
	);
}

/**
 * Internal block renderer with an already collected footnote map.
 */
function almaden_bookster_typst_render_blocks_with_footnotes( $raw, $footnotes, $allow_embedded_typst = false, $options = array(), &$assets = null ) {
	$asset_mode = function_exists( 'almaden_bookster_typst_normalize_asset_mode' )
		? almaden_bookster_typst_normalize_asset_mode( $options['asset_mode'] ?? 'original' )
		: ( 'original' === (string) ( $options['asset_mode'] ?? '' ) ? 'original' : 'optimized' );
	$image_placeholders = array();
	$image_counter = 0;
	$size_placeholders = array();
	$size_counter = 0;
	// Normalize Markdown bullets that start with `*` into the list syntax this
	// renderer already understands, before inline emphasis can consume them.
	$raw = preg_replace( '/(^|\n)([ \t]*)\*\s+/m', '$1$2- ', (string) $raw );
	$raw = preg_replace_callback( '/<figure\b[\s\S]*?<\/figure>/i', function ( $match ) use ( &$image_placeholders, &$image_counter, &$assets, $asset_mode, $options ) {
		$figure = (string) ( $match[0] ?? '' );
		if (
			false === stripos( $figure, 'pdf-book-image-block' )
			&& false === stripos( $figure, 'data-image-block="1"' )
			&& false === stripos( $figure, "data-image-block='1'" )
		) {
			return $figure;
		}

		$placeholder = '%%ALMADEN_IMAGE_BLOCK_' . $image_counter++ . '%%';
		$rendered = almaden_bookster_typst_render_content_image_block( $figure, $assets, array_merge( $options, array( 'asset_mode' => $asset_mode ) ) );
		if ( '' === $rendered ) {
			return "\n";
		}
		$image_placeholders[ $placeholder ] = $rendered;
		return "\n" . $placeholder . "\n";
	}, (string) $raw );
	$raw = preg_replace_callback( '/\[size=([0-9]+(?:\.[0-9]+)?)(px|pt|em|rem)?\]([\s\S]*?)\[\/size\]/i', function ( $match ) use ( &$size_placeholders, &$size_counter, &$assets, $footnotes, $allow_embedded_typst, $options ) {
		$body = (string) ( $match[3] ?? '' );
		if ( ! preg_match( "/\n\s*\n/", $body ) ) {
			return $match[0];
		}

		$placeholder = '%%ALMADEN_SIZE_BLOCK_' . $size_counter++ . '%%';
		$size_pt = almaden_bookster_typst_size_to_pt( $match[1], $match[2] ?: 'pt' );
		$rendered_body = almaden_bookster_typst_render_blocks_with_footnotes(
			trim( $body ),
			$footnotes,
			$allow_embedded_typst,
			$options,
			$assets
		);
		$size_placeholders[ $placeholder ] = '#text(size: ' . $size_pt . 'pt)[' . "\n" . $rendered_body . "\n" . ']';

		return "\n" . $placeholder . "\n";
	}, (string) $raw );
	$lines     = explode( "\n", (string) $raw );
	$output    = array();
	$paragraph = array();
	$list_type = '';
	$align_type = '';

	$exceptions = (array) ( $options['hyphenation_exceptions'] ?? array() );
	$heading_styles = (array) ( $options['heading_styles'] ?? array() );
	$quote_style = (array) ( $options['quote_style'] ?? array() );
	$table_style = (array) ( $options['table_style'] ?? array() );
	$footnote_mode = 'page';
	if ( isset( $options['footnote_mode'] ) && function_exists( 'almaden_bookster_typst_footnote_mode' ) ) {
		$footnote_mode = almaden_bookster_typst_footnote_mode( array( 'footnote_mode' => $options['footnote_mode'] ) );
	}
	$footnote_numbers = (array) ( $options['footnote_numbers'] ?? array() );
	$flush_paragraph = function () use ( &$paragraph, &$output, $footnotes, $exceptions, $footnote_mode, $footnote_numbers ) {
		if ( empty( $paragraph ) ) {
			return;
		}
		$text      = trim( implode( ' ', $paragraph ) );
		$output[]  = '#par[' . almaden_bookster_typst_render_inline( $text, $footnotes, 0, $exceptions, $footnote_mode, $footnote_numbers ) . ']';
		$paragraph = array();
	};
	$close_list = function () use ( &$list_type, &$output ) {
		if ( '' !== $list_type ) {
			$output[] = ')';
			$list_type = '';
		}
	};

	$line_count = count( $lines );
	for ( $line_index = 0; $line_index < $line_count; ++$line_index ) {
		$line = $lines[ $line_index ];
		$trimmed = trim( $line );
		if ( '' === $trimmed ) {
			$flush_paragraph();
			$close_list();
			continue;
		}

		if ( preg_match( '/^\[box(?:\s+([^\]]+))?\]$/i', $trimmed, $box ) ) {
			$flush_paragraph();
			$close_list();
			$box_lines = array();
			while ( ++$line_index < $line_count ) {
				$box_line = $lines[ $line_index ];
				if ( preg_match( '/^\s*\[\/box\]\s*$/i', $box_line ) ) {
					break;
				}
				$box_lines[] = $box_line;
			}
			$output[] = almaden_bookster_typst_render_box_block(
				implode( "\n", $box_lines ),
				almaden_bookster_typst_parse_html_attributes( $box[1] ?? '' ),
				$footnotes,
				$exceptions,
				$footnote_mode,
				$footnote_numbers,
				$table_style
			);
			continue;
		}

		if ( preg_match( '/^\[align=(left|center|right|justify)\]$/i', $trimmed, $align ) ) {
			$flush_paragraph();
			$close_list();
			$value    = strtolower( $align[1] );
			if ( 'justify' === $value ) {
				$align_type = 'justify';
				$output[] = '#set par(justify: true)';
			} else {
				$align_type = $value;
				$output[] = '#align(' . $value . ')[';
			}
			continue;
		}
		if ( preg_match( '/^\[\/align\]$/i', $trimmed ) ) {
			$flush_paragraph();
			$close_list();
			if ( 'justify' === $align_type ) {
				$output[] = '#set par(justify: false)';
			} elseif ( '' !== $align_type ) {
				$output[] = ']';
			}
			$align_type = '';
			continue;
		}

		if ( preg_match( '/^\[gap:\s*([0-9]+(?:\.[0-9]+)?)(px|mm|cm|pt|em|rem|in)?\s*\]$/i', $trimmed, $gap ) ) {
			$flush_paragraph();
			$close_list();
			$output[] = '#v(' . almaden_bookster_typst_length( $gap[1], $gap[2] ?: 'mm' ) . ')';
			continue;
		}
		if ( preg_match( '/^\[page[-_]?break\]$/i', $trimmed ) ) {
			$flush_paragraph();
			$close_list();
			$output[] = '#pagebreak()';
			continue;
		}
		if ( preg_match( '/^%%ALMADEN_IMAGE_BLOCK_\d+%%$/', $trimmed ) ) {
			$flush_paragraph();
			$close_list();
			$output[] = $trimmed;
			continue;
		}
		if ( preg_match( '/^%%ALMADEN_SIZE_BLOCK_\d+%%$/', $trimmed ) ) {
			$flush_paragraph();
			$close_list();
			$output[] = $trimmed;
			continue;
		}
		if ( preg_match( '/^(#{1,6})\s+(.+)$/', $trimmed, $heading ) ) {
			$flush_paragraph();
			$close_list();
			$level    = strlen( $heading[1] );
			$rendered_heading = almaden_bookster_typst_render_inline( $heading[2], $footnotes, 0, $exceptions, $footnote_mode, $footnote_numbers );
			if ( isset( $heading_styles[ $level ] ) && is_array( $heading_styles[ $level ] ) && ! empty( $heading_styles[ $level ]['font_family'] ) ) {
				$style = $heading_styles[ $level ];
				$heading_align = isset( $style['align'] ) && in_array( $style['align'], array( 'left', 'center', 'right' ), true ) ? $style['align'] : 'left';
				$heading_leading = round( max( 0, (float) ( $style['line_height'] ?? 1.3 ) - 1 ), 4 );
				$heading_margin_top = isset( $style['margin_top'] ) && is_numeric( $style['margin_top'] ) ? max( 0, min( 200, (float) $style['margin_top'] ) ) : 0;
				$heading_margin_bottom = isset( $style['margin_bottom'] ) && is_numeric( $style['margin_bottom'] ) ? max( 0, min( 200, (float) $style['margin_bottom'] ) ) : 0;
				$output[] = '#block(width: 100%, breakable: false)[' .
					"\n" .
					( $heading_margin_top > 0 ? '#v(' . round( $heading_margin_top, 3 ) . 'pt)' . "\n" : '' ) .
					'#set par(justify: false, first-line-indent: 0pt, leading: ' . $heading_leading . 'em)' . "\n" .
					'#align(' . $heading_align . ')[#heading(level: ' . $level . ')[#almaden-page-colored("content", fill => text(fill: fill, font: "' .
					almaden_bookster_typst_escape_string( $style['font_family'] ) . '", size: ' .
					almaden_bookster_typst_size_to_pt( $style['font_size'] ?? 0, 'pt' ) . 'pt, weight: ' .
					almaden_bookster_typst_font_weight( $style['font_weight'] ?? 'normal' ) . ', style: "' .
					almaden_bookster_typst_escape_string( $style['font_style'] ?? 'normal' ) . '", tracking: ' .
					almaden_bookster_typst_length( $style['letter_spacing'] ?? 0, 'pt' ) . ', hyphenate: ' .
					( ! empty( $style['hyphenate'] ) ? 'true' : 'false' ) . ')[' .
					$rendered_heading . '])]]' . "\n" .
					( $heading_margin_bottom > 0 ? '#v(' . round( $heading_margin_bottom, 3 ) . 'pt)' . "\n" : '' ) .
					']';
			} else {
				$output[] = '#heading(level: ' . $level . ')[#almaden-page-colored("content", fill => text(fill: fill)[' . $rendered_heading . '])]';
			}
			continue;
		}
		if ( preg_match( '/^>\s*(.*)$/', $trimmed, $quote ) ) {
			$flush_paragraph();
			$close_list();
			$output[] = almaden_bookster_typst_render_quote_block( $quote[1], $quote_style, $footnotes, $exceptions, $footnote_mode, $footnote_numbers );
			continue;
		}
		if ( preg_match( '/^-\s+(.+)$/', $trimmed, $item ) ) {
			$flush_paragraph();
			if ( 'bullet' !== $list_type ) {
				$close_list();
				$list_type = 'bullet';
				$output[]  = '#list(';
			}
			$output[] = '[' . almaden_bookster_typst_render_inline( $item[1], $footnotes, 0, $exceptions, $footnote_mode, $footnote_numbers ) . '],';
			continue;
		}
		if ( preg_match( '/^\d+\.\s+(.+)$/', $trimmed, $item ) ) {
			$flush_paragraph();
			if ( 'enum' !== $list_type ) {
				$close_list();
				$list_type = 'enum';
				$output[]  = '#enum(';
			}
			$output[] = '[' . almaden_bookster_typst_render_inline( $item[1], $footnotes, 0, $exceptions, $footnote_mode, $footnote_numbers ) . '],';
			continue;
		}

		// A following paragraph or unknown wrapper may begin immediately after the
		// final item. Close the function call before emitting more Typst markup.
		$close_list();
		// Unknown wrappers are retained as literal text instead of silently losing data.
		$paragraph[] = $trimmed;
	}

	$flush_paragraph();
	$close_list();
	$output = almaden_bookster_typst_apply_heading_keep_with_next( $output, $options );
	$result = implode( "\n\n", $output );
	if ( ! empty( $image_placeholders ) ) {
		$result = str_replace( array_keys( $image_placeholders ), array_values( $image_placeholders ), $result );
	}
	if ( ! empty( $size_placeholders ) ) {
		$result = str_replace( array_keys( $size_placeholders ), array_values( $size_placeholders ), $result );
	}

	return $result;
}

/**
 * Escape a Typst string literal without its surrounding quotes.
 */
function almaden_bookster_typst_escape_string( $value ) {
	return str_replace( array( '\\', '"', "\n", "\r" ), array( '\\\\', '\\"', '\\n', '' ), (string) $value );
}

function almaden_bookster_typst_size_to_pt( $value, $unit ) {
	$value = (float) $value;
	switch ( strtolower( (string) $unit ) ) {
		case 'px':
			return round( $value * 0.75, 3 );
		case 'em':
		case 'rem':
			return round( $value * 11, 3 );
		default:
			return round( $value, 3 );
	}
}

function almaden_bookster_typst_length( $value, $unit ) {
	$allowed = array( 'mm', 'cm', 'pt', 'in', 'em' );
	$unit    = strtolower( (string) $unit );
	if ( 'px' === $unit ) {
		return round( (float) $value * 0.75, 3 ) . 'pt';
	}
	if ( 'rem' === $unit ) {
		$unit = 'em';
	}
	return round( (float) $value, 3 ) . ( in_array( $unit, $allowed, true ) ? $unit : 'mm' );
}

/**
 * Build plain semantic text for post-compilation integrity checks.
 */
function almaden_bookster_typst_plain_text( $raw ) {
	$text = html_entity_decode( (string) $raw, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$text = str_replace( array( "\r\n", "\r" ), "\n", $text );
	$footnotes = array();
	preg_match_all( '/(?:^|\n)\[\^([^\]]+)\]:\s*([^\n]+)/', $text, $definitions, PREG_SET_ORDER );
	foreach ( $definitions as $definition ) {
		$footnotes[ $definition[1] ] = trim( $definition[2] );
	}
	$text = preg_replace( '/(?:^|\n)\[\^([^\]]+)\]:\s*([^\n]+)/', "\n", $text );
	$text = preg_replace( '/\[\^[^\]]+\]/', '', $text );
	$text = preg_replace( '/<foreign\s+lang=(?:"|\')[^"\']+(?:"|\')\s*>/i', '', $text );
	$text = preg_replace( '/<\/foreign>|<\/?u>/i', '', $text );
	$text = preg_replace( '/\[\/?(?:size|font)[^\]]*\]/i', '', $text );
	$text = preg_replace( '/\[\/?(?:align|box|columns?|col)[^\]]*\]/i', '', $text );
	$text = preg_replace( '/\[(?:gap:[^\]]+|page[-_]?break|(?:book[-_]?)?logo[^\]]*)\]/i', '', $text );
	$text = preg_replace( '/^\s{0,3}(?:#{1,6}|>|-|\d+\.)\s+/m', '', $text );
	$text = str_replace( array( '**', '*' ), '', $text );
	$text = preg_replace( '/<[^>]+>/', ' ', $text );
	return preg_replace( '/\s+/u', ' ', trim( $text ) );
}

/**
 * Return footnote bodies separately because PDF extraction places them at page bottom.
 */
function almaden_bookster_typst_plain_footnotes( $raw ) {
	preg_match_all( '/(?:^|\n)\[\^([^\]]+)\]:\s*([^\n]+)/', (string) $raw, $definitions, PREG_SET_ORDER );
	$output = array();
	foreach ( $definitions as $definition ) {
		$text = preg_replace( '/<[^>]+>|\*\*|\*/', '', $definition[2] );
		$text = preg_replace( '/\s+/u', ' ', trim( $text ) );
		if ( '' !== $text ) {
			$output[] = $text;
		}
	}
	return $output;
}
