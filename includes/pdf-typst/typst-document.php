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

function almaden_bookster_typst_toc_leader_fill( $leader_style ) {
	switch ( strtolower( trim( (string) $leader_style ) ) ) {
		case 'solid':
			return '#line(length: 100%)';
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

function almaden_bookster_typst_render_toc( $chapter, $chapters, $settings, $fallbacks, &$assets, $resolve_font, $show_title = true ) {
	$chapter = is_array( $chapter ) ? $chapter : array();
	$chapters = is_array( $chapters ) ? $chapters : array();
	$show_title = $show_title && ! almaden_bookster_typst_bool( $chapter['toc_hide_title'] ?? false );

	$title_style = almaden_bookster_typst_credits_text_style(
		array(
			'font_family'   => $chapter['toc_title_font_family'] ?? '',
			'font_size'     => $chapter['toc_title_font_size'] ?? ( $settings['chapter_title_font_size'] ?? 24 ),
			'font_weight'   => $chapter['toc_title_font_weight'] ?? ( $settings['chapter_title_font_weight'] ?? 'bold' ),
			'font_style'    => $chapter['toc_title_font_style'] ?? ( $settings['chapter_title_font_style'] ?? 'normal' ),
			'line_height'   => $chapter['toc_title_line_height'] ?? ( $settings['chapter_title_line_height'] ?? 1.2 ),
			'text_align'    => $chapter['toc_title_align'] ?? ( $settings['chapter_title_align'] ?? 'center' ),
			'letter_spacing'=> $chapter['toc_title_letter_spacing'] ?? ( $settings['chapter_title_letter_spacing'] ?? 0 ),
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
			'font_family'    => $chapter['toc_font_family'] ?? '',
			'font_size'      => $chapter['toc_font_size'] ?? ( $settings['font_size_content'] ?? 11.5 ),
			'font_weight'    => $chapter['toc_font_weight'] ?? 'normal',
			'font_style'     => $chapter['toc_font_style'] ?? 'normal',
			'line_height'    => $chapter['toc_line_height'] ?? 1.8,
			'letter_spacing' => $chapter['toc_letter_spacing'] ?? 0,
			'text_align'     => $chapter['toc_item_align'] ?? 'left',
		),
		$fallbacks['item_family'],
		$fallbacks['item_size'],
		$fallbacks['item_weight'],
		$fallbacks['item_line_height'],
		$resolve_font
	);

	$title_text = almaden_bookster_typst_transform_title( almaden_bookster_typst_toc_title_text( $chapter ), $chapter['toc_title_text_transform'] ?? 'none' );
	$title_padding_top = is_numeric( $chapter['toc_title_padding_top'] ?? null ) ? (float) $chapter['toc_title_padding_top'] : 0.0;
	$title_padding_bottom = is_numeric( $chapter['toc_title_padding_bottom'] ?? null ) ? (float) $chapter['toc_title_padding_bottom'] : 1.5;
	$item_spacing_pt = is_numeric( $chapter['toc_item_spacing'] ?? null ) ? round( (float) $chapter['toc_item_spacing'] * 0.75, 3 ) : 0.0;
	$leader_fill = almaden_bookster_typst_toc_leader_fill( $chapter['toc_leader_style'] ?? 'dotted' );
	$item_align = in_array( $item_style['align'], array( 'left', 'center', 'right' ), true ) ? $item_style['align'] : 'left';
	$enumerate = strtolower( trim( (string) ( $chapter['toc_enumerate'] ?? 'none' ) ) );
	$visible_chapters = array();
	$running_index = 0;
	foreach ( $chapters as $toc_index => $toc_chapter ) {
		if ( ! is_array( $toc_chapter ) ) {
			continue;
		}
		if ( '1' === (string) ( $toc_chapter['is_toc'] ?? '' ) || '1' === (string) ( $toc_chapter['is_credits'] ?? '' ) || '1' === (string) ( $toc_chapter['exclude_from_numbering'] ?? '' ) ) {
			continue;
		}
		++$running_index;
		$chapter_id = trim( (string) ( $toc_chapter['id'] ?? (string) ( $toc_index + 1 ) ) );
		$label = 'almaden-chapter-start-' . preg_replace( '/[^0-9A-Za-z_-]/', '', $chapter_id );
		$chapter_title = almaden_bookster_typst_transform_title( trim( (string) ( $toc_chapter['title'] ?? 'Capítulo' ) ), $chapter['toc_text_transform'] ?? 'none' );
		$prefix = '';
		if ( 'decimal' === $enumerate ) {
			$prefix = $running_index . '. ';
		} elseif ( 'roman' === $enumerate ) {
			$prefix = almaden_bookster_typst_toc_roman( $running_index ) . '. ';
		} elseif ( 'bullet' === $enumerate ) {
			$prefix = '• ';
		}
		$visible_chapters[] = array(
			'label'  => $label,
			'title'  => $chapter_title,
			'prefix' => $prefix,
		);
	}

	if ( empty( $visible_chapters ) ) {
		return '';
	}

	$output = '';
	if ( $show_title ) {
		if ( $title_padding_top > 0 ) {
			$output .= '#v(' . round( $title_padding_top, 4 ) . 'cm)' . "\n";
		}
		$output .= '#block(width: 100%, breakable: false)[' . "\n";
		$output .= '#set text(font: "' . almaden_bookster_typst_escape_string( $title_style['family'] ) . '", size: ' . $title_style['size'] . 'pt, weight: ' . $title_style['weight'] . ', style: "' . almaden_bookster_typst_escape_string( $title_style['style'] ) . '", tracking: ' . $title_style['tracking'] . 'pt)' . "\n";
		$output .= '#set par(leading: ' . $title_style['leading'] . 'em, spacing: 0pt)' . "\n";
		$output .= '#align(' . $title_style['align'] . ')[ ' . almaden_bookster_typst_escape_markup( $title_text ) . ' ]' . "\n";
		$output .= ']' . "\n";
		$output .= ( $title_padding_bottom > 0 ? '#v(' . round( $title_padding_bottom, 4 ) . 'cm)' . "\n" : '' );
	}
	// TOC rows contain markup calls (`#set`, `#block`, `#align`). Wrapping
	// them in `context {}` switches Typst to code mode and makes every `#`
	// invalid. Only the page-number expression below needs `#context`.
	$output .= '#block(width: 100%)[' . "\n";
	$output .= '#set text(font: "' . almaden_bookster_typst_escape_string( $item_style['family'] ) . '", size: ' . $item_style['size'] . 'pt, weight: ' . $item_style['weight'] . ', style: "' . almaden_bookster_typst_escape_string( $item_style['style'] ) . '", tracking: ' . $item_style['tracking'] . 'pt)' . "\n";
	$output .= '#set par(leading: ' . $item_style['leading'] . 'em, spacing: ' . $item_spacing_pt . 'pt)' . "\n";
	foreach ( $visible_chapters as $entry_index => $entry ) {
		$prefix = '' !== $entry['prefix'] ? $entry['prefix'] . ' ' : '';
		$page_expr = '#context { let marks = query(<' . $entry['label'] . '>); if marks.len() > 0 { counter(page).display(at: marks.last().location()) } else { "" } }';
		$output .= '#block(width: 100%, breakable: false)[' . "\n";
		$output .= '  #align(' . $item_align . ')[ ' . almaden_bookster_typst_escape_markup( $prefix . $entry['title'] ) . ' ]' . "\n";
		$output .= '  #box(width: 1fr)[' . ( '' !== $leader_fill ? '#align(center)[' . $leader_fill . ']' : '' ) . ']' . "\n";
		$output .= '  ' . $page_expr . "\n";
		$output .= ']' . "\n";
		if ( $entry_index < count( $visible_chapters ) - 1 ) {
			$output .= '#v(' . $item_spacing_pt . 'pt)' . "\n";
		}
	}
	$output .= ']' . "\n";

	return $output;
}

function almaden_bookster_typst_render_credits( $config, $author_label, $fallbacks, &$assets, $resolve_font, $cover_settings = array() ) {
	$config = is_array( $config ) ? $config : array();
	$styles = isset( $config['section_styles'] ) && is_array( $config['section_styles'] ) ? $config['section_styles'] : array();
	$order  = isset( $config['section_order'] ) && is_array( $config['section_order'] )
		? $config['section_order']
		: array( 'editorial', 'people', 'collaborators', 'logos', 'legal' );
	$sections = array();
	$role_labels = array(
		'author' => 'Autor', 'coauthor' => 'Coautor', 'editor' => 'Editor', 'translator' => 'Traductor',
		'designer' => 'Diseñador', 'proofreader' => 'Corrector', 'photographer' => 'Fotógrafo', 'other' => 'Otro',
	);
	$license_labels = array( 'all_rights_reserved' => 'Todos los derechos reservados', 'creative_commons' => 'Creative Commons' );
	$format_date = static function ( $value ) {
		if ( ! preg_match( '/^(\d{4})-(\d{2})/', trim( (string) $value ), $match ) ) {
			return trim( (string) $value );
		}
		$months = array( 1 => 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre' );
		return ( $months[ (int) $match[2] ] ?? $match[2] ) . ' ' . $match[1];
	};
	$text = static function ( $value ) {
		return almaden_bookster_typst_escape_markup( trim( (string) $value ) );
	};

	$editorial = is_array( $config['editorial'] ?? null ) ? $config['editorial'] : array();
	$people_style = almaden_bookster_typst_credits_text_style(
		$styles['people'] ?? array(),
		$fallbacks['family'], $fallbacks['size'], $fallbacks['weight'], $fallbacks['line_height'], $resolve_font, $fallbacks['tracking'] ?? 0, $fallbacks['align'] ?? ''
	);
	$people_gap_px = is_numeric( $styles['people']['item_gap_px'] ?? null ) ? max( 0, min( 80, (float) $styles['people']['item_gap_px'] ) ) : 10;
	$people_align = almaden_bookster_typst_credits_alignment( $people_style['align'] ?? '' );
	$editorial_rows = array();
	if ( '' !== trim( (string) ( $editorial['edition_number'] ?? '' ) ) ) {
		$editorial_rows[] = '#strong[' . $text( max( 0, (int) $editorial['edition_number'] ) . '.ª edición' ) . ']';
	}
	foreach ( array( 'publication_date' => 'Fecha de publicación', 'isbn' => 'ISBN', 'printer' => 'Imprenta' ) as $key => $label ) {
		$value = 'publication_date' === $key ? $format_date( $editorial[ $key ] ?? '' ) : trim( (string) ( $editorial[ $key ] ?? '' ) );
		if ( '' !== $value ) {
			$editorial_rows[] = '#strong[' . $text( $label . ':' ) . '] ' . $text( $value );
		}
	}
	$editorial_style = almaden_bookster_typst_credits_text_style(
		$styles['editorial'] ?? array(),
		$fallbacks['family'], $fallbacks['size'], $fallbacks['weight'], $fallbacks['line_height'], $resolve_font, $fallbacks['tracking'] ?? 0, $fallbacks['align'] ?? ''
	);
	$sections['editorial'] = almaden_bookster_typst_credits_line_block( implode( "\n#linebreak()\n", $editorial_rows ), $editorial_style['align'] );

	$people_rows = array();
	foreach ( (array) ( $config['people'] ?? array() ) as $person ) {
		$parts = array();
		$name  = trim( (string) ( $person['name'] ?? '' ) );
		$role  = trim( (string) ( $person['role'] ?? '' ) );
		$custom_role_title = trim( (string) ( $person['custom_role_title'] ?? '' ) );
		if ( '' !== $name ) {
			$parts[] = '#strong[' . $text( $name ) . ']';
		}
		if ( '' !== $role ) {
			$parts[] = '#emph[' . $text( 'other' === $role && '' !== $custom_role_title ? $custom_role_title : ( $role_labels[ $role ] ?? $role ) ) . ']';
		} elseif ( '' !== $custom_role_title ) {
			$parts[] = '#emph[' . $text( $custom_role_title ) . ']';
		}
		if ( ! empty( $person['show_contact'] ) ) {
			foreach ( array( 'email', 'website' ) as $key ) {
				$value = trim( (string) ( $person[ $key ] ?? '' ) );
				if ( 'website' === $key ) {
					$value = preg_replace( '#^https?://#i', '', $value );
				}
				if ( '' !== $value ) {
					$parts[] = $text( $value );
				}
			}
		}
		if ( $parts ) {
			$people_rows[] = almaden_bookster_typst_credits_line_block(
				'#block(breakable: false)[' . "\n" . implode( "\n#linebreak()\n", $parts ) . "\n]",
				$people_align
			);
		}
	}
	$people_gap_pt = round( $people_gap_px * 0.75, 3 );
	$sections['people'] = implode( $people_gap_pt > 0 ? "\n#v(" . $people_gap_pt . "pt)\n" : "\n\n", $people_rows );

	$collaborator_rows = array();
	$collaborator_section_style = almaden_bookster_typst_credits_text_style(
		$styles['collaborators'] ?? array(),
		$fallbacks['family'], $fallbacks['size'], $fallbacks['weight'], $fallbacks['line_height'], $resolve_font, $fallbacks['tracking'] ?? 0, $fallbacks['align'] ?? ''
	);
	$collaborator_styles = is_array( $config['collaborators_styles'] ?? null ) ? $config['collaborators_styles'] : array();
	$collaborator_title_style = almaden_bookster_typst_credits_text_style(
		$collaborator_styles['title'] ?? array(),
		$collaborator_section_style['family'], $collaborator_section_style['size'], $collaborator_section_style['weight'], $fallbacks['line_height'], $resolve_font,
		$collaborator_section_style['tracking'], $collaborator_section_style['align']
	);
	$collaborator_item_style = almaden_bookster_typst_credits_text_style(
		$collaborator_styles['item'] ?? array(),
		$collaborator_section_style['family'], $collaborator_section_style['size'], $collaborator_section_style['weight'], $fallbacks['line_height'], $resolve_font,
		$collaborator_section_style['tracking'], $collaborator_section_style['align']
	);
	$collaborator_image_width = is_numeric( $collaborator_styles['image_max_width'] ?? null )
		? max( 24, min( 400, (float) $collaborator_styles['image_max_width'] ) ) * 0.75
		: 72;
	$collaborator_title = trim( (string) ( $config['collaborators_title'] ?? '' ) );
	$collaborator_entries = array();
	if ( ! isset( $config['collaborators_visible'] ) || ! empty( $config['collaborators_visible'] ) ) {
		foreach ( (array) ( $config['collaborators'] ?? array() ) as $collaborator ) {
			$parts = array();
			$text_parts = array();
			$image = almaden_bookster_typst_register_upload( $collaborator['logo_url'] ?? '', $assets );
			if ( '' !== $image ) {
				$parts[] = almaden_bookster_typst_credits_line_block( '#box(width: ' . round( $collaborator_image_width, 2 ) . 'pt)[#image("' . almaden_bookster_typst_escape_string( $image ) . '", width: 100%, fit: "contain")]', $collaborator_section_style['align'] );
			}
			foreach ( array( 'name', 'type', 'website', 'text' ) as $key ) {
				$value = trim( (string) ( $collaborator[ $key ] ?? '' ) );
				if ( 'website' === $key ) {
					$value = preg_replace( '#^https?://#i', '', $value );
				}
				if ( '' !== $value ) {
					$text_parts[] = $text( $value );
				}
			}
			if ( $text_parts ) {
				$parts[] = almaden_bookster_typst_credits_styled_block( implode( "\n#linebreak()\n", $text_parts ), $collaborator_item_style );
			}
			if ( $parts ) {
				$collaborator_entries[] = '#block(breakable: false)[' . "\n" . implode( "\n#v(4pt)\n", $parts ) . "\n]";
			}
		}
	}
	if ( $collaborator_entries ) {
		if ( '' !== $collaborator_title ) {
			$collaborator_rows[] = almaden_bookster_typst_credits_styled_block( $text( $collaborator_title ), $collaborator_title_style );
		}
		$collaborator_rows = array_merge( $collaborator_rows, $collaborator_entries );
	}
	$sections['collaborators'] = implode( "\n\n", $collaborator_rows );

	$logo_rows = array();
	foreach ( (array) ( $config['logos'] ?? array() ) as $logo ) {
		$parts = array();
		$image_url = almaden_bookster_typst_resolve_credits_logo_url( $logo, $cover_settings );
		$image = almaden_bookster_typst_register_upload( $image_url, $assets );
		$logo_align = almaden_bookster_typst_credits_alignment( $logo['position'] ?? 'center' );
		if ( '' !== $image ) {
			$size = max( 24, min( 400, (int) ( $logo['size_px'] ?? 120 ) ) ) * 0.75;
			$parts[] = '#align(' . $logo_align . ')[#box(width: ' . round( $size, 2 ) . 'pt)[#image("' . almaden_bookster_typst_escape_string( $image ) . '", width: 100%, fit: "contain")]]';
		}
		if ( almaden_bookster_typst_bool( $logo['show_author_name'] ?? false ) && '' !== trim( (string) $author_label ) ) {
			$author_style = almaden_bookster_typst_credits_text_style(
				array(
					'font_family'   => $logo['author_font_family'] ?? '',
					'font_size'     => $logo['author_font_size'] ?? 16,
					'font_weight'   => $logo['author_font_weight'] ?? $fallbacks['weight'],
					'letter_spacing' => $logo['author_letter_spacing'] ?? 0,
					'text_align'    => $logo['position'] ?? 'center',
				),
				$fallbacks['family'], $fallbacks['size'], $fallbacks['weight'], $fallbacks['line_height'], $resolve_font
			);
			$author = $text( almaden_bookster_typst_transform_title( $author_label, $logo['author_text_transform'] ?? 'none' ) );
			$parts[] = almaden_bookster_typst_credits_styled_block( $author, $author_style );
		}
		if ( $parts ) {
			$logo_rows[] = implode( "\n#v(" . max( 0, (int) ( $logo['author_gap_px'] ?? 10 ) ) * 0.75 . "pt)\n", $parts );
		}
	}
	$sections['logos'] = implode( "\n\n", $logo_rows );

	$legal = is_array( $config['legal'] ?? null ) ? $config['legal'] : array();
	$legal_rows = array();
	if ( '' !== trim( (string) ( $legal['copyright_text'] ?? '' ) ) ) {
		$legal_rows[] = $text( $legal['copyright_text'] );
	}
	if ( '' !== trim( (string) ( $legal['license'] ?? '' ) ) ) {
		$legal_rows[] = $text( $license_labels[ $legal['license'] ] ?? $legal['license'] );
	}
	$legal_style = almaden_bookster_typst_credits_text_style(
		$styles['legal'] ?? array(),
		$fallbacks['family'], $fallbacks['size'], $fallbacks['weight'], $fallbacks['line_height'], $resolve_font, $fallbacks['tracking'] ?? 0, $fallbacks['align'] ?? ''
	);
	$sections['legal'] = almaden_bookster_typst_credits_line_block( implode( "\n#linebreak()\n", $legal_rows ), $legal_style['align'] );

	$renderable_sections = array();
	foreach ( $order as $section_id ) {
		if ( empty( $sections[ $section_id ] ) ) {
			continue;
		}
		$renderable_sections[] = $section_id;
	}
	$output = '';
	foreach ( $renderable_sections as $section_index => $section_id ) {
		$section_style = almaden_bookster_typst_credits_text_style(
			$styles[ $section_id ] ?? array(),
			$fallbacks['family'], $fallbacks['size'], $fallbacks['weight'], $fallbacks['line_height'], $resolve_font, $fallbacks['tracking'] ?? 0, $fallbacks['align'] ?? ''
		);
		$output .= almaden_bookster_typst_credits_styled_block( $sections[ $section_id ], $section_style );
		if ( $section_index < count( $renderable_sections ) - 1 ) {
			$output .= $section_style['separator'] ? "\n#v(6pt)\n#line(length: 100%, stroke: 0.25pt)\n#v(6pt)\n" : "\n#v(8pt)\n";
		}
	}

	return $output;
}

function almaden_bookster_typst_pt_to_unit( $pt, $unit ) {
	$pt = (float) $pt;
	switch ( $unit ) {
		case 'mm':
			return $pt * 25.4 / 72.0;
		case 'cm':
			return $pt * 2.54 / 72.0;
		case 'in':
			return $pt / 72.0;
		case 'pt':
		default:
			return $pt;
	}
}

function almaden_bookster_typst_length_literal( $value, $unit ) {
	return round( (float) $value, 4 ) . $unit;
}

function almaden_bookster_typst_running_element_has_content( $type, $custom = '' ) {
	$type = almaden_bookster_typst_normalize_header_footer_type( $type );
	if ( 'blank' === $type ) {
		return false;
	}
	if ( 'custom' === $type ) {
		return '' !== trim( (string) $custom );
	}
	return true;
}

/**
 * Register an image only when it resolves inside the WordPress uploads directory.
 */
function almaden_bookster_typst_register_upload( $url, &$assets ) {
	if ( ! function_exists( 'wp_upload_dir' ) || '' === trim( (string) $url ) ) {
		return '';
	}
	$uploads = wp_upload_dir();
	$baseurl = rtrim( (string) $uploads['baseurl'], '/' );
	$basedir = realpath( (string) $uploads['basedir'] );
	if ( ! $basedir || 0 !== strpos( (string) $url, $baseurl . '/' ) ) {
		return '';
	}
	$relative = rawurldecode( substr( (string) $url, strlen( $baseurl ) + 1 ) );
	$path     = realpath( $basedir . DIRECTORY_SEPARATOR . $relative );
	if ( ! $path || 0 !== strpos( $path, $basedir . DIRECTORY_SEPARATOR ) || ! is_file( $path ) ) {
		return '';
	}
	$extension = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
	if ( ! in_array( $extension, array( 'png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'pdf' ), true ) ) {
		return '';
	}
	$name            = hash( 'sha256', $path . '|' . filemtime( $path ) ) . '.' . $extension;
	$assets[ $name ] = $path;
	return 'assets/' . $name;
}

/**
 * Return Typst source plus semantic source text used by the integrity gate.
 */
function almaden_bookster_build_typst_document( $payload ) {
	$settings = isset( $payload['settings'] ) && is_array( $payload['settings'] ) ? $payload['settings'] : array();
	$chapters = isset( $payload['chapters'] ) && is_array( $payload['chapters'] ) ? $payload['chapters'] : array();
	$page_template_context = almaden_bookster_typst_page_template_context( $settings );
	$unit     = isset( $settings['unit'] ) && in_array( $settings['unit'], array( 'mm', 'cm', 'in', 'pt' ), true )
		? $settings['unit'] : 'cm';

	$width       = almaden_bookster_typst_number( $settings, 'page_width', 21, 5, 100 );
	$height      = almaden_bookster_typst_number( $settings, 'page_height', 29.7, 5, 100 );
	$margin_top  = almaden_bookster_typst_number( $settings, 'margin_top', 2.5, 0, 30 );
	$margin_bot  = almaden_bookster_typst_number( $settings, 'margin_bottom', 2.5, 0, 30 );
	$margin_inside_odd = almaden_bookster_typst_number(
		$settings,
		'margin_left_odd',
		almaden_bookster_typst_number( $settings, 'margin_left', 2, 0, 30 ),
		0,
		30
	);
	$margin_outside_odd = almaden_bookster_typst_number(
		$settings,
		'margin_right_odd',
		almaden_bookster_typst_number( $settings, 'margin_right', 2, 0, 30 ),
		0,
		30
	);
	$margin_inside_even = almaden_bookster_typst_number( $settings, 'margin_right_even', $margin_inside_odd, 0, 30 );
	$margin_outside_even = almaden_bookster_typst_number( $settings, 'margin_left_even', $margin_outside_odd, 0, 30 );
	$padding_top    = almaden_bookster_typst_number( $settings, 'padding_top', 0, 0, 30 );
	$padding_bottom = almaden_bookster_typst_number( $settings, 'padding_bottom', 0, 0, 30 );
	$padding_left   = almaden_bookster_typst_number( $settings, 'padding_left', 0, 0, 30 );
	$padding_right  = almaden_bookster_typst_number( $settings, 'padding_right', 0, 0, 30 );
	$bleed          = almaden_bookster_typst_number( $settings, 'bleeding', 0, 0, 10 );
	$margin_top    += $padding_top;
	$margin_bot    += $padding_bottom;
	$margin_inside  = round( ( $margin_inside_odd + $padding_left + $margin_inside_even + $padding_right ) / 2, 4 );
	$margin_outside = round( ( $margin_outside_odd + $padding_right + $margin_outside_even + $padding_left ) / 2, 4 );
	$font_size     = almaden_bookster_typst_number( $settings, 'font_size_content', 11.5, 5, 72 );
	$font_weight   = almaden_bookster_typst_font_weight( $settings['font_weight_content'] ?? 'normal' );
	$font_family   = almaden_bookster_typst_font_family( $settings['font_family_content'] ?? 'Merriweather' );
	$line_height   = almaden_bookster_typst_number( $settings, 'line_height_content', 1.65, 0.8, 4 );
	$paragraph_gap = almaden_bookster_typst_number( $settings, 'content_paragraph_spacing', 14, 0, 200 );
	$paragraph_indent = almaden_bookster_typst_number( $settings, 'content_paragraph_indent', 0, 0, 200 );
	$title_size  = almaden_bookster_typst_number( $settings, 'chapter_title_font_size', 24, 8, 100 );
	$title_gap   = almaden_bookster_typst_number( $settings, 'chapter_title_padding_bottom', 1.5, 0, 20 );
	$title_font_family = almaden_bookster_typst_font_family( $settings['chapter_title_font_family'] ?? $font_family );
	$title_font_weight = almaden_bookster_typst_font_weight( $settings['chapter_title_font_weight'] ?? 'bold' );
	$title_font_style   = isset( $settings['chapter_title_font_style'] ) ? strtolower( trim( (string) $settings['chapter_title_font_style'] ) ) : 'normal';
	if ( ! in_array( $title_font_style, array( 'normal', 'italic', 'oblique' ), true ) ) {
		$title_font_style = 'normal';
	}
	$title_letter_spacing = almaden_bookster_typst_number( $settings, 'chapter_title_letter_spacing', 0, -20, 20 );
	$lang        = isset( $settings['book_language'] ) ? preg_replace( '/[^a-zA-Z-]/', '', $settings['book_language'] ) : 'es';
	$text_align  = isset( $settings['content_text_align'] ) &&
		in_array( $settings['content_text_align'], array( 'left', 'center', 'right', 'justify' ), true )
		? $settings['content_text_align'] : 'justify';
	$last_align  = isset( $settings['content_text_align_last'] ) &&
		in_array( $settings['content_text_align_last'], array( 'left', 'center', 'right' ), true )
		? $settings['content_text_align_last'] : ( 'justify' === $text_align ? 'left' : $text_align );
	$justify     = 'justify' === $text_align;
	$hyphenate   = ! isset( $settings['content_hyphenation'] ) || '0' !== (string) $settings['content_hyphenation'];
	$hyphenation_exceptions = isset( $settings['content_hyphenation_exceptions'] )
		? preg_split( '/[,;\n]+/u', (string) $settings['content_hyphenation_exceptions'], -1, PREG_SPLIT_NO_EMPTY )
		: array();
	$title_align = isset( $settings['chapter_title_align'] ) &&
		in_array( $settings['chapter_title_align'], array( 'left', 'center', 'right' ), true )
		? $settings['chapter_title_align'] : 'center';
	$title_transform = isset( $settings['chapter_title_text_transform'] ) ? $settings['chapter_title_text_transform'] : 'none';
	$leading_em = max( 0, round( $line_height - 1, 4 ) );
	$font = almaden_bookster_typst_resolve_font( $font_family, $font_weight );
	$font_error = function_exists( 'is_wp_error' ) && is_wp_error( $font ) ? $font : null;
	if ( ! $font_error ) {
		$font_family = $font['family'];
	}
	$title_font = almaden_bookster_typst_resolve_font( $title_font_family, $title_font_weight );
	$title_font_error = function_exists( 'is_wp_error' ) && is_wp_error( $title_font ) ? $title_font : null;
	if ( ! $title_font_error ) {
		$title_font_family = $title_font['family'];
	}
	$heading1_font_family = almaden_bookster_typst_font_family( $settings['font_family_h1'] ?? $font_family );
	$heading2_font_family = almaden_bookster_typst_font_family( $settings['font_family_h2'] ?? $font_family );
	$heading3_font_family = almaden_bookster_typst_font_family( $settings['font_family_h3'] ?? $font_family );
	$heading1_font_size   = almaden_bookster_typst_number( $settings, 'font_size_h1', 24, 5, 100 );
	$heading2_font_size   = almaden_bookster_typst_number( $settings, 'font_size_h2', 18, 5, 100 );
	$heading3_font_size   = almaden_bookster_typst_number( $settings, 'font_size_h3', 14, 5, 100 );
	$heading1_font_weight = almaden_bookster_typst_font_weight( $settings['font_weight_h1'] ?? 'bold' );
	$heading2_font_weight = almaden_bookster_typst_font_weight( $settings['font_weight_h2'] ?? 'bold' );
	$heading3_font_weight = almaden_bookster_typst_font_weight( $settings['font_weight_h3'] ?? 'bold' );
	$heading1_font_style   = isset( $settings['font_style_h1'] ) ? strtolower( trim( (string) $settings['font_style_h1'] ) ) : 'normal';
	$heading2_font_style   = isset( $settings['font_style_h2'] ) ? strtolower( trim( (string) $settings['font_style_h2'] ) ) : 'normal';
	$heading3_font_style   = isset( $settings['font_style_h3'] ) ? strtolower( trim( (string) $settings['font_style_h3'] ) ) : 'normal';
	if ( ! in_array( $heading1_font_style, array( 'normal', 'italic', 'oblique' ), true ) ) {
		$heading1_font_style = 'normal';
	}
	if ( ! in_array( $heading2_font_style, array( 'normal', 'italic', 'oblique' ), true ) ) {
		$heading2_font_style = 'normal';
	}
	if ( ! in_array( $heading3_font_style, array( 'normal', 'italic', 'oblique' ), true ) ) {
		$heading3_font_style = 'normal';
	}
	$heading1_font = almaden_bookster_typst_resolve_font( $heading1_font_family, $heading1_font_weight );
	$heading2_font = almaden_bookster_typst_resolve_font( $heading2_font_family, $heading2_font_weight );
	$heading3_font = almaden_bookster_typst_resolve_font( $heading3_font_family, $heading3_font_weight );
	$heading1_font_error = function_exists( 'is_wp_error' ) && is_wp_error( $heading1_font ) ? $heading1_font : null;
	$heading2_font_error = function_exists( 'is_wp_error' ) && is_wp_error( $heading2_font ) ? $heading2_font : null;
	$heading3_font_error = function_exists( 'is_wp_error' ) && is_wp_error( $heading3_font ) ? $heading3_font : null;
	if ( ! $heading1_font_error ) {
		$heading1_font_family = $heading1_font['family'];
	}
	if ( ! $heading2_font_error ) {
		$heading2_font_family = $heading2_font['family'];
	}
	if ( ! $heading3_font_error ) {
		$heading3_font_family = $heading3_font['family'];
	}

	$book_title = isset( $payload['title'] ) ? (string) $payload['title'] : '';
	$header_font_family = almaden_bookster_typst_font_family( $settings['header_font_family'] ?? 'Merriweather' );
	$footer_font_family = almaden_bookster_typst_font_family( $settings['footer_font_family'] ?? 'Merriweather' );
	$header_font_size = almaden_bookster_typst_number( $settings, 'header_font_size', 8.5, 4, 48 );
	$footer_font_size = almaden_bookster_typst_number( $settings, 'footer_font_size', 9, 4, 48 );
	$header_font_weight = almaden_bookster_typst_font_weight( $settings['header_font_weight'] ?? 'normal' );
	$footer_font_weight = almaden_bookster_typst_font_weight( $settings['footer_font_weight'] ?? 'normal' );
	$header_font_style = isset( $settings['header_font_style'] ) ? $settings['header_font_style'] : 'normal';
	$footer_font_style = isset( $settings['footer_font_style'] ) ? $settings['footer_font_style'] : 'normal';
	$header_text_transform = isset( $settings['header_text_transform'] ) ? $settings['header_text_transform'] : 'none';
	$footer_text_transform = isset( $settings['footer_text_transform'] ) ? $settings['footer_text_transform'] : 'none';
	$header_hyphenate = almaden_bookster_typst_bool( $settings['header_hyphenate'] ?? false );
	$header_letter_spacing = almaden_bookster_typst_number( $settings, 'header_letter_spacing', 0.1, -20, 20 );
	$footer_letter_spacing = almaden_bookster_typst_number( $settings, 'footer_letter_spacing', 0, -20, 20 );
	$header_align = isset( $settings['header_align'] ) ? $settings['header_align'] : 'center';
	$footer_align = isset( $settings['footer_align'] ) ? $settings['footer_align'] : 'center';
	$header_margin_top = almaden_bookster_typst_number( $settings, 'header_margin_top', 1.0, 0, 20 );
	$header_margin_bottom = almaden_bookster_typst_number( $settings, 'header_margin_bottom', 0.5, 0, 20 );
	$footer_margin_top = almaden_bookster_typst_number( $settings, 'footer_margin_top', 0.5, 0, 20 );
	$footer_margin_bottom = almaden_bookster_typst_number( $settings, 'footer_margin_bottom', 1.0, 0, 20 );
	$header_font = almaden_bookster_typst_resolve_font( $header_font_family, $header_font_weight );
	$footer_font = almaden_bookster_typst_resolve_font( $footer_font_family, $footer_font_weight );
	$header_font_error = function_exists( 'is_wp_error' ) && is_wp_error( $header_font ) ? $header_font : null;
	$footer_font_error = function_exists( 'is_wp_error' ) && is_wp_error( $footer_font ) ? $footer_font : null;
	if ( ! $header_font_error ) {
		$header_font_family = $header_font['family'];
	}
	if ( ! $footer_font_error ) {
		$footer_font_family = $footer_font['family'];
	}
	$footnote_font_family = almaden_bookster_typst_font_family( $settings['footnote_font_family'] ?? $font_family, $font_family );
	$footnote_font_size = almaden_bookster_typst_number( $settings, 'footnote_font_size', 8.5, 4, 48 );
	$footnote_font_weight = almaden_bookster_typst_font_weight( $settings['footnote_font_weight'] ?? 'normal' );
	$footnote_align = almaden_bookster_typst_footnote_alignment( $settings['footnote_align'] ?? 'left' );
	$footnote_line_height = almaden_bookster_typst_footnote_leading_pt( $settings['footnote_line_height'] ?? null, $footnote_font_size, 11.5 );
	$footnote_entry_spacing = almaden_bookster_typst_footnote_spacing_pt( $settings['footnote_entry_spacing'] ?? null, 6 );
	$footnote_letter_spacing = almaden_bookster_typst_number( $settings, 'footnote_letter_spacing', 0, -20, 20 );
	$footnote_hyphenate = almaden_bookster_typst_bool( $settings['footnote_hyphenate'] ?? false );
	$footnote_call_scale = isset( $settings['footnote_call_scale'] ) && is_numeric( $settings['footnote_call_scale'] ) ? max( 0.1, min( 2.0, (float) $settings['footnote_call_scale'] ) ) : 0.65;
	$footnote_call_raise = isset( $settings['footnote_call_raise'] ) && is_numeric( $settings['footnote_call_raise'] ) ? max( 0, min( 2.0, (float) $settings['footnote_call_raise'] ) ) : 0.18;
	$footnote_padding_top = isset( $settings['footnote_padding_top'] ) && is_numeric( $settings['footnote_padding_top'] ) ? max( 0, min( 10, (float) $settings['footnote_padding_top'] ) ) : 0.15;
	$footnote_padding_bottom = isset( $settings['footnote_padding_bottom'] ) && is_numeric( $settings['footnote_padding_bottom'] ) ? max( 0, min( 10, (float) $settings['footnote_padding_bottom'] ) ) : 0.15;
	$footnote_padding_left = isset( $settings['footnote_padding_left'] ) && is_numeric( $settings['footnote_padding_left'] ) ? max( 0, min( 10, (float) $settings['footnote_padding_left'] ) ) : 0;
	$footnote_padding_right = isset( $settings['footnote_padding_right'] ) && is_numeric( $settings['footnote_padding_right'] ) ? max( 0, min( 10, (float) $settings['footnote_padding_right'] ) ) : 0;
	$footnote_separator_show = almaden_bookster_typst_bool( $settings['footnote_separator_show'] ?? false );
	$footnote_separator_align = isset( $settings['footnote_separator_align'] ) && in_array( $settings['footnote_separator_align'], array( 'left', 'center', 'right' ), true ) ? $settings['footnote_separator_align'] : 'left';
	$footnote_separator_width = isset( $settings['footnote_separator_width'] ) && in_array( (string) $settings['footnote_separator_width'], array( '100', '75', '50', '25' ), true ) ? (string) $settings['footnote_separator_width'] : '100';
	$footnote_separator_thickness = isset( $settings['footnote_separator_thickness'] ) && is_numeric( $settings['footnote_separator_thickness'] ) ? max( 0.05, min( 5, (float) $settings['footnote_separator_thickness'] ) ) : 0.25;
	$footnote_separator_margin_bottom = isset( $settings['footnote_separator_margin_bottom'] ) && is_numeric( $settings['footnote_separator_margin_bottom'] ) ? max( 0, min( 10, (float) $settings['footnote_separator_margin_bottom'] ) ) : 0.15;
	$page_columns_enabled = almaden_bookster_typst_bool( $settings['page_columns_enabled'] ?? false );
	$page_columns_count = isset( $settings['page_columns_count'] ) && is_numeric( $settings['page_columns_count'] )
		? max( 1, min( 4, (int) $settings['page_columns_count'] ) )
		: 2;
	$page_columns_gap = isset( $settings['page_columns_gap'] ) && is_numeric( $settings['page_columns_gap'] )
		? max( 0, min( 20, (float) $settings['page_columns_gap'] ) )
		: 0.8;
	$footnote_font = almaden_bookster_typst_resolve_font( $footnote_font_family, $footnote_font_weight );
	$footnote_font_error = function_exists( 'is_wp_error' ) && is_wp_error( $footnote_font ) ? $footnote_font : null;
	$footnote_font_assets = array();
	if ( ! $footnote_font_error ) {
		$footnote_font_family = $footnote_font['family'];
		$footnote_font_assets = array_values( array_unique( (array) ( $footnote_font['files'] ?? array() ) ) );
	} else {
		$footnote_font_family = $font_family;
	}
	$footnote_mode = almaden_bookster_typst_footnote_mode( $settings );
	$footnote_chapter_title = almaden_bookster_typst_footnote_title( $settings, 'chapter' );
	$footnote_book_title = almaden_bookster_typst_footnote_title( $settings, 'book' );
	$first_page_header_show = almaden_bookster_typst_bool( $settings['first_page_header_show'] ?? true );
	$first_page_footer_show = almaden_bookster_typst_bool( $settings['first_page_footer_show'] ?? true );
	$first_page_header_type = almaden_bookster_typst_normalize_header_footer_type( $settings['first_page_header_type'] ?? 'blank' );
	$first_page_footer_type = almaden_bookster_typst_normalize_header_footer_type( $settings['first_page_footer_type'] ?? 'page_number' );
	$first_page_header_custom = isset( $settings['first_page_header_custom'] ) ? (string) $settings['first_page_header_custom'] : '';
	$first_page_footer_custom = isset( $settings['first_page_footer_custom'] ) ? (string) $settings['first_page_footer_custom'] : '';
	$header_even_type = almaden_bookster_typst_normalize_header_footer_type( $settings['header_even_type'] ?? 'book_title' );
	$header_odd_type = almaden_bookster_typst_normalize_header_footer_type( $settings['header_odd_type'] ?? 'chapter_title' );
	$footer_even_type = almaden_bookster_typst_normalize_header_footer_type( $settings['footer_even_type'] ?? 'page_number' );
	$footer_odd_type = almaden_bookster_typst_normalize_header_footer_type( $settings['footer_odd_type'] ?? 'page_number' );
	$header_even_custom = isset( $settings['header_even_custom'] ) ? (string) $settings['header_even_custom'] : '';
	$header_odd_custom = isset( $settings['header_odd_custom'] ) ? (string) $settings['header_odd_custom'] : '';
	$footer_even_custom = isset( $settings['footer_even_custom'] ) ? (string) $settings['footer_even_custom'] : '';
	$footer_odd_custom = isset( $settings['footer_odd_custom'] ) ? (string) $settings['footer_odd_custom'] : '';

	$header_has_content = (
		( $first_page_header_show && almaden_bookster_typst_running_element_has_content( $first_page_header_type, $first_page_header_custom ) ) ||
		almaden_bookster_typst_running_element_has_content( $header_even_type, $header_even_custom ) ||
		almaden_bookster_typst_running_element_has_content( $header_odd_type, $header_odd_custom )
	);
	$footer_has_content = (
		( $first_page_footer_show && almaden_bookster_typst_running_element_has_content( $first_page_footer_type, $first_page_footer_custom ) ) ||
		almaden_bookster_typst_running_element_has_content( $footer_even_type, $footer_even_custom ) ||
		almaden_bookster_typst_running_element_has_content( $footer_odd_type, $footer_odd_custom )
	);

	$header_reserve = $header_has_content
		? round( $header_margin_top + almaden_bookster_typst_pt_to_unit( $header_font_size, $unit ) + $header_margin_bottom, 4 )
		: 0;
	$footer_reserve = $footer_has_content
		? round( $footer_margin_top + almaden_bookster_typst_pt_to_unit( $footer_font_size, $unit ) + $footer_margin_bottom, 4 )
		: 0;

	$source  = '#let almaden-current-chapter-title() = context {' . "\n";
	$source .= '  let current = here().page()' . "\n";
	$source .= '  let marks = query(<almaden-chapter-start>).filter(mark => mark.location().page() <= current)' . "\n";
	$source .= '  if marks.len() > 0 { marks.last().value } else { "" }' . "\n";
	$source .= '}' . "\n\n";

	$source .= '#let almaden-resolve-running-element(book_title, first_page_show, first_page_type, first_page_custom, even_type, even_custom, odd_type, odd_custom, text_transform, running_area) = context {' . "\n";
	$source .= '  let current = here().page()' . "\n";
	$source .= '  let chapter_marks = query(<almaden-chapter-start>).filter(mark => mark.location().page() <= current)' . "\n";
	$source .= '  let chapter_start = if chapter_marks.len() > 0 { chapter_marks.last().location().page() } else { 0 }' . "\n";
	$source .= '  let is_first_chapter_page = chapter_marks.filter(mark => mark.location().page() == current).len() > 0' . "\n";
	$source .= '  let is_intentional_blank = query(<almaden-intentional-blank>).filter(mark => mark.location().page() == current).len() > 0' . "\n";
	$source .= '  let suppress_marker = if running_area == "header" { <almaden-hide-header> } else { <almaden-hide-footer> }' . "\n";
	$source .= '  let chapter_suppresses = query(suppress_marker).filter(mark => mark.location().page() >= chapter_start and mark.location().page() <= current).len() > 0' . "\n";
	$source .= '  let is_even = calc.even(current)' . "\n";
	$source .= '  let kind = if is_intentional_blank or chapter_suppresses {' . "\n";
	$source .= '    "blank"' . "\n";
	$source .= '  } else if is_first_chapter_page {' . "\n";
	$source .= '    if first_page_show { first_page_type } else { "blank" }' . "\n";
	$source .= '  } else if is_even {' . "\n";
	$source .= '    even_type' . "\n";
	$source .= '  } else {' . "\n";
	$source .= '    odd_type' . "\n";
	$source .= '  }' . "\n";
	$source .= '  let custom = if is_first_chapter_page { first_page_custom } else if is_even { even_custom } else { odd_custom }' . "\n";
	$source .= '  let raw = if kind == "book_title" {' . "\n";
	$source .= '    book_title' . "\n";
	$source .= '  } else if kind == "chapter_title" {' . "\n";
	$source .= '    almaden-current-chapter-title()' . "\n";
	$source .= '  } else if kind == "page_number" {' . "\n";
	$source .= '    counter(page).display()' . "\n";
	$source .= '  } else if kind == "author" {' . "\n";
	$source .= '    "Autor"' . "\n";
	$source .= '  } else if kind == "custom" {' . "\n";
	$source .= '    custom' . "\n";
	$source .= '  } else {' . "\n";
	$source .= '    ""' . "\n";
	$source .= '  }' . "\n";
	$source .= '  let value = if text_transform == "uppercase" { upper(raw) } else if text_transform == "lowercase" { lower(raw) } else { raw }' . "\n";
	$source .= '  if value != "" { value } else { "" }' . "\n";
	$source .= '}' . "\n\n";

	$source .= '#let almaden-running-area(content, box_align, font_family, font_size, font_weight, font_style, letter_spacing, margin_top, margin_bottom, hyphenate) = context {' . "\n";
	$source .= '  if content != "" {' . "\n";
	$source .= '    let current = here().page()' . "\n";
	$source .= '    let is_even = calc.even(current)' . "\n";
	$source .= '    let resolved_align = if box_align == "outer" {' . "\n";
	$source .= '      if is_even { "left" } else { "right" }' . "\n";
	$source .= '    } else if box_align == "inner" {' . "\n";
	$source .= '      if is_even { "right" } else { "left" }' . "\n";
	$source .= '    } else {' . "\n";
	$source .= '      box_align' . "\n";
	$source .= '    }' . "\n";
	$source .= '    box(width: 100%, inset: (top: margin_top, bottom: margin_bottom))[' . "\n";
	$source .= '      #set text(font: font_family, size: font_size, weight: font_weight, style: font_style, tracking: letter_spacing, hyphenate: hyphenate)' . "\n";
	$source .= '      #set par(justify: false, leading: 1em, spacing: 0pt)' . "\n";
	$source .= '      #if resolved_align == "left" {' . "\n";
	$source .= '        align(left)[#content]' . "\n";
	$source .= '      } else if resolved_align == "right" {' . "\n";
	$source .= '        align(right)[#content]' . "\n";
	$source .= '      } else {' . "\n";
	$source .= '        align(center)[#content]' . "\n";
	$source .= '      }' . "\n";
	$source .= '    ]' . "\n";
	$source .= '  }' . "\n";
	$source .= '}' . "\n\n";

	$source .= '#set document(title: "' . almaden_bookster_typst_escape_string( $payload['title'] ?? '' ) . '")' . "\n";
	$source .= '#set page(width: ' . $width . $unit . ', height: ' . $height . $unit .
		', margin: (top: ' . ( $margin_top + $header_reserve ) . $unit . ', bottom: ' . ( $margin_bot + $footer_reserve ) . $unit .
		', inside: ' . $margin_inside . $unit . ', outside: ' . $margin_outside . $unit . '),' .
		' binding: left, bleed: ' . $bleed . $unit . ',' .
		' header: context {' . "\n" .
		'  let running = almaden-resolve-running-element("' . almaden_bookster_typst_escape_string( $book_title ) . '", ' . ( $first_page_header_show ? 'true' : 'false' ) . ', "' . almaden_bookster_typst_escape_string( $first_page_header_type ) . '", "' . almaden_bookster_typst_escape_string( $first_page_header_custom ) . '", "' . almaden_bookster_typst_escape_string( $header_even_type ) . '", "' . almaden_bookster_typst_escape_string( $header_even_custom ) . '", "' . almaden_bookster_typst_escape_string( $header_odd_type ) . '", "' . almaden_bookster_typst_escape_string( $header_odd_custom ) . '", "' . almaden_bookster_typst_escape_string( $header_text_transform ) . '", "header")' . "\n" .
		'  almaden-running-area(running, "' . almaden_bookster_typst_escape_string( $header_align ) . '", "' . $header_font_family . '", ' . $header_font_size . 'pt, ' . $header_font_weight . ', "' . almaden_bookster_typst_escape_string( $header_font_style ) . '", ' . $header_letter_spacing . 'pt, ' . $header_margin_top . $unit . ', ' . $header_margin_bottom . $unit . ', ' . ( $header_hyphenate ? 'true' : 'false' ) . ')' . "\n" .
		'},' . "\n" .
		' footer: context {' . "\n" .
		'  let running = almaden-resolve-running-element("' . almaden_bookster_typst_escape_string( $book_title ) . '", ' . ( $first_page_footer_show ? 'true' : 'false' ) . ', "' . almaden_bookster_typst_escape_string( $first_page_footer_type ) . '", "' . almaden_bookster_typst_escape_string( $first_page_footer_custom ) . '", "' . almaden_bookster_typst_escape_string( $footer_even_type ) . '", "' . almaden_bookster_typst_escape_string( $footer_even_custom ) . '", "' . almaden_bookster_typst_escape_string( $footer_odd_type ) . '", "' . almaden_bookster_typst_escape_string( $footer_odd_custom ) . '", "' . almaden_bookster_typst_escape_string( $footer_text_transform ) . '", "footer")' . "\n" .
		'  almaden-running-area(running, "' . almaden_bookster_typst_escape_string( $footer_align ) . '", "' . $footer_font_family . '", ' . $footer_font_size . 'pt, ' . $footer_font_weight . ', "' . almaden_bookster_typst_escape_string( $footer_font_style ) . '", ' . $footer_letter_spacing . 'pt, ' . $footer_margin_top . $unit . ', ' . $footer_margin_bottom . $unit . ', false)' . "\n" .
		'})' . "\n";
	$source .= '#set text(font: "' . almaden_bookster_typst_escape_string( $font_family ) . '", size: ' .
		$font_size . 'pt, weight: ' . $font_weight . ', lang: "' .
		almaden_bookster_typst_escape_string( $lang ?: 'es' ) . '", hyphenate: ' . ( $hyphenate ? 'true' : 'false' ) .
		', top-edge: 0.8em, bottom-edge: -0.2em)' . "\n";
	$source .= '#set par(justify: ' . ( $justify ? 'true' : 'false' ) . ', leading: ' . $leading_em .
		'em, spacing: ' . $paragraph_gap . 'pt, first-line-indent: ' . $paragraph_indent . 'pt)' . "\n";
	$source .= '#set heading(numbering: none)' . "\n\n";
	$source .= '#set super(typographic: false, size: ' . round( max( 0.1, $footnote_call_scale ), 3 ) . 'em, baseline: -' . round( max( 0, $footnote_call_raise ), 3 ) . 'em)' . "\n";
	if ( 'page' === $footnote_mode ) {
		$source .= almaden_bookster_typst_render_page_footnote_rules(
			array(
				'font_family'             => $footnote_font_family,
				'font_size'               => $footnote_font_size,
				'font_weight'             => $footnote_font_weight,
				'align'                   => $footnote_align,
				'line_height'             => $footnote_line_height,
				'letter_spacing'          => $footnote_letter_spacing,
				'hyphenate'               => $footnote_hyphenate,
				'padding_top'             => $footnote_padding_top,
				'padding_bottom'          => $footnote_padding_bottom,
				'padding_left'            => $footnote_padding_left,
				'padding_right'           => $footnote_padding_right,
				'separator_show'          => $footnote_separator_show,
				'separator_align'         => $footnote_separator_align,
				'separator_width'         => $footnote_separator_width,
				'separator_thickness'     => $footnote_separator_thickness,
				'separator_margin_bottom' => $footnote_separator_margin_bottom,
			)
		);
	}

	$plain_parts = array();
	$plain_extras = array();
	$assets       = array();
	$credits_font_assets = array();
	$credits_font_error  = null;
	$credits_font_cache  = array();
	$resolve_credits_font = static function ( $family, $weight ) use ( &$credits_font_assets, &$credits_font_error, &$credits_font_cache, $font_family ) {
		$family = almaden_bookster_typst_font_family( $family, $font_family );
		$key    = strtolower( $family ) . '|' . (int) $weight;
		if ( isset( $credits_font_cache[ $key ] ) ) {
			return $credits_font_cache[ $key ];
		}
		$resolved = almaden_bookster_typst_resolve_font( $family, $weight );
		if ( function_exists( 'is_wp_error' ) && is_wp_error( $resolved ) ) {
			if ( null === $credits_font_error ) {
				$credits_font_error = $resolved;
			}
			$credits_font_cache[ $key ] = $font_family;
			return $font_family;
		}
		$credits_font_assets = array_merge( $credits_font_assets, (array) ( $resolved['files'] ?? array() ) );
		$credits_font_cache[ $key ] = $resolved['family'];
		return $resolved['family'];
	};
	$toc_font_assets = array();
	$toc_font_error  = null;
	$toc_font_cache  = array();
	$resolve_toc_font = static function ( $family, $weight ) use ( &$toc_font_assets, &$toc_font_error, &$toc_font_cache, $font_family ) {
		$family = almaden_bookster_typst_font_family( $family, $font_family );
		$key    = strtolower( $family ) . '|' . (int) $weight;
		if ( isset( $toc_font_cache[ $key ] ) ) {
			return $toc_font_cache[ $key ];
		}
		$resolved = almaden_bookster_typst_resolve_font( $family, $weight );
		if ( function_exists( 'is_wp_error' ) && is_wp_error( $resolved ) ) {
			if ( null === $toc_font_error ) {
				$toc_font_error = $resolved;
			}
			$toc_font_cache[ $key ] = $font_family;
			return $font_family;
		}
		$toc_font_assets = array_merge( $toc_font_assets, (array) ( $resolved['files'] ?? array() ) );
		$toc_font_cache[ $key ] = $resolved['family'];
		return $resolved['family'];
	};
	$inline_font_assets = array();
	$inline_font_error  = null;
	$inline_font_cache  = array();
	$resolve_inline_font = static function ( $family ) use ( &$inline_font_assets, &$inline_font_error, &$inline_font_cache ) {
		$family = almaden_bookster_typst_font_family( $family, '' );
		if ( '' === $family ) {
			return '';
		}
		$key = strtolower( $family );
		if ( isset( $inline_font_cache[ $key ] ) ) {
			return $inline_font_cache[ $key ];
		}
		$resolved = almaden_bookster_typst_resolve_font( $family, 400 );
		if ( is_wp_error( $resolved ) ) {
			$inline_font_error = $resolved;
			return '';
		}
		$inline_font_assets = array_merge( $inline_font_assets, (array) ( $resolved['files'] ?? array() ) );
		$inline_font_cache[ $key ] = $resolved['family'];
		return $inline_font_cache[ $key ];
	};
	$rendered     = 0;
	$numbered_chapter_index = 0;
	$book_reference_groups = array();
	foreach ( $chapters as $chapter_index => $chapter ) {
		if ( ! is_array( $chapter ) ) {
			continue;
		}
		$title   = isset( $chapter['title'] ) ? (string) $chapter['title'] : '';
		$content = isset( $chapter['content'] ) ? (string) $chapter['content'] : '';
		foreach ( almaden_bookster_typst_inline_font_families( $content ) as $inline_font_family ) {
			$resolve_inline_font( $inline_font_family );
		}
		$is_toc  = isset( $chapter['is_toc'] ) && '1' === (string) $chapter['is_toc'];
		$is_credits = almaden_bookster_typst_is_credits_chapter( $chapter );
		if ( $is_toc ) {
			$toc_title_text = almaden_bookster_typst_toc_title_text( $chapter );
			if ( '' !== trim( $toc_title_text ) ) {
				$title = $toc_title_text;
			}
		}
		// Credits are global book metadata. Prefer the canonical settings payload so
		// a stale chapter snapshot cannot override the editor's latest saved values.
		$credits_config = isset( $settings['credits_config'] ) && is_array( $settings['credits_config'] )
			? $settings['credits_config']
			: ( isset( $chapter['credits_config'] ) && is_array( $chapter['credits_config'] ) ? $chapter['credits_config'] : array() );
		$blank_before = $is_credits ? almaden_bookster_typst_credits_blank_count( $settings, 'before' ) : 0;
		$blank_after  = $is_credits ? almaden_bookster_typst_credits_blank_count( $settings, 'after' ) : 0;
		$chapter_hide_header = almaden_bookster_typst_bool( $chapter['hide_header'] ?? false ) || almaden_bookster_typst_bool( $chapter['hide_all_headers_footers'] ?? false );
		$chapter_hide_footer = almaden_bookster_typst_bool( $chapter['hide_footer'] ?? false ) || almaden_bookster_typst_bool( $chapter['hide_all_headers_footers'] ?? false );
		if ( $rendered > 0 ) {
			$source .= "\n#pagebreak()\n\n";
		}
		for ( $blank_index = 0; $blank_index < $blank_before; ++$blank_index ) {
			$source .= '#metadata("credits-before") <almaden-intentional-blank>' . "\n";
			$source .= "#pagebreak()\n\n";
		}
		++$rendered;

		$chapter_top_margin = $is_credits ? $credit_margin_top : $margin_top;
		$chapter_bottom_margin = $is_credits ? $credit_margin_bottom : $margin_bot;
		$chapter_header_reserve = $chapter_hide_header ? 0 : $header_reserve;
		$chapter_footer_reserve = $chapter_hide_footer ? 0 : $footer_reserve;
		$source .= '#set page(margin: (top: ' . ( $chapter_top_margin + $chapter_header_reserve ) . $unit . ', bottom: ' . ( $chapter_bottom_margin + $chapter_footer_reserve ) . $unit . ', inside: ' . $margin_inside . $unit . ', outside: ' . $margin_outside . $unit . '))' . "\n";

		$chapter_label_id = preg_replace( '/[^0-9A-Za-z_-]/', '', (string) ( $chapter['id'] ?? (string) ( $chapter_index + 1 ) ) );
		$source .= '#metadata("' . almaden_bookster_typst_escape_string( $title ) . '") <almaden-chapter-start>' . "\n";
		if ( '' !== $chapter_label_id ) {
			$source .= '#metadata("' . almaden_bookster_typst_escape_string( $title ) . '") <almaden-chapter-start-' . almaden_bookster_typst_escape_string( $chapter_label_id ) . '>' . "\n";
		}
		if ( $chapter_hide_header ) {
			$source .= '#metadata("' . almaden_bookster_typst_escape_string( $title ) . '") <almaden-hide-header>' . "\n";
		}
		if ( $chapter_hide_footer ) {
			$source .= '#metadata("' . almaden_bookster_typst_escape_string( $title ) . '") <almaden-hide-footer>' . "\n";
		}
		if ( $is_credits && almaden_bookster_typst_bool( $chapter['credits_hide_header'] ?? false ) ) {
			$source .= '#metadata("credits") <almaden-hide-header>' . "\n";
		}
		if ( $is_credits && almaden_bookster_typst_bool( $chapter['credits_hide_page_number'] ?? false ) ) {
			$source .= '#metadata("credits") <almaden-hide-footer>' . "\n";
		}

		$image_url = '';
		if ( ! empty( $chapter['parity_image'] ) ) {
			$image_url = $chapter['parity_image'];
		} elseif ( ! empty( $chapter['chapter_image_enabled'] ) && '1' === (string) $chapter['chapter_image_enabled'] ) {
			$image_url = $chapter['chapter_image_url'] ?? '';
		}
		$image_asset = almaden_bookster_typst_register_upload( $image_url, $assets );
		if ( '' !== $image_asset ) {
			$source .= '#align(center + horizon)[#image("' . almaden_bookster_typst_escape_string( $image_asset ) .
				'", width: 100%, height: 100%, fit: "contain")]' . "\n#pagebreak()\n\n";
		}
		$credit_margin_top = $margin_top;
		$credit_margin_bottom = $margin_bot;
		if ( $is_credits && is_numeric( $chapter['credits_margin_top'] ?? null ) ) {
			$credit_margin_top = max( 0, min( 30, (float) $chapter['credits_margin_top'] ) );
		}
		if ( $is_credits && is_numeric( $chapter['credits_margin_bottom'] ?? null ) ) {
			$credit_margin_bottom = max( 0, min( 30, (float) $chapter['credits_margin_bottom'] ) );
		}
		if ( $is_credits ) {
			$source .= '#set page(margin: (top: ' . ( $credit_margin_top + $chapter_header_reserve ) . $unit . ', bottom: ' . ( $credit_margin_bottom + $chapter_footer_reserve ) . $unit . ', inside: ' . $margin_inside . $unit . ', outside: ' . $margin_outside . $unit . '))' . "\n";
		}

		$chapter_number = null;
		if ( ! $is_toc && ! $is_credits && '1' !== (string) ( $chapter['exclude_from_numbering'] ?? '0' ) ) {
			++$numbered_chapter_index;
			$chapter_number = $numbered_chapter_index;
		}

		$opening_visibility = almaden_bookster_typst_chapter_opening_visibility( $chapter, $settings );
		$opening_lines = array();
		$show_title = $opening_visibility['show_title'];
		$show_prefix = $opening_visibility['show_prefix'] && null !== $chapter_number;
		$show_subtitle = $opening_visibility['show_subtitle'];
		$prefix_position = isset( $settings['chapter_prefix_position'] ) && 'below' === strtolower( trim( (string) $settings['chapter_prefix_position'] ) ) ? 'below' : 'above';
		$subtitle_align = isset( $chapter['subtitle_align'] ) && in_array( $chapter['subtitle_align'], array( 'left', 'center', 'right' ), true )
			? $chapter['subtitle_align']
			: ( isset( $settings['chapter_subtitle_align'] ) && in_array( $settings['chapter_subtitle_align'], array( 'left', 'center', 'right' ), true ) ? $settings['chapter_subtitle_align'] : 'center' );

		if ( $show_prefix && 'above' === $prefix_position ) {
			$prefix_template = isset( $settings['chapter_prefix_template'] ) ? (string) $settings['chapter_prefix_template'] : 'Capítulo {N}';
			$prefix_text = str_replace(
				array( '{N}', '{R}' ),
				array(
					(string) $chapter_number,
					almaden_bookster_typst_toc_roman( $chapter_number ),
				),
				$prefix_template
			);
			$opening_lines[] = '#align(' . $title_align . ')[#text(font: "' . almaden_bookster_typst_escape_string( $title_font_family ) . '", size: ' . $title_size . 'pt, weight: ' . $title_font_weight . ', style: "' . almaden_bookster_typst_escape_string( $title_font_style ) . '", tracking: ' . $title_letter_spacing . 'pt)[' . almaden_bookster_typst_escape_markup( $prefix_text ) . ']]';
		}

		if ( $show_title ) {
			$display_title = almaden_bookster_typst_transform_title( $title, $title_transform );
			$opening_lines[] = '#align(' . $title_align . ')[#heading(level: 1, outlined: true)[#text(font: "' . almaden_bookster_typst_escape_string( $title_font_family ) . '", size: ' . $title_size . 'pt, weight: ' . $title_font_weight . ', style: "' . almaden_bookster_typst_escape_string( $title_font_style ) . '", tracking: ' . $title_letter_spacing . 'pt)[' . almaden_bookster_typst_escape_markup( $display_title ) . ']]]' ;
		}

		if ( $show_prefix && 'below' === $prefix_position ) {
			$prefix_template = isset( $settings['chapter_prefix_template'] ) ? (string) $settings['chapter_prefix_template'] : 'Capítulo {N}';
			$prefix_text = str_replace(
				array( '{N}', '{R}' ),
				array(
					(string) $chapter_number,
					almaden_bookster_typst_toc_roman( $chapter_number ),
				),
				$prefix_template
			);
			$opening_lines[] = '#align(' . $title_align . ')[#text(font: "' . almaden_bookster_typst_escape_string( $title_font_family ) . '", size: ' . $title_size . 'pt, weight: ' . $title_font_weight . ', style: "' . almaden_bookster_typst_escape_string( $title_font_style ) . '", tracking: ' . $title_letter_spacing . 'pt)[' . almaden_bookster_typst_escape_markup( $prefix_text ) . ']]';
		}

		if ( $show_subtitle ) {
			$subtitle_text = trim( (string) ( $chapter['subtitle_text'] ?? '' ) );
			$subtitle_font_family = almaden_bookster_typst_font_family( $chapter['subtitle_font_family'] ?? ( $settings['chapter_subtitle_font_family'] ?? $font_family ), $font_family );
			$subtitle_font_size = almaden_bookster_typst_number( $chapter, 'subtitle_font_size', almaden_bookster_typst_number( $settings, 'chapter_subtitle_font_size', 12, 6, 72 ), 6, 72 );
			$subtitle_font_weight = almaden_bookster_typst_font_weight( $chapter['subtitle_font_weight'] ?? ( $settings['chapter_subtitle_font_weight'] ?? $font_weight ), $font_weight );
			$subtitle_font_style = strtolower( trim( (string) ( $chapter['subtitle_font_style'] ?? ( $settings['chapter_subtitle_font_style'] ?? 'normal' ) ) ) );
			if ( ! in_array( $subtitle_font_style, array( 'normal', 'italic', 'oblique' ), true ) ) {
				$subtitle_font_style = 'normal';
			}
			$subtitle_letter_spacing = almaden_bookster_typst_number( $chapter, 'subtitle_letter_spacing', almaden_bookster_typst_number( $settings, 'chapter_subtitle_letter_spacing', 0, -20, 20 ), -20, 20 );
			$subtitle_text_transform = strtolower( trim( (string) ( $chapter['subtitle_text_transform'] ?? ( $settings['chapter_subtitle_text_transform'] ?? 'none' ) ) ) );
			$subtitle_display = almaden_bookster_typst_transform_title( $subtitle_text, $subtitle_text_transform );
			$opening_lines[] = '#align(' . $subtitle_align . ')[#text(font: "' . almaden_bookster_typst_escape_string( $subtitle_font_family ) . '", size: ' . round( max( 6, $subtitle_font_size * 0.75 ), 3 ) . 'pt, weight: ' . $subtitle_font_weight . ', style: "' . almaden_bookster_typst_escape_string( $subtitle_font_style ) . '", tracking: ' . round( $subtitle_letter_spacing * 0.75, 3 ) . 'pt)[' . almaden_bookster_typst_escape_markup( $subtitle_display ) . ']]';
		}

		if ( ! empty( $opening_lines ) ) {
			$source .= ( $show_title ? '#v(10mm)' : '#v(4mm)' ) . "\n";
			$source .= implode( "\n#v(3mm)\n", $opening_lines ) . "\n";
			if ( $show_title ) {
				$source .= '#v(' . $title_gap . $unit . ')' . "\n\n";
			} else {
				$source .= "\n";
			}
		}

		if ( $is_toc ) {
			$toc_title_source = almaden_bookster_typst_render_toc(
				$chapter,
				$chapters,
				$settings,
				array(
					'title_family'      => $title_font_family,
					'title_size'        => $title_size,
					'title_weight'      => $title_font_weight,
					'title_line_height' => 1.2,
					'item_family'       => $font_family,
					'item_size'         => $font_size,
					'item_weight'       => $font_weight,
					'item_line_height'  => $line_height,
				),
				$assets,
				$resolve_toc_font,
				! ( isset( $chapter['toc_hide_title'] ) && '1' === (string) $chapter['toc_hide_title'] )
			);
			$source .= $toc_title_source;
			continue;
		}

		if ( $is_credits && ! empty( $credits_config ) ) {
			$credits_font_family = almaden_bookster_typst_font_family( $chapter['credits_font_family'] ?? '', $font_family );
			$credits_font_size = almaden_bookster_typst_number( $chapter, 'credits_font_size', $font_size, 5, 72 );
			$credits_font_weight = almaden_bookster_typst_font_weight( $chapter['credits_font_weight'] ?? $font_weight, $font_weight );
			$credits_tracking = almaden_bookster_typst_number( $chapter, 'credits_letter_spacing', 0, -20, 20 );
			$credits_align = almaden_bookster_typst_credits_alignment( $chapter['credits_align'] ?? '' );
			$source .= almaden_bookster_typst_render_credits(
				$credits_config,
				$chapter['credits_author_label'] ?? '',
				array(
					'family'      => $credits_font_family,
					'size'        => $credits_font_size,
					'weight'      => $credits_font_weight,
					'line_height' => $line_height,
					'tracking'    => $credits_tracking,
					'align'       => $credits_align,
				),
				$assets,
				$resolve_credits_font,
				$payload['coverSettings'] ?? ( $payload['cover_settings'] ?? array() )
			);
		} else {
			if ( $page_columns_enabled && ! $is_credits ) {
				$source .= '#set page(columns: ' . $page_columns_count . ')' . "\n";
				$source .= '#set columns(gutter: ' . almaden_bookster_typst_length_literal( $page_columns_gap, $unit ) . ')' . "\n";
			}
			$chapter_footnotes = 'page' === $footnote_mode
				? array(
					'raw' => $content,
					'definitions' => array(),
					'order' => array(),
					'numbers' => array(),
				)
				: almaden_bookster_typst_collect_footnote_data( $content );
			$chapter_endnotes = array();
			foreach ( (array) ( $chapter_footnotes['order'] ?? array() ) as $footnote_id ) {
				if ( empty( $chapter_footnotes['definitions'][ $footnote_id ] ) ) {
					continue;
				}
				$chapter_endnotes[] = array(
					'id'     => $footnote_id,
					'number' => (int) ( $chapter_footnotes['numbers'][ $footnote_id ] ?? 0 ),
					'body'   => (string) $chapter_footnotes['definitions'][ $footnote_id ],
				);
			}
			$content_render_options = array(
				'hyphenation_exceptions' => $hyphenation_exceptions,
				'heading_styles'         => array(
					1 => array(
						'font_family'    => $heading1_font_family,
						'font_size'      => $heading1_font_size,
						'font_weight'    => $heading1_font_weight,
						'font_style'     => $heading1_font_style,
						'letter_spacing' => 0,
					),
					2 => array(
						'font_family'    => $heading2_font_family,
						'font_size'      => $heading2_font_size,
						'font_weight'    => $heading2_font_weight,
						'font_style'     => $heading2_font_style,
						'letter_spacing' => 0,
					),
					3 => array(
						'font_family'    => $heading3_font_family,
						'font_size'      => $heading3_font_size,
						'font_weight'    => $heading3_font_weight,
						'font_style'     => $heading3_font_style,
						'letter_spacing' => 0,
					),
				),
			);
			if ( 'page' !== $footnote_mode ) {
				$content_render_options['footnotes'] = $chapter_footnotes;
				$content_render_options['footnote_mode'] = $footnote_mode;
			}
			$content_align = $justify ? $last_align : $text_align;
			$uses_page_templates = ! empty( $page_template_context['templates'] );
			if ( $uses_page_templates ) {
				// Physical template pages must stay at document level. #set align keeps
				// the chapter alignment without wrapping those pages in a container.
				$source .= '#set align(' . $content_align . ')' . "\n";
			} else {
				$source .= '#align(' . $content_align . ")[\n";
			}
			$source       .= almaden_bookster_typst_render_blocks(
				$chapter_footnotes['raw'] ?? $content,
				$content_render_options
			) . "\n";
			if ( $uses_page_templates ) {
				$source .= '#set align(left)' . "\n";
			} else {
				$source .= "]\n";
			}
			if ( ! empty( $chapter_endnotes ) ) {
				if ( 'chapter' === $footnote_mode ) {
					$source .= "\n" . almaden_bookster_typst_render_footnote_entries(
						$chapter_endnotes,
						array(
							'title'         => $footnote_chapter_title,
							'title_level'   => 2,
							'font_family'   => $footnote_font_family,
							'font_size'     => $footnote_font_size,
							'font_weight'   => $footnote_font_weight,
							'align'         => $footnote_align,
							'leading'       => $footnote_line_height,
							'letter_spacing'=> $footnote_letter_spacing,
							'hyphenate'     => $footnote_hyphenate,
							'entry_gap'     => $footnote_entry_spacing,
							'heading_margin'=> 0.7,
						)
					);
				} elseif ( 'book' === $footnote_mode ) {
					$book_reference_groups[] = array(
						'title'   => $title,
						'entries' => $chapter_endnotes,
					);
				}
			}
			if ( $page_columns_enabled && ! $is_credits ) {
				$source .= '#set page(columns: 1)' . "\n";
			}
		}
		$plain_content  = almaden_bookster_typst_plain_text( $content );
		if ( '' !== $plain_content ) {
			$plain_parts[] = $plain_content;
		}
		$plain_extras = array_merge( $plain_extras, almaden_bookster_typst_plain_footnotes( $content ) );
		for ( $blank_index = 0; $blank_index < $blank_after; ++$blank_index ) {
			$source .= "\n#pagebreak()\n";
			$source .= '#metadata("credits-after") <almaden-intentional-blank>' . "\n";
		}
		if ( $is_credits ) {
			$source .= '#set page(margin: (top: ' . ( $margin_top + $chapter_header_reserve ) . $unit . ', bottom: ' . ( $margin_bot + $chapter_footer_reserve ) . $unit . ', inside: ' . $margin_inside . $unit . ', outside: ' . $margin_outside . $unit . '))' . "\n";
		}
	}

	if ( 'book' === $footnote_mode && ! empty( $book_reference_groups ) ) {
		$source .= "\n#pagebreak()\n\n";
		$source .= '#metadata("' . almaden_bookster_typst_escape_string( $footnote_book_title ) . '") <almaden-chapter-start>' . "\n";
		$source .= '#heading(level: 1)[#text(font: "' . almaden_bookster_typst_escape_string( $footnote_font_family ) . '", size: ' . max( 12, round( $footnote_font_size * 1.9, 2 ) ) . 'pt, weight: ' . $footnote_font_weight . ')[ ' . almaden_bookster_typst_escape_markup( $footnote_book_title ) . ' ]]' . "\n";
		$source .= '#v(0.7cm)' . "\n";
		foreach ( $book_reference_groups as $reference_group ) {
			if ( empty( $reference_group['entries'] ) ) {
				continue;
			}
			$source .= almaden_bookster_typst_render_footnote_entries(
				$reference_group['entries'],
				array(
					'title'       => $reference_group['title'] ?? '',
					'title_level' => 2,
					'font_family' => $footnote_font_family,
					'font_size'   => $footnote_font_size,
					'font_weight' => $footnote_font_weight,
					'align'       => $footnote_align,
					'leading'     => $footnote_line_height,
					'letter_spacing' => $footnote_letter_spacing,
					'hyphenate'   => $footnote_hyphenate,
					'entry_gap'   => $footnote_entry_spacing,
				)
			);
		}
	}

	$font_assets = array_filter(
		array_merge(
			$font_error ? array() : $font['files'],
			$title_font_error ? array() : $title_font['files'],
			$header_font_error ? array() : $header_font['files'],
			$footer_font_error ? array() : $footer_font['files'],
			$footnote_font_error ? array() : $footnote_font_assets,
			$heading1_font_error ? array() : $heading1_font['files'],
			$heading2_font_error ? array() : $heading2_font['files'],
			$heading3_font_error ? array() : $heading3_font['files'],
			$toc_font_assets,
			$credits_font_assets,
			$inline_font_assets
		)
	);
	$source = almaden_bookster_typst_compose_page_templates( $source, $page_template_context );

	return array(
		'source'        => $source,
		'page_templates' => $page_template_context['templates'],
		'page_template_context' => $page_template_context,
		'semantic_text' => implode( ' ', $plain_parts ),
		'semantic_extras' => $plain_extras,
		'assets'        => $assets,
		'font_assets'   => array_values( array_unique( $font_assets ) ),
		'build_error'   => $font_error ?: $title_font_error ?: $header_font_error ?: $footer_font_error ?: $heading1_font_error ?: $heading2_font_error ?: $heading3_font_error ?: $toc_font_error ?: $credits_font_error ?: $inline_font_error,
		'heading_styles' => array(
			1 => array(
				'font_family'    => $heading1_font_family,
				'font_size'      => $heading1_font_size,
				'font_weight'    => $heading1_font_weight,
				'font_style'     => $heading1_font_style,
				'letter_spacing' => 0,
			),
			2 => array(
				'font_family'    => $heading2_font_family,
				'font_size'      => $heading2_font_size,
				'font_weight'    => $heading2_font_weight,
				'font_style'     => $heading2_font_style,
				'letter_spacing' => 0,
			),
			3 => array(
				'font_family'    => $heading3_font_family,
				'font_size'      => $heading3_font_size,
				'font_weight'    => $heading3_font_weight,
				'font_style'     => $heading3_font_style,
				'letter_spacing' => 0,
			),
		),
		'typography'    => array(
			'family'            => $font_family,
			'size'              => $font_size,
			'weight'            => $font_weight,
			'line_height'       => $line_height,
			'align'             => $text_align,
			'last_align'        => $last_align,
			'hyphenate'         => $hyphenate,
			'paragraph_indent'  => $paragraph_indent,
			'paragraph_spacing' => $paragraph_gap,
		),
		'geometry'      => array(
			'unit'           => $unit,
			'width'          => $width,
			'height'         => $height,
			'top'            => $margin_top,
			'bottom'         => $margin_bot,
			'content_top'    => $margin_top + $header_reserve,
			'content_bottom' => $margin_bot + $footer_reserve,
			'inside'         => $margin_inside,
			'outside'        => $margin_outside,
			'bleed'          => $bleed,
		),
		'source_hash'   => hash( 'sha256', implode( "\n", array_merge( $plain_parts, $plain_extras ) ) ),
	);
}
