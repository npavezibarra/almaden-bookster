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
	return preg_replace( '/([\\\\#\[\]\$\*_<>@`])/', '\\\\$1', (string) $text );
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

function almaden_bookster_typst_render_box_block( $raw, $attrs, $footnotes, $exceptions, $footnote_mode, $footnote_numbers ) {
	$style = almaden_bookster_typst_parse_style_declarations( $attrs['style'] ?? '' );
	$body = preg_replace( '/^\s*\[html\]\s*/i', '', (string) $raw );
	$body = preg_replace( '/\s*\[\/html\]\s*$/i', '', $body );
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
	$lines     = explode( "\n", (string) $raw );
	$output    = array();
	$paragraph = array();
	$list_type = '';
	$align_type = '';

	$exceptions = (array) ( $options['hyphenation_exceptions'] ?? array() );
	$heading_styles = (array) ( $options['heading_styles'] ?? array() );
	$quote_style = (array) ( $options['quote_style'] ?? array() );
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
				$footnote_numbers
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
				$output[] = '#block(width: 100%, breakable: false, above: ' . round( $heading_margin_top, 3 ) . 'pt, below: ' . round( $heading_margin_bottom, 3 ) . 'pt)[' .
					"\n" .
					'#set par(justify: false, first-line-indent: 0pt, leading: ' . $heading_leading . 'em)' . "\n" .
					'#align(' . $heading_align . ')[#heading(level: ' . $level . ')[#almaden-page-colored("content", fill => text(fill: fill, font: "' .
					almaden_bookster_typst_escape_string( $style['font_family'] ) . '", size: ' .
					almaden_bookster_typst_size_to_pt( $style['font_size'] ?? 0, 'pt' ) . 'pt, weight: ' .
					almaden_bookster_typst_font_weight( $style['font_weight'] ?? 'normal' ) . ', style: "' .
					almaden_bookster_typst_escape_string( $style['font_style'] ?? 'normal' ) . '", tracking: ' .
					almaden_bookster_typst_length( $style['letter_spacing'] ?? 0, 'pt' ) . ', hyphenate: ' .
					( ! empty( $style['hyphenate'] ) ? 'true' : 'false' ) . ')[' .
					$rendered_heading . '])]]' . "\n" .
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
	$result = implode( "\n\n", $output );
	if ( ! empty( $image_placeholders ) ) {
		$result = str_replace( array_keys( $image_placeholders ), array_values( $image_placeholders ), $result );
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
	$text = str_replace( array( "\r\n", "\r" ), "\n", (string) $raw );
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
