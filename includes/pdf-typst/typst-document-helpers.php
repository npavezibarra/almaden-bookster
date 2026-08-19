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
require_once __DIR__ . '/page-styles/bootstrap.php';

function almaden_bookster_typst_number( $settings, $key, $fallback, $min, $max ) {
	$value = isset( $settings[ $key ] ) && is_numeric( $settings[ $key ] ) ? (float) $settings[ $key ] : $fallback;
	return max( $min, min( $max, $value ) );
}

function almaden_bookster_typst_running_content_margin( $page_margin, $running_reserve ) {
	return round( max( 0, (float) $page_margin, (float) $running_reserve ), 4 );
}

/**
 * Resolve a string override while treating an empty value as "inherit".
 *
 * Chapter payloads always include the override keys, even when the editor is
 * using the book-level setting. A null-coalesce expression therefore cannot
 * distinguish an intentional override from an empty inherited value.
 */
function almaden_bookster_typst_string_override( $override, $fallback ) {
	$override = trim( (string) $override );
	return '' !== $override ? $override : (string) $fallback;
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

function almaden_bookster_typst_normalize_asset_mode( $value ) {
	return 'original' === (string) $value ? 'original' : 'optimized';
}

function almaden_bookster_typst_resolve_upload_path_from_url( $url ) {
	$url = trim( (string) $url );
	if ( '' === $url || ! function_exists( 'wp_upload_dir' ) ) {
		return '';
	}

	$uploads = wp_upload_dir();
	$base_url = rtrim( (string) ( $uploads['baseurl'] ?? '' ), '/' );
	$base_dir = rtrim( (string) ( $uploads['basedir'] ?? '' ), DIRECTORY_SEPARATOR );
	if ( '' === $base_url || '' === $base_dir ) {
		return '';
	}

	$clean_url = strtok( $url, '?' );
	$base_path = parse_url( $base_url, PHP_URL_PATH );
	$url_path  = parse_url( $clean_url, PHP_URL_PATH );
	if ( ! is_string( $base_path ) || ! is_string( $url_path ) || 0 !== strpos( $url_path, $base_path . '/' ) ) {
		return '';
	}

	$relative = ltrim( rawurldecode( substr( $url_path, strlen( $base_path ) ) ), '/' );
	$relative = str_replace( array( '/', '\\' ), DIRECTORY_SEPARATOR, $relative );
	if ( '' === $relative || false !== strpos( $relative, '..' ) ) {
		return '';
	}

	$source_path = $base_dir . DIRECTORY_SEPARATOR . $relative;
	return is_file( $source_path ) ? $source_path : '';
}

function almaden_bookster_typst_resolve_attachment_original_path( $attachment_id ) {
	$attachment_id = function_exists( 'absint' ) ? absint( $attachment_id ) : max( 0, (int) $attachment_id );
	if ( $attachment_id <= 0 ) {
		return '';
	}

	$file_path = '';
	if ( function_exists( 'wp_get_original_image_path' ) ) {
		$file_path = wp_get_original_image_path( $attachment_id );
	}
	if ( empty( $file_path ) && function_exists( 'get_attached_file' ) ) {
		$file_path = get_attached_file( $attachment_id );
	}

	return ! empty( $file_path ) && file_exists( $file_path ) ? $file_path : '';
}

function almaden_bookster_typst_resolve_attachment_preview_url( $attachment_id ) {
	$attachment_id = function_exists( 'absint' ) ? absint( $attachment_id ) : max( 0, (int) $attachment_id );
	if ( $attachment_id <= 0 ) {
		return '';
	}

	if ( function_exists( 'wp_get_attachment_image_src' ) ) {
		foreach ( array( 'medium_large', 'large', 'medium', 'thumbnail' ) as $size ) {
			$image = wp_get_attachment_image_src( $attachment_id, $size );
			if ( ! empty( $image[0] ) ) {
				return function_exists( 'esc_url_raw' ) ? esc_url_raw( $image[0] ) : (string) $image[0];
			}
		}
	}

	if ( function_exists( 'wp_get_original_image_url' ) ) {
		$original = wp_get_original_image_url( $attachment_id );
		if ( ! empty( $original ) ) {
			return function_exists( 'esc_url_raw' ) ? esc_url_raw( $original ) : (string) $original;
		}
	}

	if ( function_exists( 'wp_get_attachment_url' ) ) {
		$attachment_url = wp_get_attachment_url( $attachment_id );
		if ( ! empty( $attachment_url ) ) {
			return function_exists( 'esc_url_raw' ) ? esc_url_raw( $attachment_url ) : (string) $attachment_url;
		}
	}

	return '';
}

function almaden_bookster_typst_resolve_image_url_for_asset_mode( $source, $asset_mode = 'original' ) {
	$asset_mode = almaden_bookster_typst_normalize_asset_mode( $asset_mode );

	if ( is_array( $source ) ) {
		$attachment_id = function_exists( 'absint' ) ? absint( $source['attachment_id'] ?? 0 ) : max( 0, (int) ( $source['attachment_id'] ?? 0 ) );
		$original_url = function_exists( 'esc_url_raw' )
			? esc_url_raw( (string) ( $source['original_url'] ?? $source['url'] ?? '' ) )
			: trim( (string) ( $source['original_url'] ?? $source['url'] ?? '' ) );
		$preview_url = function_exists( 'esc_url_raw' )
			? esc_url_raw( (string) ( $source['preview_url'] ?? '' ) )
			: trim( (string) ( $source['preview_url'] ?? '' ) );
		$url = function_exists( 'esc_url_raw' )
			? esc_url_raw( (string) ( $source['url'] ?? '' ) )
			: trim( (string) ( $source['url'] ?? '' ) );

		if ( 'optimized' === $asset_mode ) {
			if ( '' !== $preview_url ) {
				return $preview_url;
			}
			if ( $attachment_id > 0 ) {
				$attachment_preview = almaden_bookster_typst_resolve_attachment_preview_url( $attachment_id );
				if ( '' !== $attachment_preview ) {
					return $attachment_preview;
				}
			}
			if ( '' !== $url ) {
				return $url;
			}
		}

		if ( '' !== $original_url ) {
			return $original_url;
		}
		if ( $attachment_id > 0 ) {
			$attachment_original = function_exists( 'wp_get_original_image_url' ) ? wp_get_original_image_url( $attachment_id ) : '';
			if ( empty( $attachment_original ) && function_exists( 'wp_get_attachment_url' ) ) {
				$attachment_original = wp_get_attachment_url( $attachment_id );
			}
			if ( ! empty( $attachment_original ) ) {
				return function_exists( 'esc_url_raw' ) ? esc_url_raw( $attachment_original ) : (string) $attachment_original;
			}
		}
		if ( '' !== $url ) {
			return $url;
		}
		if ( 'optimized' === $asset_mode && '' !== $preview_url ) {
			return $preview_url;
		}

		return '';
	}

	$url = trim( (string) $source );
	if ( '' === $url ) {
		return '';
	}

	if ( 'optimized' === $asset_mode && function_exists( 'attachment_url_to_postid' ) ) {
		$attachment_id = attachment_url_to_postid( function_exists( 'esc_url_raw' ) ? esc_url_raw( $url ) : $url );
		if ( $attachment_id <= 0 && preg_match( '/-scaled(?=\.[a-zA-Z0-9]+$)/', $url ) ) {
			$attachment_id = attachment_url_to_postid( preg_replace( '/-scaled(?=\.[a-zA-Z0-9]+$)/', '', $url ) );
		}
		if ( $attachment_id > 0 ) {
			$preview_url = almaden_bookster_typst_resolve_attachment_preview_url( $attachment_id );
			if ( '' !== $preview_url ) {
				return $preview_url;
			}
		}
	}

	return function_exists( 'esc_url_raw' ) ? esc_url_raw( $url ) : $url;
}

function almaden_bookster_typst_resolve_image_path_for_asset_mode( $source, $asset_mode = 'original' ) {
	$image_url = almaden_bookster_typst_resolve_image_url_for_asset_mode( $source, $asset_mode );
	if ( '' === $image_url ) {
		return '';
	}

	$path = almaden_bookster_typst_resolve_upload_path_from_url( $image_url );
	if ( '' !== $path ) {
		return $path;
	}

	if ( is_array( $source ) ) {
		$attachment_id = function_exists( 'absint' ) ? absint( $source['attachment_id'] ?? 0 ) : max( 0, (int) ( $source['attachment_id'] ?? 0 ) );
		if ( $attachment_id > 0 ) {
			$attachment_path = almaden_bookster_typst_resolve_attachment_original_path( $attachment_id );
			if ( '' !== $attachment_path ) {
				return $attachment_path;
			}
		}
	}

	return '';
}

function almaden_bookster_typst_register_upload( $source, &$assets, $asset_mode = 'original' ) {
	$path = almaden_bookster_typst_resolve_image_path_for_asset_mode( $source, $asset_mode );
	if ( '' === $path || ! is_file( $path ) ) {
		return '';
	}

	$extension = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
	if ( ! in_array( $extension, array( 'png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'pdf' ), true ) ) {
		return '';
	}
	$name = hash( 'sha256', $path . '|' . filemtime( $path ) ) . '.' . $extension;
	$assets[ $name ] = $path;

	return 'assets/' . $name;
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

function almaden_bookster_typst_chapter_blank_count( $chapter, $position ) {
	$key = 'chapter_blank_' . $position;
	$value = is_array( $chapter ) && isset( $chapter[ $key ] ) ? $chapter[ $key ] : 0;

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

function almaden_bookster_typst_credits_vertical_alignment( $align ) {
	$align = strtolower( trim( (string) $align ) );
	return in_array( $align, array( 'top', 'center', 'bottom' ), true ) ? $align : 'bottom';
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
	$hide_opening = '1' === (string) ( $chapter['hide_opening'] ?? '0' ) && ! $is_toc && ! $is_credits;
	$show_title = $has_title && empty( $chapter['hide_title'] ) && ! $is_credits && ! $hide_opening;
	$show_prefix = ! $hide_opening
		&& ! $is_toc
		&& ! $is_credits
		&& almaden_bookster_typst_bool( $settings['chapter_prefix_show'] ?? false )
		&& '1' !== (string) ( $chapter['exclude_from_numbering'] ?? '0' );
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

function almaden_bookster_typst_chapter_prefix_ornament( $ornament ) {
	$ornament = strtolower( trim( (string) $ornament ) );

	return in_array( $ornament, array( 'none', 'line_below', 'line_above_below', 'asterisks' ), true ) ? $ornament : 'none';
}

function almaden_bookster_typst_render_chapter_prefix( $prefix_text, $style, $alignment, $ornament = 'none' ) {
	$style = is_array( $style ) ? $style : array();
	$alignment = in_array( $alignment, array( 'left', 'center', 'right' ), true ) ? $alignment : 'center';
	$ornament = almaden_bookster_typst_chapter_prefix_ornament( $ornament );
	$font_family = almaden_bookster_typst_font_family( $style['font_family'] ?? 'Playfair Display', 'Playfair Display' );
	$font_size = isset( $style['font_size'] ) && is_numeric( $style['font_size'] ) ? max( 6, min( 100, (float) $style['font_size'] ) ) : 16;
	$font_weight = almaden_bookster_typst_font_weight( $style['font_weight'] ?? 'normal' );
	$font_style = isset( $style['font_style'] ) ? strtolower( trim( (string) $style['font_style'] ) ) : 'normal';
	if ( ! in_array( $font_style, array( 'normal', 'italic', 'oblique' ), true ) ) {
		$font_style = 'normal';
	}
	$tracking = isset( $style['letter_spacing'] ) && is_numeric( $style['letter_spacing'] ) ? round( max( -20, min( 20, (float) $style['letter_spacing'] ) ), 3 ) : 0;
	$body = '#text(font: "' . almaden_bookster_typst_escape_string( $font_family ) . '", size: ' . round( $font_size, 3 ) . 'pt, weight: ' . $font_weight . ', style: "' . almaden_bookster_typst_escape_string( $font_style ) . '", tracking: ' . $tracking . 'pt)[' . almaden_bookster_typst_escape_markup( $prefix_text ) . ']';
	$parts = array();

	if ( 'line_above_below' === $ornament ) {
		$parts[] = '#align(center)[#line(length: 100%, stroke: 0.35pt)]';
	}

	$parts[] = '#align(' . $alignment . ')[' . $body . ']';

	if ( 'line_below' === $ornament || 'line_above_below' === $ornament ) {
		$parts[] = '#align(center)[#line(length: 100%, stroke: 0.35pt)]';
	}

	if ( 'asterisks' === $ornament ) {
		$parts[] = '#align(center)[' . almaden_bookster_typst_escape_markup( '***' ) . ']';
	}

	return '#block(width: 100%, breakable: false)[' . "\n" .
		'#set par(justify: false, first-line-indent: 0pt)' . "\n" .
		implode( "\n#v(2mm)\n", $parts ) . "\n]";
}
