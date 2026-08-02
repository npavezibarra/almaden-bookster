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

function almaden_bookster_typst_render_inline( $text, $footnotes = array(), $depth = 0, $exceptions = array() ) {
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
			$content = almaden_bookster_typst_render_inline( $match[2][0], $footnotes, $depth + 1, $exceptions );
			$output .= '#text(lang: "' . almaden_bookster_typst_escape_string( $lang ) . '")[' . $content . ']';
			break;
		case 1:
			$output .= '#underline[' . almaden_bookster_typst_render_inline( $match[1][0], $footnotes, $depth + 1, $exceptions ) . ']';
			break;
		case 2:
			$output .= '#strong[' . almaden_bookster_typst_render_inline( $match[1][0], $footnotes, $depth + 1, $exceptions ) . ']';
			break;
		case 3:
			$output .= '#emph[' . almaden_bookster_typst_render_inline( $match[1][0], $footnotes, $depth + 1, $exceptions ) . ']';
			break;
		case 4:
			$size_pt = almaden_bookster_typst_size_to_pt( $match[1][0], $match[2][0] );
			$output .= '#text(size: ' . $size_pt . 'pt)[' .
				almaden_bookster_typst_render_inline( $match[3][0], $footnotes, $depth + 1, $exceptions ) . ']';
			break;
		case 5:
			$family = function_exists( 'almaden_bookster_typst_font_family' )
				? almaden_bookster_typst_font_family( $match[1][0], '' )
				: trim( (string) $match[1][0] );
			if ( '' === $family ) {
				$output .= almaden_bookster_typst_render_inline( $match[2][0], $footnotes, $depth + 1, $exceptions );
				break;
			}
			$output .= '#text(font: "' . almaden_bookster_typst_escape_string( $family ) . '")[' .
				almaden_bookster_typst_render_inline( $match[2][0], $footnotes, $depth + 1, $exceptions ) . ']';
			break;
		case 6:
			$id = $match[1][0];
			if ( isset( $footnotes[ $id ] ) ) {
				$output .= '#footnote[' .
					almaden_bookster_typst_render_inline( $footnotes[ $id ], $footnotes, $depth + 1, $exceptions ) . ']';
			} else {
				$output .= almaden_bookster_typst_escape_markup( $match[0][0] );
			}
			break;
	}

	return $output . almaden_bookster_typst_render_inline( $after, $footnotes, $depth + 1, $exceptions );
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

/**
 * Render RAW block syntax.
 */
function almaden_bookster_typst_render_blocks( $raw, $options = array() ) {
	$raw       = str_replace( array( "\r\n", "\r" ), "\n", (string) $raw );
	$footnotes = array();
	$raw       = preg_replace_callback(
		'/(?:^|\n)\[\^([^\]]+)\]:\s*([^\n]+)/',
		function ( $match ) use ( &$footnotes ) {
			$footnotes[ $match[1] ] = trim( $match[2] );
			return "\n";
		},
		$raw
	);

	return almaden_bookster_typst_render_blocks_with_footnotes( $raw, $footnotes, false, $options );
}

/**
 * Internal block renderer with an already collected footnote map.
 */
function almaden_bookster_typst_render_blocks_with_footnotes( $raw, $footnotes, $allow_embedded_typst = false, $options = array() ) {
	$lines     = explode( "\n", (string) $raw );
	$output    = array();
	$paragraph = array();
	$list_type = '';

	$exceptions = (array) ( $options['hyphenation_exceptions'] ?? array() );
	$heading_styles = (array) ( $options['heading_styles'] ?? array() );
	$flush_paragraph = function () use ( &$paragraph, &$output, $footnotes, $exceptions ) {
		if ( empty( $paragraph ) ) {
			return;
		}
		$text      = trim( implode( ' ', $paragraph ) );
		$output[]  = '#par[' . almaden_bookster_typst_render_inline( $text, $footnotes, 0, $exceptions ) . ']';
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
			$output[] = 'justify' === $value ? '#[#set par(justify: true)' : '#align(' . $value . ')[';
			continue;
		}
		if ( preg_match( '/^\[\/align\]$/i', $trimmed ) ) {
			$flush_paragraph();
			$close_list();
			$output[] = ']';
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
		if ( preg_match( '/^(#{1,6})\s+(.+)$/', $trimmed, $heading ) ) {
			$flush_paragraph();
			$close_list();
			$level    = strlen( $heading[1] );
			$rendered_heading = almaden_bookster_typst_render_inline( $heading[2], $footnotes, 0, $exceptions );
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
				almaden_bookster_typst_render_inline( $quote[1], $footnotes, 0, $exceptions ) . ']';
			continue;
		}
		if ( preg_match( '/^-\s+(.+)$/', $trimmed, $item ) ) {
			$flush_paragraph();
			if ( 'bullet' !== $list_type ) {
				$close_list();
				$list_type = 'bullet';
				$output[]  = '#list(';
			}
			$output[] = '[' . almaden_bookster_typst_render_inline( $item[1], $footnotes, 0, $exceptions ) . '],';
			continue;
		}
		if ( preg_match( '/^\d+\.\s+(.+)$/', $trimmed, $item ) ) {
			$flush_paragraph();
			if ( 'enum' !== $list_type ) {
				$close_list();
				$list_type = 'enum';
				$output[]  = '#enum(';
			}
			$output[] = '[' . almaden_bookster_typst_render_inline( $item[1], $footnotes, 0, $exceptions ) . '],';
			continue;
		}

		// Unknown wrappers are retained as literal text instead of silently losing data.
		$paragraph[] = $trimmed;
	}

	$flush_paragraph();
	$close_list();
	return implode( "\n\n", $output );
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
