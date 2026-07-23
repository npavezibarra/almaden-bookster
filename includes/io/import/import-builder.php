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
	$text = html_entity_decode( $text, ENT_QUOTES, get_bloginfo( 'charset' ) ?: 'UTF-8' );
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

function almaden_bookster_split_blocks_into_chapters( array $blocks, $mapping ) {
	if ( ! is_array( $mapping ) ) {
		$mapping = array();
	}
	$chapters = array();
	$current = array(
		'title'       => '',
		'content'     => '',
		'block_count' => 0,
		'outline'     => array(),
		'id'          => 'import-' . uniqid(),
	);

	$append_block = function( &$chapter, $block, array $mapping ) {
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

		$chapter['content'] .= trim( $block['text'] ) . "\n\n";
		$chapter['outline'][] = array(
			'level' => 0,
			'label' => 'Texto',
			'text'  => trim( $block['text'] ),
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

		$append_block( $current, $block, $mapping );
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

function almaden_bookster_build_chapters_from_parsed_document( $book_id, array $parsed, array $mapping ) {
	$available_styles = isset( $parsed['separator_options'] ) && is_array( $parsed['separator_options'] ) ? $parsed['separator_options'] : almaden_bookster_build_separator_candidates( $parsed['blocks'] );
	$mapping = almaden_bookster_normalize_import_mapping( $mapping, $available_styles );
	$validation = almaden_bookster_validate_import_mapping( $mapping, $available_styles );
	if ( ! empty( $validation['errors'] ) ) {
		return new WP_Error( 'invalid_mapping', implode( ' ', $validation['errors'] ) );
	}
	$chapters = almaden_bookster_split_blocks_into_chapters( $parsed['blocks'], $mapping );
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
