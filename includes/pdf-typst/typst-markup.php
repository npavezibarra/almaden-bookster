<?php
/**
 * Deterministic RAW-to-Typst renderer.
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'ALMADEN_TYPST_TESTING' ) ) {
	exit;
}

/**
 * Escape plain text for Typst markup mode.
 */
function almaden_bookster_typst_escape_markup( $text ) {
	return preg_replace( '/([\\\\#\[\]\$\*_<>@])/', '\\\\$1', (string) $text );
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
			$output .= '#strong[' . almaden_bookster_typst_render_inline( $match[1][0], $footnotes, $depth + 1, $exceptions, $footnote_mode, $footnote_numbers ) . ']';
			break;
		case 3:
			$output .= '#emph[' . almaden_bookster_typst_render_inline( $match[1][0], $footnotes, $depth + 1, $exceptions, $footnote_mode, $footnote_numbers ) . ']';
			break;
		case 4:
			$size_pt = almaden_bookster_typst_size_to_pt( $match[1][0], $match[2][0] );
			$output .= '#text(size: ' . $size_pt . 'pt)[' .
				almaden_bookster_typst_render_inline( $match[3][0], $footnotes, $depth + 1, $exceptions, $footnote_mode, $footnote_numbers ) . ']';
			break;
		case 5:
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
		case 6:
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

function almaden_bookster_typst_render_content_image_block( $html, &$assets, $asset_mode = 'original' ) {
	$html = trim( (string) $html );
	if ( '' === $html || ! preg_match( '/<img\b[^>]*>/i', $html, $img_match ) ) {
		return '';
	}

	if ( ! is_array( $assets ) ) {
		$assets = array();
	}

	$figure_attrs = almaden_bookster_typst_parse_html_attributes( $html );
	$img_attrs = almaden_bookster_typst_parse_html_attributes( $img_match[0] );
	$source = array(
		'url'         => trim( (string) ( $img_attrs['src'] ?? '' ) ),
		'original_url' => trim( (string) ( $img_attrs['data-original-src'] ?? $img_attrs['src'] ?? '' ) ),
		'preview_url' => trim( (string) ( $img_attrs['data-preview-src'] ?? '' ) ),
	);
	$image_url = almaden_bookster_typst_resolve_image_url_for_asset_mode( $source, $asset_mode );
	$image_asset = almaden_bookster_typst_register_upload( array_merge( $source, array( 'url' => $image_url ) ), $assets, $asset_mode );
	if ( '' === $image_asset ) {
		return '';
	}

	$caption = '';
	if ( preg_match( '/<figcaption\b[^>]*>([\s\S]*?)<\/figcaption>/i', $html, $caption_match ) ) {
		$caption = trim( preg_replace( '/<[^>]+>/', ' ', $caption_match[1] ) );
	}
	$fit = strtolower( trim( (string) ( $figure_attrs['data-fit'] ?? 'contain' ) ) );
	if ( ! in_array( $fit, array( 'contain', 'cover', 'stretch', 'fill' ), true ) ) {
		$fit = 'contain';
	}
	$caption_markup = '';
	if ( '' !== $caption ) {
		$caption_markup = "\n#v(1.5mm)\n#align(center)[#text(size: 8.5pt)[ " . almaden_bookster_typst_escape_markup( $caption ) . ' ]]';
	}

	return '#block(breakable: false, width: 100%)[' . "\n" .
		'#align(center)[#image("' . almaden_bookster_typst_escape_string( $image_asset ) . '", width: 100%, fit: "' . almaden_bookster_typst_escape_string( $fit ) . '")]' .
		$caption_markup . "\n]";
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
	$raw = preg_replace_callback( '/<figure\b[\s\S]*?<\/figure>/i', function ( $match ) use ( &$image_placeholders, &$image_counter, &$assets, $asset_mode ) {
		$figure = (string) ( $match[0] ?? '' );
		if (
			false === stripos( $figure, 'pdf-book-image-block' )
			&& false === stripos( $figure, 'data-image-block="1"' )
			&& false === stripos( $figure, "data-image-block='1'" )
		) {
			return $figure;
		}

		$placeholder = '%%ALMADEN_IMAGE_BLOCK_' . $image_counter++ . '%%';
		$rendered = almaden_bookster_typst_render_content_image_block( $figure, $assets, $asset_mode );
		if ( '' === $rendered ) {
			return $figure;
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

	foreach ( $lines as $line ) {
		$trimmed = trim( $line );
		if ( '' === $trimmed ) {
			$flush_paragraph();
			$close_list();
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
				$output[] = '#heading(level: ' . $level . ')[#text(font: "' .
					almaden_bookster_typst_escape_string( $style['font_family'] ) . '", size: ' .
					almaden_bookster_typst_size_to_pt( $style['font_size'] ?? 0, 'pt' ) . 'pt, weight: ' .
					almaden_bookster_typst_font_weight( $style['font_weight'] ?? 'normal' ) . ', style: "' .
					almaden_bookster_typst_escape_string( $style['font_style'] ?? 'normal' ) . '", tracking: ' .
					almaden_bookster_typst_length( $style['letter_spacing'] ?? 0, 'pt' ) . ')[' .
					$rendered_heading . ']]';
			} else {
				$output[] = '#heading(level: ' . $level . ')[' . $rendered_heading . ']';
			}
			continue;
		}
		if ( preg_match( '/^>\s*(.*)$/', $trimmed, $quote ) ) {
			$flush_paragraph();
			$close_list();
			$output[] = '#quote(block: true)[' .
				almaden_bookster_typst_render_inline( $quote[1], $footnotes, 0, $exceptions, $footnote_mode, $footnote_numbers ) . ']';
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
