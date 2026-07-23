<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

function almaden_bookster_parse_txt_import_document( $path, $filename ) {
	$text = file_get_contents( $path );
	if ( false === $text ) {
		return new WP_Error( 'txt_read_failed', 'No se pudo leer el archivo TXT.' );
	}

	return almaden_bookster_blocks_from_plain_text( $text, $filename, 'txt' );
}

function almaden_bookster_blocks_from_plain_text( $text, $filename, $format ) {
	$lines = preg_split( "/\R/u", (string) $text );
	$blocks = array();
	$pending = array();

	$flush_pending = function() use ( &$blocks, &$pending ) {
		if ( empty( $pending ) ) {
			return;
		}
		$joined = trim( implode( ' ', $pending ) );
		if ( '' !== $joined ) {
			$joined = htmlspecialchars( $joined, ENT_QUOTES, get_bloginfo( 'charset' ) ?: 'UTF-8' );
			$style_key = almaden_bookster_guess_plain_text_style_key( $joined );
			$blocks[] = array(
				'type'          => in_array( $style_key, array( 'heading-1', 'heading-2', 'heading-3', 'title', 'subtitle' ), true ) ? 'heading' : 'paragraph',
				'text'          => $joined,
				'style_key'     => $style_key,
				'style_label'   => almaden_bookster_plain_text_style_label( $style_key ),
				'heading_level' => almaden_bookster_import_heading_number_from_style_key( $style_key ),
			);
		}
		$pending = array();
	};

	foreach ( $lines as $line ) {
		$trimmed = trim( (string) $line );
		if ( '' === $trimmed ) {
			$flush_pending();
			$blocks[] = array(
				'type'          => 'blank',
				'text'          => '',
				'style_key'     => '',
				'style_label'   => 'Blank',
				'heading_level' => 0,
			);
			continue;
		}

		$pending[] = $trimmed;
	}

	$flush_pending();

	return array(
		'title'       => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
		'blocks'      => $blocks,
		'block_count' => count( array_filter( $blocks, function( $block ) { return 'blank' !== $block['type']; } ) ),
	);
}

function almaden_bookster_guess_plain_text_style_key( $text ) {
	$length = function_exists( 'mb_strlen' ) ? mb_strlen( $text ) : strlen( $text );
	if ( preg_match( '/^cap[ií]tulo\b/i', $text ) ) {
		return 'heading-1';
	}
	if ( preg_match( '/^[A-ZÁÉÍÓÚÜÑ0-9 ,.:;¿?!()\-]{4,}$/u', $text ) && $length <= 90 ) {
		return 'heading-2';
	}
	if ( preg_match( '/^[A-ZÁÉÍÓÚÜÑ][^\n]{3,70}$/u', $text ) && $length <= 70 ) {
		return 'heading-3';
	}
	return 'paragraph';
}

function almaden_bookster_plain_text_style_label( $style_key ) {
	$labels = array(
		'heading-1' => 'Heading 1 (heurístico)',
		'heading-2' => 'Heading 2 (heurístico)',
		'heading-3' => 'Heading 3 (heurístico)',
		'title'     => 'Title (heurístico)',
		'subtitle'  => 'Subtitle (heurístico)',
		'paragraph' => 'Paragraph',
	);
	return isset( $labels[ $style_key ] ) ? $labels[ $style_key ] : $style_key;
}

function almaden_bookster_normalize_import_style_key( $style_name ) {
	$style_name = strtolower( trim( (string) $style_name ) );
	$style_name = preg_replace( '/[^a-z0-9]+/i', '-', $style_name );
	$style_name = trim( $style_name, '-' );
	if ( in_array( $style_name, array( 'title', 'subtitle' ), true ) ) {
		return $style_name;
	}
	if ( preg_match( '/heading-?([1-6])/', $style_name, $m ) ) {
		return 'heading-' . intval( $m[1] );
	}
	if ( preg_match( '/heading([1-6])/', $style_name, $m ) ) {
		return 'heading-' . intval( $m[1] );
	}
	return $style_name;
}

function almaden_bookster_import_heading_number_from_style_key( $style_key ) {
	if ( 'title' === $style_key || 'subtitle' === $style_key ) {
		return 0;
	}
	if ( preg_match( '/heading-(\d)/', (string) $style_key, $m ) ) {
		return intval( $m[1] );
	}
	return 0;
}

function almaden_bookster_import_level_to_markdown( $heading_level, $separator_key ) {
	$separator_level = almaden_bookster_import_heading_number_from_style_key( $separator_key );
	if ( $heading_level <= 0 ) {
		return 0;
	}
	return max( 1, $heading_level - $separator_level + 1 );
}

function almaden_bookster_build_separator_candidates( array $blocks ) {
	$counts = array();
	foreach ( $blocks as $block ) {
		if ( 'heading' !== $block['type'] ) {
			continue;
		}
		$key = $block['style_key'] ?: 'heading-1';
		if ( ! isset( $counts[ $key ] ) ) {
			$counts[ $key ] = array(
				'count' => 0,
				'label' => almaden_bookster_separator_label_from_key( $key ),
			);
		}
		$counts[ $key ]['count']++;
	}

	if ( empty( $counts ) ) {
		$counts = array(
			'heading-1' => array( 'count' => 1, 'label' => 'Heading 1' ),
			'heading-2' => array( 'count' => 1, 'label' => 'Heading 2' ),
		);
	}

	uasort( $counts, function( $a, $b ) {
		return $b['count'] <=> $a['count'];
	} );

	$options = array();
	foreach ( $counts as $key => $meta ) {
		$options[] = array(
			'key'   => $key,
			'label' => $meta['label'],
			'count' => $meta['count'],
		);
	}

	$known = array( 'title', 'subtitle', 'heading-1', 'heading-2', 'heading-3' );
	foreach ( $known as $key ) {
		$exists = false;
		foreach ( $options as $option ) {
			if ( $option['key'] === $key ) {
				$exists = true;
				break;
			}
		}
		if ( ! $exists ) {
			$options[] = array(
				'key'   => $key,
				'label' => almaden_bookster_separator_label_from_key( $key ),
				'count' => 0,
			);
		}
	}

	return array_slice( $options, 0, 6 );
}
