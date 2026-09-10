<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

function almaden_bookster_build_chapter_preview( array $blocks, $mapping ) {
	$chapters = almaden_bookster_split_blocks_into_chapters( $blocks, $mapping );
	$preview = array();
	foreach ( $chapters as $chapter ) {
		$preview[] = array(
			'title'  => $chapter['title'],
			'blocks' => $chapter['block_count'],
			'outline' => isset( $chapter['outline'] ) ? $chapter['outline'] : array(),
		);
	}
	return $preview;
}

function almaden_bookster_clean_import_heading_text( $text ) {
	$text = (string) $text;
	$text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, get_bloginfo( 'charset' ) ?: 'UTF-8' );
	$text = strip_tags( $text );
	$text = preg_replace( '/\*\*\*(.*?)\*\*\*/s', '$1', $text );
	$text = preg_replace( '/\*\*(.*?)\*\*/s', '$1', $text );
	$text = preg_replace( '/\*(.*?)\*/s', '$1', $text );
	$text = preg_replace( '/__(.*?)__/s', '$1', $text );
	$text = preg_replace( '/_(.*?)_/s', '$1', $text );
	$text = preg_replace( '/[“”]/u', '"', $text );
	$text = preg_replace( "/[‘’]/u", "'", $text );
	$text = preg_replace( '/\s+/', ' ', $text );
	return trim( $text );
}

function almaden_bookster_normalize_import_text_entities( $text ) {
	return html_entity_decode( (string) $text, ENT_QUOTES | ENT_HTML5, get_bloginfo( 'charset' ) ?: 'UTF-8' );
}

function almaden_bookster_import_table_style_defaults() {
	return array(
		'enabled'       => '0',
		'background'    => '#f3f3f3',
		'border_color'  => '#d9d9d9',
		'border_width'  => 1,
		'padding_y'     => 18,
		'padding_x'     => 22,
		'border_radius' => 4,
		'font_size'     => '',
		'line_height'   => 1.65,
		'text_align'    => 'left',
	);
}

function almaden_bookster_sanitize_import_table_color( $value, $fallback ) {
	$value = trim( (string) $value );
	return preg_match( '/^#[0-9a-f]{6}$/i', $value ) ? strtolower( $value ) : $fallback;
}

function almaden_bookster_sanitize_import_table_number( $value, $fallback, $min, $max ) {
	if ( ! is_numeric( $value ) ) {
		return $fallback;
	}
	return max( $min, min( $max, (float) $value ) );
}

function almaden_bookster_normalize_import_table_style( $style ) {
	$defaults = almaden_bookster_import_table_style_defaults();
	$style = is_array( $style ) ? wp_parse_args( $style, $defaults ) : $defaults;
	$text_align = in_array( (string) $style['text_align'], array( 'left', 'center', 'right', 'justify' ), true ) ? (string) $style['text_align'] : $defaults['text_align'];

	return array(
		'enabled'       => '1' === (string) $style['enabled'] ? '1' : '0',
		'background'    => almaden_bookster_sanitize_import_table_color( $style['background'], $defaults['background'] ),
		'border_color'  => almaden_bookster_sanitize_import_table_color( $style['border_color'], $defaults['border_color'] ),
		'border_width'  => almaden_bookster_sanitize_import_table_number( $style['border_width'], $defaults['border_width'], 0, 12 ),
		'padding_y'     => almaden_bookster_sanitize_import_table_number( $style['padding_y'], $defaults['padding_y'], 0, 80 ),
		'padding_x'     => almaden_bookster_sanitize_import_table_number( $style['padding_x'], $defaults['padding_x'], 0, 100 ),
		'border_radius' => almaden_bookster_sanitize_import_table_number( $style['border_radius'], $defaults['border_radius'], 0, 48 ),
		'font_size'     => '' === trim( (string) $style['font_size'] ) ? '' : almaden_bookster_sanitize_import_table_number( $style['font_size'], '', 8, 32 ),
		'line_height'   => almaden_bookster_sanitize_import_table_number( $style['line_height'], $defaults['line_height'], 1, 3 ),
		'text_align'    => $text_align,
	);
}

function almaden_bookster_import_table_markdown_to_html( $text ) {
	$text = almaden_bookster_normalize_import_text_entities( $text );
	$text = htmlspecialchars( $text, ENT_QUOTES | ENT_HTML5, get_bloginfo( 'charset' ) ?: 'UTF-8' );
	$text = preg_replace( '/\*\*\*(.*?)\*\*\*/s', '<strong><em>$1</em></strong>', $text );
	$text = preg_replace( '/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $text );
	$text = preg_replace( '/\*(.*?)\*/s', '<em>$1</em>', $text );
	$text = str_replace( "\n", '<br>', $text );
	return $text;
}

function almaden_bookster_format_import_table_css_number( $number ) {
	return rtrim( rtrim( sprintf( '%.2F', (float) $number ), '0' ), '.' );
}

function almaden_bookster_import_table_to_raw_box( $text, array $style ) {
	$font_size = '' !== $style['font_size'] ? 'font-size:' . almaden_bookster_format_import_table_css_number( $style['font_size'] ) . 'px;' : '';
	$css = sprintf(
		'background:%1$s;border:%2$spx solid %3$s;padding:%4$spx %5$spx;border-radius:%6$spx;line-height:%7$s;text-align:%8$s;%9$s',
		$style['background'],
		almaden_bookster_format_import_table_css_number( $style['border_width'] ),
		$style['border_color'],
		almaden_bookster_format_import_table_css_number( $style['padding_y'] ),
		almaden_bookster_format_import_table_css_number( $style['padding_x'] ),
		almaden_bookster_format_import_table_css_number( $style['border_radius'] ),
		almaden_bookster_format_import_table_css_number( $style['line_height'] ),
		$style['text_align'],
		$font_size
	);
	$paragraphs = preg_split( "/\n{2,}/", trim( almaden_bookster_normalize_import_text_entities( $text ) ) );
	$html = '<div style="' . $css . '">' . "\n\n";
	foreach ( $paragraphs as $paragraph ) {
		$paragraph = trim( $paragraph );
		if ( '' === $paragraph ) {
			continue;
		}
		$html .= '<p>' . almaden_bookster_import_table_markdown_to_html( $paragraph ) . '</p>' . "\n\n";
	}
	$html = rtrim( $html ) . "\n\n" . '</div>';

	return "[box]\n[html]\n" . $html . "\n[/html]\n[/box]";
}

function almaden_bookster_split_blocks_into_chapters( array $blocks, $mapping, array $table_style = array() ) {
	if ( ! is_array( $mapping ) ) {
		$mapping = array();
	}
	$table_style = almaden_bookster_normalize_import_table_style( $table_style );
	$chapters = array();
	$current = array(
		'title'       => '',
		'content'     => '',
		'block_count' => 0,
		'outline'     => array(),
		'id'          => 'import-' . uniqid(),
	);

	$append_block = function( &$chapter, $block, array $mapping, array $table_style ) {
		if ( 'blank' === $block['type'] ) {
			$chapter['content'] = rtrim( $chapter['content'] ) . "\n\n";
			return;
		}

		if ( 'heading' === $block['type'] ) {
			$md_level = almaden_bookster_get_import_semantic_level( $block['style_key'], $mapping );
			$heading_text = almaden_bookster_clean_import_heading_text( $block['text'] );
			if ( $md_level > 0 ) {
				$chapter['content'] .= str_repeat( '#', $md_level ) . ' ' . $heading_text . "\n\n";
				$chapter['outline'][] = array(
					'level' => $md_level,
					'label' => almaden_bookster_separator_label_from_key( $block['style_key'] ),
					'text'  => $heading_text,
				);
				$chapter['block_count']++;
				return;
			}
		}

		$block_text = trim( almaden_bookster_normalize_import_text_entities( $block['text'] ) );
		if ( '1' === $table_style['enabled'] && isset( $block['style_key'] ) && 'table' === (string) $block['style_key'] ) {
			$block_text = almaden_bookster_import_table_to_raw_box( $block_text, $table_style );
		}
		$chapter['content'] .= $block_text . "\n\n";
		$chapter['outline'][] = array(
			'level' => 0,
			'label' => 'Texto',
			'text'  => $block_text,
		);
		$chapter['block_count']++;
	};

	$finalize = function( &$chapter, &$chapters ) {
		$chapter['content'] = trim( $chapter['content'] );
		if ( '' === $chapter['title'] ) {
			$chapter['title'] = 'Capítulo ' . ( count( $chapters ) + 1 );
		}
		if ( '' !== $chapter['title'] || '' !== $chapter['content'] ) {
			$chapters[] = $chapter;
		}
		$chapter = array(
			'title'       => '',
			'content'     => '',
			'block_count' => 0,
			'outline'     => array(),
			'id'          => 'import-' . uniqid(),
		);
	};

	foreach ( $blocks as $block ) {
		$is_separator = 'heading' === $block['type'] && isset( $mapping['chapter_separator'] ) && $mapping['chapter_separator'] === $block['style_key'];
		if ( $is_separator ) {
			$chapter_title = almaden_bookster_clean_import_heading_text( $block['text'] );
			if ( '' !== trim( $current['content'] ) ) {
				$finalize( $current, $chapters );
			}
			$current['title'] = $chapter_title;
			$current['content'] = '';
			$current['block_count'] = 0;
			$current['outline'][] = array(
				'level' => 1,
				'label' => 'Capítulo',
				'text'  => $chapter_title,
			);
			continue;
		}

		if ( '' === trim( $current['content'] ) && 'blank' === $block['type'] ) {
			continue;
		}

		$append_block( $current, $block, $mapping, $table_style );
	}

	if ( '' !== trim( $current['content'] ) ) {
		$finalize( $current, $chapters );
	}

	if ( empty( $chapters ) && ! empty( $blocks ) ) {
		$chapters[] = array(
			'title'       => 'Capítulo 1',
			'content'     => trim( implode( "\n\n", array_map( function( $block ) {
				return 'blank' === $block['type'] ? '' : ( 'heading' === $block['type'] ? almaden_bookster_clean_import_heading_text( $block['text'] ) : $block['text'] );
			}, $blocks ) ) ),
			'block_count' => count( array_filter( $blocks, function( $block ) {
				return 'blank' !== $block['type'];
			} ) ),
			'outline'     => array(),
			'id'          => 'import-' . uniqid(),
		);
	}

	return $chapters;
}

function almaden_bookster_build_chapters_from_parsed_document( $book_id, array $parsed, array $mapping, array $table_style = array() ) {
	$available_styles = isset( $parsed['separator_options'] ) && is_array( $parsed['separator_options'] ) ? $parsed['separator_options'] : almaden_bookster_build_separator_candidates( $parsed['blocks'] );
	$mapping = almaden_bookster_normalize_import_mapping( $mapping, $available_styles );
	$validation = almaden_bookster_validate_import_mapping( $mapping, $available_styles );
	if ( ! empty( $validation['errors'] ) ) {
		return new WP_Error( 'invalid_mapping', implode( ' ', $validation['errors'] ) );
	}
	$chapters = almaden_bookster_split_blocks_into_chapters( $parsed['blocks'], $mapping, $table_style );
	if ( empty( $chapters ) ) {
		return new WP_Error( 'empty_document', 'No se encontraron capítulos para importar.' );
	}

	$source_book_id = get_post_meta( $book_id, '_almaden_source_book_id', true );
	if ( empty( $source_book_id ) ) {
		$source_book_id = $book_id;
	}

	$existing = get_posts( array(
		'post_type'      => 'book_chapter',
		'post_parent'    => $source_book_id,
		'posts_per_page' => 1,
		'orderby'        => 'menu_order',
		'order'          => 'DESC',
		'fields'         => 'ids',
	) );
	$menu_order = ! empty( $existing ) ? ( intval( get_post_field( 'menu_order', $existing[0] ) ) + 1 ) : 1;
	$created = array();

	foreach ( $chapters as $index => $chapter ) {
		$post_id = wp_insert_post( array(
			'post_title'   => $chapter['title'],
			'post_content' => $chapter['content'],
			'post_status'  => 'publish',
			'post_type'    => 'book_chapter',
			'post_parent'  => $source_book_id,
			'menu_order'   => $menu_order + $index,
		), true );

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		$created[] = array(
			'id'       => strval( $post_id ),
			'old_id'   => $chapter['id'],
			'title'    => $chapter['title'],
			'content'  => $chapter['content'],
			'menu_order' => $menu_order + $index,
		);
	}

	return array(
		'chapter_count' => count( $created ),
		'chapters'      => $created,
		'warnings'      => $validation['warnings'],
	);
}
