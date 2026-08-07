<?php
/**
 * Build a complete Typst book document from editor state.
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'ALMADEN_TYPST_TESTING' ) ) {
	exit;
}

require_once __DIR__ . '/typst-markup.php';
require_once __DIR__ . '/typst-fonts.php';
require_once __DIR__ . '/typst-footnotes.php';
require_once __DIR__ . '/page-templates/bootstrap.php';

function almaden_bookster_typst_number( $settings, $key, $fallback, $min, $max ) {
	$value = isset( $settings[ $key ] ) && is_numeric( $settings[ $key ] ) ? (float) $settings[ $key ] : $fallback;
	return max( $min, min( $max, $value ) );
}

function almaden_bookster_typst_transform_title( $title, $transform ) {
	switch ( $transform ) {
		case 'uppercase':
			return function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $title, 'UTF-8' ) : strtoupper( $title );
		case 'lowercase':
			return function_exists( 'mb_strtolower' ) ? mb_strtolower( $title, 'UTF-8' ) : strtolower( $title );
		case 'capitalize':
			if ( function_exists( 'mb_convert_case' ) ) {
				return mb_convert_case( $title, MB_CASE_TITLE, 'UTF-8' );
			}
			return ucwords( strtolower( $title ) );
		default:
			return $title;
	}
}

function almaden_bookster_typst_normalize_header_footer_type( $type ) {
	$type = strtolower( trim( (string) $type ) );
	if ( in_array( $type, array( 'book_title', 'chapter_title', 'page_number', 'custom', 'author' ), true ) ) {
		return $type;
	}
	return 'blank';
}

function almaden_bookster_typst_bool( $value ) {
	return ! empty( $value ) && '0' !== (string) $value && 'false' !== strtolower( (string) $value );
}

function almaden_bookster_typst_credits_blank_count( $settings, $position ) {
	$key    = 'credits_blank_' . $position;
	$config = isset( $settings['credits_config'] ) && is_array( $settings['credits_config'] )
		? $settings['credits_config']
		: array();
	$editorial = isset( $config['editorial'] ) && is_array( $config['editorial'] )
		? $config['editorial']
		: array();
	$value = $editorial[ 'blank_' . $position ] ?? ( $settings[ $key ] ?? 0 );

	return is_numeric( $value ) ? max( 0, min( 999, (int) $value ) ) : 0;
}

function almaden_bookster_typst_is_credits_chapter( $chapter ) {
	if ( isset( $chapter['is_credits'] ) && '1' === (string) $chapter['is_credits'] ) {
		return true;
	}

	$content = (string) ( $chapter['content'] ?? '' );
	$content = function_exists( 'wp_strip_all_tags' ) ? wp_strip_all_tags( $content ) : strip_tags( $content );
	$content = trim( $content );
	return 'En este capítulo se generará automáticamente la página de Créditos.' === $content;
}

function almaden_bookster_typst_credits_text_style( $style, $fallback_family, $fallback_size, $fallback_weight, $fallback_line_height, $resolve_font, $fallback_tracking = 0, $fallback_align = '' ) {
	$style       = is_array( $style ) ? $style : array();
	$family      = almaden_bookster_typst_font_family( $style['font_family'] ?? '', $fallback_family );
	$size_px     = isset( $style['font_size'] ) && is_numeric( $style['font_size'] ) ? (float) $style['font_size'] : (float) $fallback_size / 0.75;
	$weight      = almaden_bookster_typst_font_weight( $style['font_weight'] ?? $fallback_weight, $fallback_weight );
	$font_style  = isset( $style['font_style'] ) ? strtolower( trim( (string) $style['font_style'] ) ) : 'normal';
	if ( ! in_array( $font_style, array( 'normal', 'italic', 'oblique' ), true ) ) {
		$font_style = 'normal';
	}
	$line_height = isset( $style['line_height'] ) && is_numeric( $style['line_height'] ) ? (float) $style['line_height'] : (float) $fallback_line_height;
	$tracking_px = isset( $style['letter_spacing'] ) && is_numeric( $style['letter_spacing'] ) ? (float) $style['letter_spacing'] : (float) $fallback_tracking;
	$align       = in_array( $style['text_align'] ?? '', array( 'left', 'center', 'right' ), true ) ? $style['text_align'] : almaden_bookster_typst_credits_alignment( $fallback_align );

	return array(
		'family'      => $resolve_font( $family, $weight ),
		'size'        => round( max( 6, $size_px * 0.75 ), 3 ),
		'weight'      => $weight,
		'style'       => $font_style,
		'leading'     => round( max( 0, $line_height - 1 ), 4 ),
		'tracking'    => round( $tracking_px * 0.75, 3 ),
		'align'       => $align,
		'separator'   => ! empty( $style['show_separator'] ),
	);
}

function almaden_bookster_typst_credits_styled_block( $body, $style ) {
	if ( '' === trim( $body ) ) {
		return '';
	}
	$aligned_body = '' !== $style['align'] ? '#align(' . $style['align'] . ')[ ' . $body . ' ]' : $body;
	return '#[' . "\n" .
		'#set text(font: "' . almaden_bookster_typst_escape_string( $style['family'] ) . '", size: ' . $style['size'] . 'pt, weight: ' . $style['weight'] . ', style: "' . almaden_bookster_typst_escape_string( $style['style'] ) . '", tracking: ' . $style['tracking'] . 'pt)' . "\n" .
		'#set par(leading: ' . $style['leading'] . 'em, spacing: 4pt)' . "\n" .
		$aligned_body . "\n" .
		"]\n";
}

function almaden_bookster_typst_credits_alignment( $align ) {
	return in_array( $align, array( 'left', 'center', 'right' ), true ) ? $align : '';
}

function almaden_bookster_typst_normalize_cover_settings( $cover_settings ) {
	if ( is_string( $cover_settings ) && '' !== trim( $cover_settings ) ) {
		$decoded = json_decode( $cover_settings, true );
		if ( is_array( $decoded ) ) {
			$cover_settings = $decoded;
		}
	}

	return is_array( $cover_settings ) ? $cover_settings : array();
}

function almaden_bookster_typst_cover_logo_url_from_settings( $cover_settings ) {
	$cover_settings = almaden_bookster_typst_normalize_cover_settings( $cover_settings );
	$text_layers = $cover_settings['text_layers'] ?? array();
	if ( is_string( $text_layers ) && '' !== trim( $text_layers ) ) {
		$decoded = json_decode( $text_layers, true );
		if ( is_array( $decoded ) ) {
			$text_layers = $decoded;
		}
	}
	if ( ! is_array( $text_layers ) || empty( $text_layers ) ) {
		return '';
	}

	$logo_group = null;
	foreach ( $text_layers as $layer ) {
		if ( is_array( $layer ) && 'group' === ( $layer['type'] ?? '' ) && ( true === ( $layer['isBookLogo'] ?? false ) || 'true' === (string) ( $layer['isBookLogo'] ?? '' ) ) ) {
			$logo_group = $layer;
			break;
		}
	}
	if ( ! is_array( $logo_group ) ) {
		return '';
	}

	$group_id = trim( (string) ( $logo_group['id'] ?? '' ) );
	if ( '' === $group_id ) {
		return '';
	}

	foreach ( $text_layers as $layer ) {
		if ( ! is_array( $layer ) ) {
			continue;
		}
		if ( 'image' !== ( $layer['type'] ?? '' ) ) {
			continue;
		}
		if ( trim( (string) ( $layer['parentId'] ?? '' ) ) !== $group_id ) {
			continue;
		}
		$url = trim( (string) ( $layer['url'] ?? '' ) );
		if ( '' !== $url ) {
			return $url;
		}
	}

	return '';
}

function almaden_bookster_typst_resolve_credits_logo_url( $logo, $cover_settings ) {
	$logo = is_array( $logo ) ? $logo : array();
	$source = strtolower( trim( (string) ( $logo['logo_source'] ?? $logo['source_type'] ?? $logo['mode'] ?? 'image' ) ) );
	if ( 'cover_logo' === $source ) {
		return almaden_bookster_typst_cover_logo_url_from_settings( $cover_settings );
	}

	return trim( (string) ( $logo['logo_url'] ?? $logo['image_url'] ?? $logo['url'] ?? '' ) );
}

function almaden_bookster_typst_credits_line_block( $body, $align ) {
	$align = almaden_bookster_typst_credits_alignment( $align );
	return '' !== $align ? '#align(' . $align . ')[' . $body . ']' : $body;
}

function almaden_bookster_typst_chapter_opening_visibility( $chapter, $settings ) {
	$chapter = is_array( $chapter ) ? $chapter : array();
	$settings = is_array( $settings ) ? $settings : array();
	$has_title = '' !== trim( (string) ( $chapter['title'] ?? '' ) );
	$is_toc    = isset( $chapter['is_toc'] ) && '1' === (string) $chapter['is_toc'];
	$is_credits = almaden_bookster_typst_is_credits_chapter( $chapter );
	$hide_header = almaden_bookster_typst_bool( $chapter['hide_header'] ?? false )
		|| almaden_bookster_typst_bool( $chapter['hide_all_headers_footers'] ?? false );
	$hide_opening = '1' === (string) ( $chapter['hide_opening'] ?? '0' ) && ! $is_toc && ! $is_credits;
	$show_title = $has_title && empty( $chapter['hide_title'] ) && ! $is_credits && ! $hide_opening;
	$show_prefix = ! $hide_opening
		&& ! $is_toc
		&& ! $is_credits
		&& almaden_bookster_typst_bool( $settings['chapter_prefix_show'] ?? false )
		&& '1' !== (string) ( $chapter['exclude_from_numbering'] ?? '0' )
		&& ! $hide_header;
	$show_subtitle = ! $hide_opening
		&& ! $is_toc
		&& ! $is_credits
		&& '' !== trim( (string) ( $chapter['subtitle_text'] ?? '' ) )
		&& ( ! isset( $settings['chapter_subtitle_show'] ) || almaden_bookster_typst_bool( $settings['chapter_subtitle_show'] ) );

	return array(
		'has_title'          => $has_title,
		'show_title'         => $show_title,
		'show_prefix'        => $show_prefix,
		'show_subtitle'      => $show_subtitle,
		'has_visible_content' => $show_title || $show_prefix || $show_subtitle,
	);
}
