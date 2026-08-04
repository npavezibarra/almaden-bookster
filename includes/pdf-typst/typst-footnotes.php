<?php
/**
 * Footnote and endnote helpers for Typst export.
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'ALMADEN_TYPST_TESTING' ) ) {
	exit;
}

function almaden_bookster_typst_footnote_mode( $settings ) {
	$mode = strtolower( trim( (string) ( $settings['footnote_mode'] ?? 'page' ) ) );
	return in_array( $mode, array( 'page', 'chapter', 'book' ), true ) ? $mode : 'page';
}

function almaden_bookster_typst_footnote_title( $settings, $scope = 'chapter' ) {
	$scope = 'book' === $scope ? 'book' : 'chapter';
	$default = 'book' === $scope ? 'Referencias' : 'Referencia';
	$key = 'book' === $scope ? 'footnote_book_title' : 'footnote_chapter_title';
	$title = trim( (string) ( $settings[ $key ] ?? '' ) );

	return '' !== $title ? $title : $default;
}

function almaden_bookster_typst_footnote_alignment( $align ) {
	$align = strtolower( trim( (string) $align ) );
	return in_array( $align, array( 'left', 'center', 'right', 'justify' ), true ) ? $align : 'left';
}

function almaden_bookster_typst_footnote_leading_pt( $value, $font_size = 8.5, $fallback = 11.5 ) {
	$value = is_numeric( $value ) ? (float) $value : $fallback;
	return max( 0.1, min( 40, $value ) );
}

function almaden_bookster_typst_footnote_spacing_pt( $value, $fallback = 6 ) {
	$value = is_numeric( $value ) ? (float) $value : $fallback;
	return max( 0, min( 40, $value ) );
}

/**
 * Render the native Typst footnote rules used by the page-level mode.
 *
 * The body of a show-rule switches between Typst code and content markup.
 * Keep the # prefixes inside the content blocks so functions are executed
 * instead of being printed as literal text in the exported PDF.
 */
function almaden_bookster_typst_render_page_footnote_rules( $options = array() ) {
	$font_family = almaden_bookster_typst_font_family( $options['font_family'] ?? 'Merriweather', 'Merriweather' );
	$font_size = isset( $options['font_size'] ) && is_numeric( $options['font_size'] )
		? max( 4, min( 48, (float) $options['font_size'] ) )
		: 8.5;
	$font_weight = almaden_bookster_typst_font_weight( $options['font_weight'] ?? 'normal' );
	$align_value = $options['align'] ?? 'left';
	$separator_align_value = $options['separator_align'] ?? 'left';
	$separator_width_value = (string) ( $options['separator_width'] ?? '100' );
	$align = in_array( $align_value, array( 'left', 'center', 'right', 'justify' ), true ) ? $align_value : 'left';
	$separator_align = in_array( $separator_align_value, array( 'left', 'center', 'right' ), true ) ? $separator_align_value : 'left';
	$separator_width = in_array( $separator_width_value, array( '100', '75', '50', '25' ), true ) ? $separator_width_value : '100';
	$separator_show = ! empty( $options['separator_show'] );
	$separator_thickness = isset( $options['separator_thickness'] ) && is_numeric( $options['separator_thickness'] ) ? max( 0.05, min( 5, (float) $options['separator_thickness'] ) ) : 0.25;
	$padding_top = isset( $options['padding_top'] ) && is_numeric( $options['padding_top'] ) ? max( 0, min( 10, (float) $options['padding_top'] ) ) : 0.15;
	$padding_bottom = isset( $options['padding_bottom'] ) && is_numeric( $options['padding_bottom'] ) ? max( 0, min( 10, (float) $options['padding_bottom'] ) ) : 0.15;
	$padding_left = isset( $options['padding_left'] ) && is_numeric( $options['padding_left'] ) ? max( 0, min( 10, (float) $options['padding_left'] ) ) : 0;
	$padding_right = isset( $options['padding_right'] ) && is_numeric( $options['padding_right'] ) ? max( 0, min( 10, (float) $options['padding_right'] ) ) : 0;
	$separator_margin = isset( $options['separator_margin_bottom'] ) && is_numeric( $options['separator_margin_bottom'] ) ? max( 0, min( 10, (float) $options['separator_margin_bottom'] ) ) : 0.15;
	$line_height = almaden_bookster_typst_footnote_leading_pt( $options['line_height'] ?? null, $font_size, 11.5 );
	$letter_spacing = isset( $options['letter_spacing'] ) && is_numeric( $options['letter_spacing'] ) ? max( -20, min( 20, (float) $options['letter_spacing'] ) ) : 0;
	$hyphenate = ! empty( $options['hyphenate'] );
	$align_wrapper = in_array( $align, array( 'center', 'right' ), true );

	$source = '#set footnote.entry(';
	$source .= 'separator: ' . ( $separator_show ? '[#align(' . $separator_align . ')[#line(length: ' . $separator_width . '% + 0pt, stroke: ' . $separator_thickness . 'pt)]]' : '[]' ) . ', ';
	$source .= 'clearance: ' . round( $padding_top + ( $separator_show ? $separator_margin : 0 ), 3 ) . 'cm, ';
	$source .= 'gap: ' . round( $padding_bottom, 3 ) . 'cm, ';
	$source .= 'indent: ' . round( $padding_left, 3 ) . 'cm' . ')' . "\n";
	$source .= '#show footnote.entry: set text(font: "' . almaden_bookster_typst_escape_string( $font_family ) . '", size: ' . $font_size . 'pt, weight: ' . $font_weight . ', tracking: ' . $letter_spacing . 'pt, hyphenate: ' . ( $hyphenate ? 'true' : 'false' ) . ')' . "\n";
	$source .= '#show footnote.entry: it => {' . "\n";
	$source .= '  set par(justify: ' . ( 'justify' === $align ? 'true' : 'false' ) . ', leading: ' . $line_height . 'em, spacing: 0pt)' . "\n";
	$source .= '  pad(right: ' . round( $padding_right, 3 ) . 'cm)[' . "\n";
	if ( $align_wrapper ) {
		$source .= '    #align(' . $align . ')[' . "\n";
	}
	$source .= '      #grid(columns: (auto, 1fr), column-gutter: 0.35em, counter(footnote).display(at: it.note.location()), it.note.body)' . "\n";
	if ( $align_wrapper ) {
		$source .= '    ]' . "\n";
	}
	$source .= '  ]' . "\n";
	$source .= '}' . "\n\n";

	return $source;
}

function almaden_bookster_typst_collect_footnote_data( $raw ) {
	$raw = str_replace( array( "\r\n", "\r" ), "\n", (string) $raw );
	$definitions = array();
	preg_match_all( '/(?:^|\n)\[\^([^\]]+)\]:\s*([^\n]+)/', $raw, $matches, PREG_SET_ORDER );
	foreach ( $matches as $match ) {
		$definitions[ $match[1] ] = trim( $match[2] );
	}

	$clean_raw = preg_replace( '/(?:^|\n)\[\^([^\]]+)\]:\s*([^\n]+)/', "\n", $raw );
	$order = array();
	$seen = array();
	preg_match_all( '/\[\^([^\]]+)\]/', $clean_raw, $references, PREG_SET_ORDER );
	foreach ( $references as $reference ) {
		$id = $reference[1];
		if ( ! isset( $definitions[ $id ] ) || isset( $seen[ $id ] ) ) {
			continue;
		}
		$seen[ $id ] = true;
		$order[] = $id;
	}

	$numbers = array();
	foreach ( $order as $index => $id ) {
		$numbers[ $id ] = $index + 1;
	}

	return array(
		'raw'        => $clean_raw,
		'definitions'=> $definitions,
		'order'      => $order,
		'numbers'    => $numbers,
	);
}

function almaden_bookster_typst_render_footnote_entries( $entries, $options = array() ) {
	$entries = is_array( $entries ) ? array_values( $entries ) : array();
	if ( empty( $entries ) ) {
		return '';
	}

	$font_family = almaden_bookster_typst_font_family( $options['font_family'] ?? 'Merriweather', 'Merriweather' );
	$font_size = isset( $options['font_size'] ) && is_numeric( $options['font_size'] )
		? max( 4, min( 48, (float) $options['font_size'] ) )
		: 8.5;
	$font_weight = almaden_bookster_typst_font_weight( $options['font_weight'] ?? 'normal' );
	$align = almaden_bookster_typst_footnote_alignment( $options['align'] ?? 'left' );
	$title = trim( (string) ( $options['title'] ?? '' ) );
	$title_level = isset( $options['title_level'] ) && is_numeric( $options['title_level'] ) ? max( 1, min( 6, (int) $options['title_level'] ) ) : 2;
	$title_size = isset( $options['title_size'] ) && is_numeric( $options['title_size'] )
		? max( 10, min( 48, (float) $options['title_size'] ) )
		: max( 12, round( $font_size * 1.9, 2 ) );
	$title_weight = almaden_bookster_typst_font_weight( $options['title_weight'] ?? 'bold' );
	$title_family = almaden_bookster_typst_font_family( $options['title_font_family'] ?? $font_family, $font_family );
	$indent = isset( $options['indent'] ) && is_numeric( $options['indent'] ) ? max( 0, min( 10, (float) $options['indent'] ) ) : 0;
	$leading = almaden_bookster_typst_footnote_leading_pt( $options['leading'] ?? null, $font_size, 11.5 );
	$spacing = isset( $options['spacing'] ) && is_numeric( $options['spacing'] ) ? max( 0, min( 40, (float) $options['spacing'] ) ) : 0;
	$entry_gap = almaden_bookster_typst_footnote_spacing_pt( $options['entry_gap'] ?? ( $options['entry_spacing'] ?? null ), 6 );
	$heading_margin = isset( $options['heading_margin'] ) && is_numeric( $options['heading_margin'] ) ? max( 0, min( 10, (float) $options['heading_margin'] ) ) : 0.7;
	$exceptions = (array) ( $options['hyphenation_exceptions'] ?? array() );
	$hyphenate = ! empty( $options['hyphenate'] );
	$letter_spacing = isset( $options['letter_spacing'] ) && is_numeric( $options['letter_spacing'] ) ? max( -20, min( 20, (float) $options['letter_spacing'] ) ) : 0;

	// Keep the footnote typography scoped to this reference list.
	$source = '#block[' . "\n";
	if ( '' !== $title ) {
		$source .= '#v(' . $heading_margin . 'cm)' . "\n";
		$source .= '#heading(level: ' . $title_level . ')[#text(font: "' . almaden_bookster_typst_escape_string( $title_family ) . '", size: ' . $title_size . 'pt, weight: ' . $title_weight . ')[ ' . almaden_bookster_typst_escape_markup( $title ) . ' ]]' . "\n";
		$source .= '#v(' . max( 0.3, $heading_margin / 2 ) . 'cm)' . "\n";
	}
	$source .= '#set text(font: "' . almaden_bookster_typst_escape_string( $font_family ) . '", size: ' . $font_size . 'pt, weight: ' . $font_weight . ', tracking: ' . $letter_spacing . 'pt, hyphenate: ' . ( $hyphenate ? 'true' : 'false' ) . ')' . "\n";
	$source .= '#set par(justify: ' . ( 'justify' === $align ? 'true' : 'false' ) . ', leading: ' . $leading . 'pt, spacing: ' . $spacing . 'pt, first-line-indent: 0pt)' . "\n";

	foreach ( $entries as $entry ) {
		$number = isset( $entry['number'] ) ? (int) $entry['number'] : 0;
		$body = isset( $entry['body'] ) ? (string) $entry['body'] : '';
		$rendered_body = almaden_bookster_typst_render_inline( $body, array(), 0, $exceptions, 'page', array() );
		$line = '#par[' . '#super[' . $number . '] ' . $rendered_body . ']';
		if ( in_array( $align, array( 'center', 'right' ), true ) ) {
			$line = '#align(' . $align . ')[' . $line . ']';
		}
		$source .= $line . "\n";
		$source .= '#v(' . $entry_gap . 'pt)' . "\n";
	}

	return $source . "]\n";
}
