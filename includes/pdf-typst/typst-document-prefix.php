<?php
/**
 * Build a complete Typst book document from editor state.
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'ALMADEN_TYPST_TESTING' ) ) {
	exit;
}

require_once __DIR__ . '/typst-document-helpers.php';

function almaden_bookster_typst_page_style_color_literal( $value, $fallback = '#ffffff' ) {
	$value = strtolower( trim( (string) $value ) );
	if ( ! preg_match( '/^#(?:[0-9a-f]{3}|[0-9a-f]{6}|[0-9a-f]{8})$/', $value ) ) {
		$value = strtolower( trim( (string) $fallback ) );
	}
	if ( '' === $value ) {
		$value = '#ffffff';
	}
	if ( '#' !== $value[0] ) {
		$value = '#' . $value;
	}

	return 'rgb("' . $value . '")';
}

function almaden_bookster_typst_page_style_background_paint( $background ) {
	$background = is_array( $background ) ? $background : array();
	$background_type = strtolower( trim( (string) ( $background['type'] ?? 'color' ) ) );

	if ( 'gradient' === $background_type ) {
		$gradient = is_array( $background['gradient'] ?? null ) ? $background['gradient'] : array();
		$stops = array();
		foreach ( (array) ( $gradient['stops'] ?? array() ) as $stop ) {
			$stop = is_array( $stop ) ? $stop : array();
			$stops[] = almaden_bookster_typst_page_style_color_literal( $stop['color'] ?? '#ffffff', '#ffffff' );
		}
		if ( empty( $stops ) ) {
			$stops[] = almaden_bookster_typst_page_style_color_literal( $background['color'] ?? '#ffffff', '#ffffff' );
		}
		$angle = isset( $gradient['angle'] ) && is_numeric( $gradient['angle'] )
			? max( 0, min( 360, (float) $gradient['angle'] ) )
			: 135;

		return 'gradient.linear(' . implode( ', ', $stops ) . ', angle: ' . rtrim( rtrim( number_format( $angle, 4, '.', '' ), '0' ), '.' ) . 'deg)';
	}

	if ( 'image' === $background_type ) {
		$overlay = is_array( $background['overlay'] ?? null ) ? $background['overlay'] : array();
		return almaden_bookster_typst_page_style_color_literal( $overlay['color'] ?? ( $background['color'] ?? '#ffffff' ), '#ffffff' );
	}

	return almaden_bookster_typst_page_style_color_literal( $background['color'] ?? '#ffffff', '#ffffff' );
}

function almaden_bookster_typst_build_document_prefix( $context, $payload ) {
	extract( $context, EXTR_SKIP );
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
	$page_styles = isset( $page_styles ) && is_array( $page_styles ) ? $page_styles : array();
	$page_style_resolvers = array(
		'fill' => array(),
		'content' => array(),
		'header' => array(),
		'footer' => array(),
		'opening' => array(),
	);
	foreach ( $page_styles as $page_style ) {
		$page_style = is_array( $page_style ) ? $page_style : array();
		$page_number = isset( $page_style['resolved_page'] ) && is_numeric( $page_style['resolved_page'] )
			? max( 1, (int) $page_style['resolved_page'] )
			: ( isset( $page_style['page_number'] ) && is_numeric( $page_style['page_number'] ) ? max( 1, (int) $page_style['page_number'] ) : 0 );
		if ( $page_number < 1 ) {
			continue;
		}

		$style = isset( $page_style['style'] ) && is_array( $page_style['style'] ) ? $page_style['style'] : array();
		$background = isset( $style['background'] ) && is_array( $style['background'] ) ? $style['background'] : array();
		$text_colors = isset( $style['text_colors'] ) && is_array( $style['text_colors'] ) ? $style['text_colors'] : array();
		$background_type = strtolower( trim( (string) ( $background['type'] ?? 'color' ) ) );
		$page_style_resolvers['fill'][] = array(
			'page' => $page_number,
			'expr' => almaden_bookster_typst_page_style_background_paint( $background ),
		);
		foreach ( array( 'content', 'header', 'footer', 'opening' ) as $kind ) {
			$color = isset( $text_colors[ $kind ] ) ? (string) $text_colors[ $kind ] : '#111111';
			$color = strtolower( trim( ltrim( $color, '#' ) ) );
			if ( ! preg_match( '/^(?:[0-9a-f]{3}|[0-9a-f]{6}|[0-9a-f]{8})$/', $color ) ) {
				$color = '111111';
			}
			$page_style_resolvers[ $kind ][] = array(
				'page' => $page_number,
				'expr' => almaden_bookster_typst_page_style_color_literal( '#' . $color, '#111111' ),
			);
		}
	}
	$page_style_build_expression = static function ( $entries, $fallback_color ) {
		$parts = array();
		foreach ( (array) $entries as $entry ) {
			$parts[] = 'if current == ' . (int) ( $entry['page'] ?? 0 ) . ' { ' . (string) ( $entry['expr'] ?? $fallback_color ) . ' }';
		}
		$parts[] = '{ ' . $fallback_color . ' }';
		return implode( ' else ', $parts );
	};
	$page_style_fill_expr = $page_style_build_expression( $page_style_resolvers['fill'], almaden_bookster_typst_page_style_color_literal( '#ffffff', '#ffffff' ) );
	$page_style_content_expr = $page_style_build_expression( $page_style_resolvers['content'], almaden_bookster_typst_page_style_color_literal( '#111111', '#111111' ) );
	$page_style_header_expr = $page_style_build_expression( $page_style_resolvers['header'], almaden_bookster_typst_page_style_color_literal( '#111111', '#111111' ) );
	$page_style_footer_expr = $page_style_build_expression( $page_style_resolvers['footer'], almaden_bookster_typst_page_style_color_literal( '#111111', '#111111' ) );
	$page_style_opening_expr = $page_style_build_expression( $page_style_resolvers['opening'], almaden_bookster_typst_page_style_color_literal( '#111111', '#111111' ) );
	$transition_blank_mode = almaden_bookster_typst_chapter_transition_mode( $settings );
	$transition_blank_text = trim( (string) ( $settings['chapter_transition_blank_text'] ?? '...' ) );

	$source  = '#let almaden-chapter-transition-mode = "' . almaden_bookster_typst_escape_string( $transition_blank_mode ) . '"' . "\n";
	$source .= '#let almaden-is-chapter-transition-page() = {' . "\n";
	$source .= '  let current = here().page()' . "\n";
	$source .= '  let starts_next = query(<almaden-chapter-start>).any(mark => mark.location().page() == current + 1)' . "\n";
	$source .= '  let breaks = query(<almaden-chapter-parity-break>)' . "\n";
	$source .= '  let break_here = breaks.any(mark => mark.location().page() == current)' . "\n";
	$source .= '  let break_before = current > 1 and breaks.any(mark => mark.location().page() == current - 1)' . "\n";
	$source .= '  starts_next and (break_before or (current == 1 and break_here))' . "\n";
	$source .= '}' . "\n\n";
	$source .= '#let almaden-page-style-color(kind) = {' . "\n";
	$source .= '  let current = here().page()' . "\n";
	$source .= '  let force_white = almaden-is-chapter-transition-page() and almaden-chapter-transition-mode != "blank_with_header_footer"' . "\n";
	$source .= '  if kind == "fill" and force_white { rgb("ffffff") }' . "\n";
	$source .= '  else if kind == "fill" { ' . $page_style_fill_expr . ' }' . "\n";
	$source .= '  else if kind == "header" { ' . $page_style_header_expr . ' }' . "\n";
	$source .= '  else if kind == "footer" { ' . $page_style_footer_expr . ' }' . "\n";
	$source .= '  else if kind == "opening" { ' . $page_style_opening_expr . ' }' . "\n";
	$source .= '  else { ' . $page_style_content_expr . ' }' . "\n";
	$source .= '}' . "\n\n";
	$source .= '#let almaden-page-background() = context {' . "\n";
	$source .= '  rect(width: 100%, height: 100%, fill: almaden-page-style-color("fill"))' . "\n";
	$source .= ( 'intentional_text' === $transition_blank_mode && '' !== $transition_blank_text
		? '  if almaden-is-chapter-transition-page() { place(center + horizon)[#text(fill: rgb("111111"))[' . almaden_bookster_typst_escape_markup( $transition_blank_text ) . ']] }' . "\n"
		: '' ) ;
	$source .= '}' . "\n\n";
	$source .= '#let almaden-page-styled(kind, body) = context {' . "\n";
	$source .= '  set text(fill: almaden-page-style-color(kind))' . "\n";
	$source .= '  body' . "\n";
	$source .= '}' . "\n\n";

	$source .= '#let almaden-current-chapter-title() = context {' . "\n";
	$source .= '  let current = here().page()' . "\n";
	$source .= '  let marks = query(<almaden-chapter-start>).filter(mark => mark.location().page() <= current)' . "\n";
	$source .= '  if marks.len() > 0 { marks.last().value } else { "" }' . "\n";
	$source .= '}' . "\n\n";

	$source .= '#let almaden-resolve-running-element(book_title, first_page_show, first_page_type, first_page_custom, even_type, even_custom, odd_type, odd_custom, text_transform, running_area) = context {' . "\n";
	$source .= '  let current = here().page()' . "\n";
	$source .= '  let chapter_marks = query(<almaden-chapter-start>).filter(mark => mark.location().page() <= current)' . "\n";
	$source .= '  let chapter_start = if chapter_marks.len() > 0 { chapter_marks.last().location().page() } else { 0 }' . "\n";
	$source .= '  let is_first_chapter_page = chapter_marks.filter(mark => mark.location().page() == current).len() > 0' . "\n";
	$source .= '  let is_chapter_opening_page = query(<almaden-chapter-opening>).filter(mark => mark.location().page() == current).len() > 0' . "\n";
	$source .= '  let chapter_image_marks = query(<almaden-chapter-image-page>).filter(mark => mark.location().page() < current)' . "\n";
	$source .= '  let is_first_text_page_after_image = chapter_image_marks.len() > 0 and chapter_image_marks.last().location().page() == current - 1' . "\n";
	$source .= '  let is_chapter_image_page = query(<almaden-chapter-image-page>).any(mark => mark.location().page() == current)' . "\n";
	$source .= '  let is_intentional_blank = query(<almaden-intentional-blank>).filter(mark => mark.location().page() == current).len() > 0' . "\n";
	$source .= '  let hides_transition_running = almaden-is-chapter-transition-page() and almaden-chapter-transition-mode != "blank_with_header_footer"' . "\n";
	$source .= '  let suppress_marker = if running_area == "header" { <almaden-hide-header> } else { <almaden-hide-footer> }' . "\n";
	$source .= '  let suppress_page_marker = if running_area == "header" { <almaden-hide-header-page> } else { <almaden-hide-footer-page> }' . "\n";
	$source .= '  let chapter_suppresses = query(suppress_marker).filter(mark => mark.location().page() >= chapter_start and mark.location().page() <= current).len() > 0' . "\n";
	$source .= '  let page_suppresses = query(suppress_page_marker).any(mark => mark.location().page() == current)' . "\n";
	$source .= '  let is_even = calc.even(current)' . "\n";
	$source .= '  let use_first_page_config = is_chapter_opening_page or is_first_text_page_after_image or is_first_chapter_page' . "\n";
	$source .= '  let kind = if is_intentional_blank or hides_transition_running or chapter_suppresses or page_suppresses or is_chapter_image_page {' . "\n";
	$source .= '    "blank"' . "\n";
	$source .= '  } else if use_first_page_config {' . "\n";
	$source .= '    if first_page_show { first_page_type } else { "blank" }' . "\n";
	$source .= '  } else if is_even {' . "\n";
	$source .= '    even_type' . "\n";
	$source .= '  } else {' . "\n";
	$source .= '    odd_type' . "\n";
	$source .= '  }' . "\n";
	$source .= '  let custom = if use_first_page_config { first_page_custom } else if is_even { even_custom } else { odd_custom }' . "\n";
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

	$source .= '#let almaden-running-area(content, running_area, box_align, font_family, font_size, font_weight, font_style, letter_spacing, margin_top, margin_bottom, hyphenate) = context {' . "\n";
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
	$source .= '    let running_fill = almaden-page-style-color(running_area)' . "\n";
	$source .= '    box(width: 100%, inset: (top: margin_top, bottom: margin_bottom))[' . "\n";
	$source .= '      #set text(fill: running_fill, font: font_family, size: font_size, weight: font_weight, style: font_style, tracking: letter_spacing, hyphenate: hyphenate)' . "\n";
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
		' fill: rgb("ffffff"),' .
		' background: almaden-page-background(),' .
		' binding: left, bleed: ' . $bleed . $unit . ',' .
		' header: context {' . "\n" .
		'  let running = almaden-resolve-running-element("' . almaden_bookster_typst_escape_string( $book_title ) . '", ' . ( $first_page_header_show ? 'true' : 'false' ) . ', "' . almaden_bookster_typst_escape_string( $first_page_header_type ) . '", "' . almaden_bookster_typst_escape_string( $first_page_header_custom ) . '", "' . almaden_bookster_typst_escape_string( $header_even_type ) . '", "' . almaden_bookster_typst_escape_string( $header_even_custom ) . '", "' . almaden_bookster_typst_escape_string( $header_odd_type ) . '", "' . almaden_bookster_typst_escape_string( $header_odd_custom ) . '", "' . almaden_bookster_typst_escape_string( $header_text_transform ) . '", "header")' . "\n" .
		'  almaden-running-area(running, "header", "' . almaden_bookster_typst_escape_string( $header_align ) . '", "' . $header_font_family . '", ' . $header_font_size . 'pt, ' . $header_font_weight . ', "' . almaden_bookster_typst_escape_string( $header_font_style ) . '", ' . $header_letter_spacing . 'pt, ' . $header_margin_top . $unit . ', ' . $header_margin_bottom . $unit . ', ' . ( $header_hyphenate ? 'true' : 'false' ) . ')' . "\n" .
		'},' . "\n" .
		' footer: context {' . "\n" .
		'  let running = almaden-resolve-running-element("' . almaden_bookster_typst_escape_string( $book_title ) . '", ' . ( $first_page_footer_show ? 'true' : 'false' ) . ', "' . almaden_bookster_typst_escape_string( $first_page_footer_type ) . '", "' . almaden_bookster_typst_escape_string( $first_page_footer_custom ) . '", "' . almaden_bookster_typst_escape_string( $footer_even_type ) . '", "' . almaden_bookster_typst_escape_string( $footer_even_custom ) . '", "' . almaden_bookster_typst_escape_string( $footer_odd_type ) . '", "' . almaden_bookster_typst_escape_string( $footer_odd_custom ) . '", "' . almaden_bookster_typst_escape_string( $footer_text_transform ) . '", "footer")' . "\n" .
		'  almaden-running-area(running, "footer", "' . almaden_bookster_typst_escape_string( $footer_align ) . '", "' . $footer_font_family . '", ' . $footer_font_size . 'pt, ' . $footer_font_weight . ', "' . almaden_bookster_typst_escape_string( $footer_font_style ) . '", ' . $footer_letter_spacing . 'pt, ' . $footer_margin_top . $unit . ', ' . $footer_margin_bottom . $unit . ', false)' . "\n" .
		'})' . "\n";
	$source .= '#set text(fill: rgb("111111"), font: "' . almaden_bookster_typst_escape_string( $font_family ) . '", size: ' .
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
	return get_defined_vars();
}
